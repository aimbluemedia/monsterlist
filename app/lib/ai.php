<?php
// "AI fill": fetch a business's website, have Claude extract listing fields.
// Raw cURL against the Anthropic Messages API (no Composer needed on shared
// hosting). Structured outputs guarantee the response matches our schema.

/** API key from app/config.php, or (easier) Admin → Settings. */
function ai_api_key(): string
{
    $key = $GLOBALS['config']['anthropic']['api_key'] ?? '';
    return $key !== '' ? $key : setting('anthropic_api_key');
}

function ai_configured(): bool
{
    return ai_api_key() !== '';
}

/** Which model to ask. One place, so the two callers cannot drift apart. */
function ai_model(): string
{
    return (string)($GLOBALS['config']['anthropic']['model'] ?? 'claude-opus-5');
}

/**
 * POST a Messages request and hand back the decoded body.
 *
 * Both AI features talk to the same endpoint with the same headers, and the
 * failures worth naming are the same ones — a key that is refused, a model
 * that declines, an answer cut off mid-sentence. Doing that twice invited the
 * two copies to drift, so it lives here.
 */
function ai_request(array $body, int $timeout, ?string &$error = null): ?array
{
    $error   = null;
    $apiBase = rtrim($GLOBALS['config']['anthropic']['base_url'] ?? 'https://api.anthropic.com', '/');

    $ch = curl_init($apiBase . '/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . ai_api_key(),
            'anthropic-version: 2023-06-01',
        ],
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        $error = curl_errno($ch) === CURLE_OPERATION_TIMEDOUT
            ? 'The AI took longer than ' . $timeout . ' seconds and was cut off. Try again — a second run is usually quicker.'
            : 'Could not reach the AI service: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }
    curl_close($ch);

    $data = json_decode($resp, true);
    if (!is_array($data))          { $error = 'The AI service sent something that was not a reply.'; return null; }
    if (isset($data['error']))     { $error = 'AI service error: ' . ($data['error']['message'] ?? 'unknown'); return null; }
    return $data;
}

/**
 * Why the search tools failed, if they did.
 *
 * A server-side tool that cannot run does not raise anything: the request comes
 * back 200 and the tool's result block holds an error object instead of the
 * usual list of results. Left unread, a key whose organisation has web search
 * switched off looks exactly like a business nobody has written about — so read
 * it, and say which it was.
 */
function ai_tool_error(array $data): string
{
    foreach ($data['content'] ?? [] as $block) {
        $type = $block['type'] ?? '';
        if ($type !== 'web_search_tool_result' && $type !== 'web_fetch_tool_result') continue;
        $content = $block['content'] ?? null;
        // A list is results. An object is a failure.
        if (is_array($content) && isset($content['error_code'])) return (string)$content['error_code'];
    }
    return '';
}

/** Concatenate the text blocks of a Messages response, skipping tool traffic. */
function ai_text_blocks(array $data): string
{
    $out = [];
    foreach ($data['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'text' && trim((string)$block['text']) !== '') $out[] = $block['text'];
    }
    return implode("\n\n", $out);
}

/**
 * Fetch a public website with SSRF protection: http(s) only, standard ports,
 * public IPs only, redirects re-validated hop by hop, response capped at 1 MB.
 * Returns HTML or null (with $error set).
 */
function fetch_website(string $url, ?string &$error = null): ?string
{
    $error = null;
    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $url) && !preg_match('#^https?://#i', $url)) {
        $error = 'Only http(s) website addresses are supported.';
        return null;
    }
    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;

    for ($hop = 0; $hop < 4; $hop++) {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host']) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            $error = 'That does not look like a valid website address.';
            return null;
        }
        if (!empty($parts['port']) && !in_array((int)$parts['port'], [80, 443], true)) {
            $error = 'Only standard web ports are supported.';
            return null;
        }
        $ips = gethostbynamel($parts['host']) ?: [];
        if (!$ips) { $error = 'We could not find that website — check the address.'; return null; }
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $error = 'That address is not reachable from here.';
                return null;
            }
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,     // redirects handled manually so each hop is re-validated
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT      => setting('site_name') . 'Bot/1.0 (+' . site_url('/') . ')',
            CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
        ]);
        $body   = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $redir  = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);

        if ($body === false) { $error = 'We could not reach that website. Is it online?'; return null; }
        if ($status >= 300 && $status < 400 && $redir) { $url = $redir; continue; }
        if ($status >= 400) { $error = "That website returned an error (HTTP $status)."; return null; }
        return mb_substr($body, 0, 1024 * 1024);
    }
    $error = 'Too many redirects on that website.';
    return null;
}

