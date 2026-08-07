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
    $nullableStr = ['type' => ['string', 'null']];
    return [
        'type' => 'object',
        'properties' => [
            'name'         => ['type' => 'string', 'description' => 'Official business name, without legal suffixes unless part of the brand'],
            'tagline'      => $nullableStr + ['description' => 'One catchy line (max 120 chars) selling the business, drawn from their own messaging'],
            'description'  => $nullableStr + ['description' => '2-4 sentence plain-text description of what the business does, who it serves, and what makes it stand out. Write in third person.'],
            'category_id'  => ['type' => 'string', 'enum' => $categoryIds, 'description' => 'Best-fitting directory category'],
            'country_code' => $nullableStr + ['description' => 'ISO 3166-1 alpha-2 country code of the business location, e.g. US, GB'],
            'us_state'     => $nullableStr + ['description' => 'Full US state name (e.g. Arizona) if the business is in the United States, else null'],
            'city'         => $nullableStr + ['description' => 'City the business operates from'],
            'address'      => $nullableStr + ['description' => 'Street address if published'],
            'phone'        => $nullableStr + ['description' => 'Primary phone number as published'],
            'email'        => $nullableStr + ['description' => 'Public contact email'],
            'founded'      => ['type' => ['integer', 'null'], 'description' => 'Year founded, if stated'],
            'services'     => [
                'type'  => 'array',
                'items' => ['type' => 'string'],
                'maxItems' => 10,
                'description' => 'Up to 10 short names of services or offerings this business actually provides, '
                               . 'each 1-4 words, title case, taken from their own wording. Empty array if the '
                               . 'page does not say what they offer.',
            ],
            'social'       => [
                'type' => 'object',
                'properties' => [
                    'facebook'  => $nullableStr, 'instagram' => $nullableStr, 'tiktok'   => $nullableStr,
                    'youtube'   => $nullableStr, 'pinterest' => $nullableStr, 'linkedin' => $nullableStr,
                    'reddit'    => $nullableStr, 'x'         => $nullableStr,
                ],
                'required' => ['facebook', 'instagram', 'tiktok', 'youtube', 'pinterest', 'linkedin', 'reddit', 'x'],
                'additionalProperties' => false,
            ],
        ],
        'required' => ['name', 'tagline', 'description', 'category_id', 'country_code',
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
        'model'      => $GLOBALS['config']['anthropic']['model'] ?? 'claude-opus-4-8',
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

    $apiBase = rtrim($GLOBALS['config']['anthropic']['base_url'] ?? 'https://api.anthropic.com', '/');
    $ch = curl_init($apiBase . '/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . ai_api_key(),
            'anthropic-version: 2023-06-01',
        ],
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) { $error = 'Could not reach the AI service: ' . curl_error($ch); curl_close($ch); return null; }
    curl_close($ch);

    $data = json_decode($resp, true);
    if (isset($data['error'])) { $error = 'AI service error: ' . ($data['error']['message'] ?? 'unknown'); return null; }
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
    foreach (['facebook','instagram','tiktok','youtube','pinterest','linkedin','reddit','x'] as $net) {
        $v = $f['social'][$net] ?? null;
        $clean = $v ? clean_url((string)$v) : null;
        if ($clean) $out['social'][$net] = $clean;
    }
    return $out;
}