/**
 * Boil a page down to what matters for extraction: title, meta/OG tags,
 * JSON-LD blocks, social/contact links, and readable text.
 */
function website_to_context(string $html, string $url): string
{
    $out = ["SOURCE URL: $url"];

    if (preg_match('#<title[^>]*>(.*?)</title>#is', $html, $m)) {
        $out[] = 'PAGE TITLE: ' . trim(html_entity_decode(strip_tags($m[1])));
    }
    foreach (['description', 'og:title', 'og:description', 'og:site_name'] as $metaName) {
        if (preg_match('#<meta[^>]+(?:name|property)=["\']' . preg_quote($metaName, '#') . '["\'][^>]+content=["\']([^"\']*)["\']#i', $html, $m)) {
            $out[] = strtoupper($metaName) . ': ' . html_entity_decode($m[1]);
        }
    }
    // Structured data the site already publishes (often has address, phone, hours)
    if (preg_match_all('#<script[^>]+application/ld\+json[^>]*>(.*?)</script>#is', $html, $m)) {
        foreach (array_slice($m[1], 0, 3) as $ld) {
            $out[] = 'JSON-LD: ' . mb_substr(trim($ld), 0, 2000);
        }
    }
    // Social + contact links straight from hrefs (more reliable than prose)
    if (preg_match_all('#href=["\']([^"\']+)["\']#i', $html, $m)) {
        $links = [];
        foreach (array_unique($m[1]) as $href) {
            if (preg_match('#^(?:https?:)?//(?:www\.)?(facebook|instagram|tiktok|youtube|pinterest|linkedin|reddit|x|twitter)\.com/[^"\']+#i', $href)
                || str_starts_with($href, 'tel:') || str_starts_with($href, 'mailto:')) {
                $links[] = $href;
            }
            if (count($links) >= 15) break;
        }
        if ($links) $out[] = 'CONTACT/SOCIAL LINKS: ' . implode(' | ', $links);
    }
    // Visible text
    $text = preg_replace('#<(script|style|noscript|svg|iframe)[^>]*>.*?</\1>#is', ' ', $html);
    $text = preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text)));
    $out[] = 'PAGE TEXT: ' . mb_substr(trim($text), 0, 12000);

    return implode("\n\n", $out);
}

/** JSON schema Claude's answer must match (structured outputs). */
function ai_listing_schema(array $categoryIds): array
{
    // A nullable field is a union type, and structured outputs allow at most 16
    // of those per schema. Keep nullables for the fields where null is
    // meaningful, and use the empty string for the long tail (the socials).
    $nullableStr  = ['type' => ['string', 'null']];
    $emptyableStr = ['type' => 'string', 'description' => 'Full profile URL, or an empty string if the site does not link to one'];
    return [
        'type' => 'object',
        'properties' => [
            'name'         => ['type' => 'string', 'description' => 'Official business name, without legal suffixes unless part of the brand'],
            'tagline'      => $nullableStr + ['description' => 'One catchy line (max 120 chars) selling the business, drawn from their own messaging'],
            'description'  => $nullableStr + ['description' => '2-4 sentence plain-text description of what the business does, who it serves, and what makes it stand out. Write in third person.'],
            'category_id'  => ['type' => 'string', 'enum' => $categoryIds, 'description' => 'Best-fitting directory category'],
            // An enum, not free text: this value goes straight into the
            // page's @type, and a Schema.org type that does not exist is
            // worse than the general one that does. The empty string is in
            // the list so "I cannot tell" has somewhere to go.
            'business_type' => ['type' => 'string', 'enum' => array_merge([''], array_keys(schema_type_labels())),
                                'description' => 'Schema.org LocalBusiness subtype that best describes this business, '
                                               . 'e.g. Plumber, Dentist, Attorney. Empty string if none clearly fits.'],
            'country_code' => $nullableStr + ['description' => 'ISO 3166-1 alpha-2 country code of the business location, e.g. US, GB'],
            'us_state'     => $nullableStr + ['description' => 'Full US state name (e.g. Arizona) if the business is in the United States, else null'],
            'city'         => $nullableStr + ['description' => 'City the business operates from'],
            'address'      => $nullableStr + ['description' => 'Street address if published'],
            'phone'        => $nullableStr + ['description' => 'Primary phone number as published'],
            'email'        => $nullableStr + ['description' => 'Public contact email'],
            'founded'      => ['type' => ['integer', 'null'], 'description' => 'Year founded, if stated'],
            // No maxItems: the structured-output schema subset rejects array
            // size constraints ("For 'array' type, property 'maxItems' is not
            // supported"). The limit is stated in the description and enforced
            // in ai_postprocess(), which is the real guarantee anyway.
            'services'     => [
                'type'  => 'array',
                'items' => ['type' => 'string'],
                'description' => 'At most 10 short names of services or offerings this business actually '
                               . 'provides, each 1-4 words, title case, taken from their own wording. Return '
                               . 'an empty array if the page does not say what they offer. Never list more '
                               . 'than 10 — extras are discarded.',
            ],
            // Plain strings, not nullable ones. Structured outputs cap a schema
            // at 16 union-typed parameters ("too many parameters with union
            // types … limit: 16"), and every nullable field is a union — the
            // 9 above plus 8 socials came to 17 and the whole request was
            // rejected. Empty string means "not found"; clean_url() maps "" to
            // null in ai_postprocess(), so nothing downstream changes.
            'social'       => [
                'type' => 'object',
                'properties' => [
                    'facebook'  => $emptyableStr, 'instagram' => $emptyableStr, 'tiktok'   => $emptyableStr,
                    'youtube'   => $emptyableStr, 'pinterest' => $emptyableStr, 'linkedin' => $emptyableStr,
                    'reddit'    => $emptyableStr, 'x'         => $emptyableStr,
                ],
                'required' => ['facebook', 'instagram', 'tiktok', 'youtube', 'pinterest', 'linkedin', 'reddit', 'x'],
                'additionalProperties' => false,
            ],
        ],
        'required' => ['name', 'tagline', 'description', 'category_id', 'business_type', 'country_code',
                       'us_state', 'city', 'address', 'phone', 'email', 'founded', 'services', 'social'],
        'additionalProperties' => false,
    ];
}

/**
 * Ask Claude to extract listing fields from the website context.
 * Returns the decoded array, or null with $error set.
 */
function ai_extract_listing(string $url, ?string &$error = null): ?array
{
    $error = null;
    if (!ai_configured()) { $error = 'AI fill is not configured yet (missing Anthropic API key in app/config.php).'; return null; }

    $html = fetch_website($url, $error);
    if ($html === null) return null;
    $context = website_to_context($html, $url);

    $cats = categories_all();
    $catLines = array_map(fn($c) => $c['id'] . ' = ' . $c['label'], $cats);
    $catIds   = array_column($cats, 'id');

    $body = [
        'model'      => ai_model(),
        'max_tokens' => 2048,
        'system'     => 'You extract business-directory listing data from website content. '
                      . 'Only state facts supported by the provided content — use null for anything not present. '
                      . 'Never invent contact details, addresses, or founding years.',
        'messages'   => [[
            'role'    => 'user',
            'content' => "Extract listing data for this small business from its website content below.\n\n"
                       . "Available categories (pick the single best category_id):\n" . implode("\n", $catLines) . "\n\n"
                       . "WEBSITE CONTENT:\n" . $context,
        ]],
        'output_config' => ['format' => ['type' => 'json_schema', 'schema' => ai_listing_schema($catIds)]],
    ];

    $data = ai_request($body, 120, $error);
    if ($data === null) return null;
    if (($data['stop_reason'] ?? '') === 'refusal') { $error = 'The AI declined to process this website.'; return null; }
    if (($data['stop_reason'] ?? '') === 'max_tokens') { $error = 'The AI response was cut short — please try again.'; return null; }

    $text = null;
    foreach ($data['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'text') { $text = $block['text']; break; }
    }
    $fields = $text !== null ? json_decode($text, true) : null;
    if (!is_array($fields)) { $error = 'The AI returned an unexpected response — please try again.'; return null; }

    return ai_postprocess($fields, $url);
}

/** Validate/normalize the model's output against our database before it reaches the form. */
function ai_postprocess(array $f, string $sourceUrl): array
{
    $out = [
        'name'        => mb_substr(trim((string)($f['name'] ?? '')), 0, 180),
        'tagline'     => mb_substr(trim((string)($f['tagline'] ?? '')), 0, 255),
        'description' => mb_substr(trim((string)($f['description'] ?? '')), 0, 5000),
        'address'     => mb_substr(trim((string)($f['address'] ?? '')), 0, 255),
        'phone'       => mb_substr(trim((string)($f['phone'] ?? '')), 0, 40),
        'city'        => mb_substr(trim((string)($f['city'] ?? '')), 0, 140),
        'website'     => clean_url($sourceUrl) ?? '',
    ];

    $out['category_id'] = category_by_id((string)($f['category_id'] ?? '')) ? $f['category_id'] : '';
    // Checked against our own list rather than trusted: the model is told
    // the enum, but the value ends up in @type and is worth verifying.
    $out['business_type'] = schema_type_valid($f['business_type'] ?? null) ? (string)$f['business_type'] : '';

    $cc = strtoupper(trim((string)($f['country_code'] ?? '')));
    $out['country'] = (preg_match('/^[A-Z]{2}$/', $cc) && row('SELECT code FROM countries WHERE code = ?', [$cc])) ? $cc : '';

    $out['region'] = '';
    if ($out['country'] === 'US' && !empty($f['us_state'])) {
        $state = row('SELECT slug FROM regions WHERE country_code = "US" AND (name = ? OR code = ?)',
                     [trim((string)$f['us_state']), strtoupper(trim((string)$f['us_state']))]);
        $out['region'] = $state['slug'] ?? '';
    }

    $email = filter_var(trim((string)($f['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $out['email'] = $email ?: '';

    $founded = (int)($f['founded'] ?? 0);
    $out['founded'] = ($founded >= 1800 && $founded <= (int)date('Y')) ? $founded : '';

    // Short, de-duplicated service names for the wizard's suggestion bubbles.
    $out['services'] = [];
    foreach ((array)($f['services'] ?? []) as $svc) {
        $svc = trim(preg_replace('/\s+/u', ' ', (string)$svc));
        if ($svc === '') continue;
        $svc = mb_substr($svc, 0, 80);
        if (in_array(mb_strtolower($svc), array_map('mb_strtolower', $out['services']), true)) continue;
        $out['services'][] = $svc;
        if (count($out['services']) >= 10) break;
    }

    $out['social'] = [];
    foreach (array_keys(social_nets()) as $net) {   // one shared list; absent keys are simply skipped
        $v = $f['social'][$net] ?? null;
        $clean = $v ? clean_url((string)$v) : null;
        if ($clean) $out['social'][$net] = $clean;
    }
    return $out;
}

// ---------------------------------------------------------------------------
// Writing the Profile section.
//
// The Profile is the one part of a listing that is genuinely long — up to
// PROFILE_MAX_WORDS — and it is the part nobody writes. A business owner who
// will happily type a phone number will not sit down and write 1,200 words
// about their own company, so on most paid listings the section stays empty
// and the storefront is poorer for it.
//
// This is a different job from ai_extract_listing(). That one pulls facts out
// of one page and puts them in fields. This one has to find enough material to
// say something for 1,200 words, which one page rarely holds — so Claude gets
// web search and web fetch and goes looking: the rest of the business's own
// site, and whatever else about it is public.
//
// Everything it writes is a claim about a real business, so the rules below
// are strict about invention. A profile that reads well and says something
// untrue is worse than no profile: it is the directory putting words in
// somebody's mouth. Nothing is saved automatically either — the text lands in
// the editing box for a person to read, change and then save.
// ---------------------------------------------------------------------------

/** The floor. Under this and something went wrong, whatever the reason. */
const PROFILE_MIN_WORDS = 600;

/**
 * What we already know, written out for the prompt.
 *
 * Given to Claude as established fact so it does not go looking for what we
 * can already tell it, and so the profile agrees with the rest of the page.
 */
function profile_known_facts(array $b): string
{
    $lines = [];
    $add = function (string $label, $value) use (&$lines) {
        $value = trim((string)$value);
        if ($value !== '') $lines[] = $label . ': ' . $value;
    };

    $add('Business name', $b['name'] ?? '');
    $add('Website', $b['website'] ?? '');
    $add('Category', $b['category_label'] ?? '');
    if (!empty($b['business_type'])) $add('Type of business', schema_type_label((string)$b['business_type']));
    $add('Tagline', $b['tagline'] ?? '');
    $add('Short description already on the listing', $b['description'] ?? '');
    $add('Street address', $b['address'] ?? '');

    $place = array_filter([$b['city_name'] ?? '', $b['region_name'] ?? '', $b['country_name'] ?? '']);
    $add('City', implode(', ', $place));
    $add('Year founded', !empty($b['founded']) ? (string)(int)$b['founded'] : '');

    // Services and products live in their own tables, and they are the most
    // useful thing we hold: they say what the business actually sells, in the
    // business's own words. Worth a query rather than leaving them out.
    if (!empty($b['id'])) {
        $svc = rows('SELECT name, description FROM services WHERE business_id = ? ORDER BY id LIMIT 25', [(int)$b['id']]);
        if ($svc) {
            $add('Services on the listing', implode(' | ', array_map(
                fn($s) => trim($s['name'] . ($s['description'] ? ' — ' . $s['description'] : '')), $svc)));
        }
        $prod = rows('SELECT name, note FROM products WHERE business_id = ? ORDER BY id LIMIT 25', [(int)$b['id']]);
        if ($prod) {
            $add('Products on the listing', implode(' | ', array_map(
                fn($p) => trim($p['name'] . ($p['note'] ? ' — ' . $p['note'] : '')), $prod)));
        }
    }

    $social = json_decode((string)($b['social'] ?? ''), true);
    if (is_array($social) && $social) $add('Social profiles', implode(' | ', array_filter($social)));

    $reviews = wizard_links($b['review_links'] ?? null);
    if ($reviews) $add('Review profiles', implode(' | ', array_slice($reviews, 0, 10)));

    return $lines ? implode("\n", $lines) : '(nothing on file yet)';
}

/** The rules. Kept apart from the request so they can be read as prose. */
function profile_instructions(array $b): string
{
    $name = trim((string)($b['name'] ?? 'this business'));
    $city = trim((string)($b['city_name'] ?? ''));
    $site = trim((string)($b['website'] ?? ''));

    return <<<TXT
Write the Profile section for {$name}'s listing in a small-business directory.

RESEARCH FIRST, THEN WRITE.
Use web search and web fetch before writing a word. Read the rest of the
business's own website — the about page, services pages, project or gallery
pages, FAQ, team page — because one page is rarely enough material. Then look
for what else is public about it.

BE SURE IT IS THE SAME BUSINESS.
Company names repeat across towns and trades. Only use a source you can tie to
this business by its website domain, address, phone number or city. If a result
is about a different company with a similar name, discard it — do not blend two
businesses into one profile. If you cannot confirm which one a source is about,
leave it out.

WHAT THE PROFILE HAS TO BE.
- Between 1,000 and 1,500 words. Under 1,000 is too short to be worth the
  section; over 1,500 gets cut off.
- Plain paragraphs separated by a blank line. No headings, no bullet points,
  no numbered lists, no markdown, no bold or italics, no emoji. Every one of
  those shows up on the page as literal characters, because the storefront
  prints this text as written.
- Third person. This is the directory describing the business, not the
  business talking about itself — so "{$name} has worked in {$city} since…",
  never "we" or "our team".
- Specific. Name the actual services, the actual materials, the actual sorts
  of job. A paragraph that would fit any business in the trade is wasted.
- Readable by a person first. It will be indexed, but writing for the index is
  what makes directory copy unreadable. Mention the city and the trade because
  they are true and relevant, not to hit a count.

WHAT IT MUST NOT DO.
- Never invent a fact. No made-up founding years, staff numbers, prices,
  guarantees, certifications, licence numbers, awards, charity work, review
  scores or years of experience. If the research did not establish it, it does
  not go in — and do not paper over the gap with "reportedly" or "is said to".
- No phone numbers, email addresses or street addresses. The listing shows
  those separately, and a stale one here is worse than none.
- No superlatives you cannot support: not "the best", "#1", "award-winning",
  "leading" or "trusted by thousands" unless a source actually says so and you
  can name what it is.
- No prices, discounts, offers or opening hours — all of them go out of date.
- Do not write about the directory, this listing, or the fact that you are an
  AI. Write about the business.

IF THERE IS NOT ENOUGH TO SAY.
Write what the sources support and stop, even if that is well short of 1,000
words. A short honest profile is fine. Padding it with invented detail is not.

HOW TO ANSWER.
Put the finished profile between <profile> and </profile> tags, with nothing
else inside them. Anything you want to say about your research goes outside
the tags, where it will be discarded.
TXT;
}

/**
 * Research and write a Profile section. Returns the text, or null with $error.
 *
 * $b is a business row — or the half-filled form the staff editor is holding,
 * which is the same shape. Nothing is written to the database here.
 */
function ai_write_profile(array $b, ?string &$error = null): ?string
{
    $error = null;
    if (!ai_configured()) {
        $error = 'AI is not configured yet — add an Anthropic API key in Superadmin → Settings.';
        return null;
    }
    if (trim((string)($b['name'] ?? '')) === '') {
        $error = 'Give the listing a business name first — there is nothing to research without one.';
        return null;
    }

    // The home page, fetched by us. Claude can fetch pages itself, and will,
    // but a site that refuses its fetcher would otherwise leave it with only
    // the name to go on. This is the floor under the research, not the whole
    // of it, so a failure here is a note in the prompt rather than an error.
    $context = '';
    $url = clean_url((string)($b['website'] ?? ''));
    if ($url) {
        $fetchError = null;
        $html = fetch_website($url, $fetchError);
        $context = $html !== null
            ? "THE BUSINESS'S HOME PAGE, ALREADY FETCHED:\n" . website_to_context($html, $url)
            : "NOTE: the home page at {$url} could not be read from here ({$fetchError}). Try fetching it yourself.";
    }

    // Long-running: research, then twelve hundred words. Shared hosting caps
    // execution time low enough to kill this mid-flight, so ask for more.
    if (function_exists('set_time_limit')) @set_time_limit(360);

    $ask = [
        'role'    => 'user',
        'content' => profile_instructions($b)
                   . "\n\nWHAT THE DIRECTORY ALREADY HOLDS (treat as established fact):\n"
                   . profile_known_facts($b)
                   . ($context !== '' ? "\n\n" . $context : ''),
    ];
    $messages = [$ask];

    // A server-side tool loop can hit its own iteration limit and hand back
    // stop_reason "pause_turn" with the work half done. Sending the turn
    // straight back resumes it — no extra instruction, the API sees the
    // trailing tool block and picks up. Bounded, because a loop that cannot
    // finish must end as an error rather than run forever.
    //
    // Each resume is the question followed by the paused turn — the paused turn
    // replacing the one before it, not stacking on top. Appending would build
    // two assistant messages in a row, which the API rejects: roles alternate.
    for ($attempt = 0; $attempt < 4; $attempt++) {
        $data = ai_request([
            'model'      => ai_model(),
            'max_tokens' => 16000,
            // Enough thinking to plan the research and hold 1,200 words
            // together; not so much that a staff member watches a spinner for
            // five minutes. Prose is not the hardest thing we ask of it.
            'output_config' => ['effort' => 'medium'],
            'system'     => 'You research small businesses and write accurate, readable long-form '
                          . 'profiles about them for a business directory. You never state anything '
                          . 'you have not confirmed from a source about that specific business.',
            'tools'      => [
                // Dynamic-filtering versions: they run their own code to sift
                // results before those reach the context. Declaring a separate
                // code execution tool alongside them confuses the model, so do
                // not add one here.
                ['type' => 'web_search_20260209', 'name' => 'web_search', 'max_uses' => 8],
                ['type' => 'web_fetch_20260209',  'name' => 'web_fetch',  'max_uses' => 8],
            ],
            'messages'   => $messages,
        ], 300, $error);
        if ($data === null) return null;

        $stop = (string)($data['stop_reason'] ?? '');
        if ($stop === 'refusal') {
            $error = 'The AI declined to write about this business.';
            return null;
        }
        if ($stop === 'pause_turn') {
            $messages = [$ask, ['role' => 'assistant', 'content' => $data['content'] ?? []]];
            continue;
        }
        if ($stop === 'max_tokens') {
            $error = 'The AI ran out of room before finishing. Try again.';
            return null;
        }

        // Research that could not run makes for a thin profile, and "thin
        // profile" is a misleading thing to be told when the real answer is
        // that the key is not allowed to search.
        if ($toolError = ai_tool_error($data)) {
            $error = 'The AI could not search the web (' . $toolError . '). If that says the tool is '
                   . 'disabled, turn web search on for this API key at platform.claude.com → Settings.';
            return null;
        }
        return profile_from_reply(ai_text_blocks($data), $error);
    }

    $error = 'The AI kept going without finishing its research. Try again.';
    return null;
}

/**
 * Pull the profile out of the reply and make it safe to print.
 *
 * The storefront renders this with nl2br() over escaped text — no markdown is
 * interpreted — so a stray "##" or "**" would appear on the page exactly as
 * typed. The model is told not to use them; this is what happens when it does
 * anyway.
 */
function profile_from_reply(string $reply, ?string &$error = null): ?string
{
    $error = null;
    $text  = trim($reply);
    if ($text === '') { $error = 'The AI came back with nothing. Try again.'; return null; }

    // Between the tags if they are there; otherwise take the lot and hope the
    // model kept its commentary to itself.
    if (preg_match('#<profile>(.*?)</profile>#is', $text, $m)) $text = trim($m[1]);

    $text = str_replace("\r\n", "\n", $text);
    $text = preg_replace('/^\s{0,3}#{1,6}\s*/m', '', $text);   // headings
    $text = preg_replace('/^\s{0,3}[-*+]\s+/m', '', $text);    // bullets
    $text = preg_replace('/\*{1,3}([^*]+)\*{1,3}/', '$1', $text); // bold/italic
    $text = preg_replace('/^\s*[-*_]{3,}\s*$/m', '', $text);   // rules
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    $text = trim($text);

    $words = preg_match_all('/\S+/u', $text);
    if ($words < PROFILE_MIN_WORDS) {
        $error = 'The AI could only find enough for ' . number_format($words) . ' words, which is too '
               . 'thin to publish. That usually means the website is very small or mostly images — '
               . 'the box is yours to write in by hand.';
        return null;
    }
    return profile_cap($text);
}
