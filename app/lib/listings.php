<?php
// Business listing queries + analytics counters.

const TIER_ORDER = "FIELD(b.tier, 'featured', 'pro', 'free')";

function listings_for_city(int $cityId, int $page = 1, int $perPage = 20): array
{
    $offset = ($page - 1) * $perPage;
    return rows(
        "SELECT b.*, c.label AS category_label, c.icon AS category_icon
         FROM businesses b
         LEFT JOIN categories c ON c.id = b.category_id
         WHERE b.city_id = ? AND b.status = 'live'
         ORDER BY " . TIER_ORDER . ", b.rating DESC, b.review_count DESC
         LIMIT $perPage OFFSET $offset",
        [$cityId]
    );
}

function listings_count_city(int $cityId): int
{
    return (int)scalar("SELECT COUNT(*) FROM businesses WHERE city_id = ? AND status = 'live'", [$cityId]);
}

function listings_for_category(string $catId, int $page = 1, int $perPage = 20): array
{
    $offset = ($page - 1) * $perPage;
    return rows(
        "SELECT b.*, c.label AS category_label, c.icon AS category_icon,
                ci.name AS city_name, ci.slug AS city_slug, ci.country_code, ci.region_id,
                r.slug AS region_slug, r.name AS region_name, co.name AS country_name
         FROM businesses b
         LEFT JOIN categories c ON c.id = b.category_id
         JOIN cities ci ON ci.id = b.city_id
         JOIN countries co ON co.code = ci.country_code
         LEFT JOIN regions r ON r.id = ci.region_id
         WHERE b.category_id = ? AND b.status = 'live'
         ORDER BY " . TIER_ORDER . ", b.rating DESC
         LIMIT $perPage OFFSET $offset",
        [$catId]
    );
}

function listings_count_category(string $catId): int
{
    return (int)scalar("SELECT COUNT(*) FROM businesses WHERE category_id = ? AND status = 'live'", [$catId]);
}

function business_in_city(int $cityId, string $slug): ?array
{
    return row(
        "SELECT b.*, c.label AS category_label, c.icon AS category_icon
         FROM businesses b
         LEFT JOIN categories c ON c.id = b.category_id
         WHERE b.city_id = ? AND b.slug = ? AND b.status = 'live'",
        [$cityId, $slug]
    );
}

function featured_businesses(int $limit = 6): array
{
    return rows(
        "SELECT b.*, c.label AS category_label, c.icon AS category_icon,
                ci.name AS city_name, ci.slug AS city_slug, ci.country_code, ci.region_id,
                r.slug AS region_slug
         FROM businesses b
         LEFT JOIN categories c ON c.id = b.category_id
         JOIN cities ci ON ci.id = b.city_id
         LEFT JOIN regions r ON r.id = ci.region_id
         WHERE b.status = 'live' AND b.tier = 'featured'
         ORDER BY b.rating DESC, b.review_count DESC
         LIMIT " . (int)$limit
    );
}

function newest_businesses(int $limit = 6): array
{
    return rows(
        "SELECT b.*, c.label AS category_label, c.icon AS category_icon,
                ci.name AS city_name, ci.slug AS city_slug, ci.country_code, ci.region_id,
                r.slug AS region_slug
         FROM businesses b
         LEFT JOIN categories c ON c.id = b.category_id
         JOIN cities ci ON ci.id = b.city_id
         LEFT JOIN regions r ON r.id = ci.region_id
         WHERE b.status = 'live'
         ORDER BY b.created_at DESC
         LIMIT " . (int)$limit
    );
}

function search_businesses(string $qstr, int $page = 1, int $perPage = 20): array
{
    $offset = ($page - 1) * $perPage;
    // FULLTEXT natural language; fall back to LIKE for very short queries.
    if (mb_strlen($qstr) >= 3) {
        return rows(
            "SELECT b.*, c.label AS category_label, c.icon AS category_icon,
                    ci.name AS city_name, ci.slug AS city_slug, ci.country_code, ci.region_id,
                    r.slug AS region_slug, r.name AS region_name, co.name AS country_name,
                    MATCH(b.name, b.tagline, b.description) AGAINST (?) AS score
             FROM businesses b
             LEFT JOIN categories c ON c.id = b.category_id
             JOIN cities ci ON ci.id = b.city_id
             JOIN countries co ON co.code = ci.country_code
             LEFT JOIN regions r ON r.id = ci.region_id
             WHERE b.status = 'live' AND MATCH(b.name, b.tagline, b.description) AGAINST (?)
             ORDER BY " . TIER_ORDER . ", score DESC
             LIMIT $perPage OFFSET $offset",
            [$qstr, $qstr]
        );
    }
    $like = '%' . $qstr . '%';
    return rows(
        "SELECT b.*, c.label AS category_label, c.icon AS category_icon,
                ci.name AS city_name, ci.slug AS city_slug, ci.country_code, ci.region_id,
                r.slug AS region_slug, r.name AS region_name, co.name AS country_name
         FROM businesses b
         LEFT JOIN categories c ON c.id = b.category_id
         JOIN cities ci ON ci.id = b.city_id
         JOIN countries co ON co.code = ci.country_code
         LEFT JOIN regions r ON r.id = ci.region_id
         WHERE b.status = 'live' AND b.name LIKE ?
         ORDER BY " . TIER_ORDER . ", b.rating DESC
         LIMIT $perPage OFFSET $offset",
        [$like]
    );
}

/**
 * Does this tier include the premium storefront? Phone, public email, logo,
 * photo gallery, video and social links are all paid features — free listings
 * carry the name, description, category, location and website only.
 */
function tier_enhanced(?string $tier): bool
{
    return in_array((string)$tier, ['pro', 'featured'], true);
}

/**
 * A listing's logo, or null when its tier does not include one.
 *
 * Every card and avatar goes through this rather than reading logo_url direct,
 * so a listing that was uploaded a logo on Pro and then downgraded stops
 * showing it everywhere at once instead of in the places someone remembered.
 */
function listing_logo(array $b): ?string
{
    return tier_enhanced($b['tier'] ?? 'free') && !empty($b['logo_url']) ? (string)$b['logo_url'] : null;
}

/** How many words the Profile section holds on a paid plan. */
const PROFILE_MAX_WORDS = 1500;

/**
 * Trim the Profile section to PROFILE_MAX_WORDS, keeping paragraph breaks.
 *
 * Word-counted rather than character-counted because that is how the limit is
 * sold ("1,500 words"), and cut at the end of the sentence that crosses the
 * limit so it never stops mid-thought.
 */
function profile_cap(string $text): string
{
    $text = trim(str_replace("\r\n", "\n", $text));
    if ($text === '') return '';
    $count = preg_match_all('/\S+/u', $text);
    if ($count <= PROFILE_MAX_WORDS) return $text;

    // Character position of the word limit, then round up to a sentence end.
    if (preg_match('/^(?:\s*\S+){' . PROFILE_MAX_WORDS . '}/u', $text, $m)) {
        return sentence_cap($text, mb_strlen($m[0]), mb_strlen($m[0]) + 400);
    }
    return $text;
}

/** Canonical storefront path. Accepts a row that includes city fields. */
function business_path(array $b): string
{
    $cc = strtolower($b['country_code']);
    if (!empty($b['region_id']) && !empty($b['region_slug'])) {
        return "/$cc/{$b['region_slug']}/{$b['city_slug']}/{$b['slug']}";
    }
    return "/$cc/{$b['city_slug']}/{$b['slug']}";
}

/** Unique slug for a new/renamed business within its city. */
function unique_business_slug(string $name, int $cityId, int $excludeId = 0): string
{
    $base = slugify($name) ?: 'business';
    $slug = $base;
    $n = 2;
    while (row('SELECT id FROM businesses WHERE city_id = ? AND slug = ? AND id != ?', [$cityId, $slug, $excludeId])) {
        $slug = $base . '-' . $n++;
    }
    return $slug;
}

/** Increment a daily analytics counter (view / click / call). */
function track_event(int $businessId, string $event): void
{
    if (!in_array($event, ['view', 'click', 'call'], true)) return;
    q('INSERT INTO listing_events (business_id, event, day, count) VALUES (?, ?, CURDATE(), 1)
       ON DUPLICATE KEY UPDATE count = count + 1', [$businessId, $event]);
}

/**
 * Lifetime totals per event for one listing: ['view' => n, 'click' => n, 'call' => n].
 *
 * listing_events stores a row per business/event/day, so this is a rollup of
 * every day the listing has existed. Missing events read as 0 rather than being
 * absent, so callers never have to guard the key.
 */
function listing_event_totals(int $businessId): array
{
    $totals = ['view' => 0, 'click' => 0, 'call' => 0];
    foreach (rows('SELECT event, SUM(count) AS n FROM listing_events
                   WHERE business_id = ? GROUP BY event', [$businessId]) as $r) {
        if (isset($totals[$r['event']])) $totals[$r['event']] = (int)$r['n'];
    }
    return $totals;
}

/** Recompute a business's cached rating after a review change. */
function refresh_rating(int $businessId): void
{
    q('UPDATE businesses b SET
         b.review_count = (SELECT COUNT(*) FROM reviews WHERE business_id = b.id AND status = "live"),
         b.rating = COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE business_id = b.id AND status = "live"), 0)
       WHERE b.id = ?', [$businessId]);
}

/** Keep cities.listing_count fresh (called on status changes). */
function refresh_city_count(int $cityId): void
{
    q('UPDATE cities SET listing_count =
         (SELECT COUNT(*) FROM businesses WHERE city_id = ? AND status = "live")
       WHERE id = ?', [$cityId, $cityId]);
}

// ---------------------------------------------------------------------------
// Listing form handling. Shared by the member area (/account/listings) and the
// staff control panel (/superadmin/listings), so both write the same fields
// through the same validation.
// ---------------------------------------------------------------------------

/** Find-or-create the city for a submitted location. Returns city id or null. */
// ---------------------------------------------------------------------------
// Opening hours.
//
// Stored as JSON on businesses.hours: one entry per day, always seven, always
// in this order. Times are 24-hour "HH:MM" because that is what <input
// type="time"> gives back and what Schema.org's opens/closes want — the
// storefront turns them into something readable at the point of display, which
// is the only place the format is a matter of taste.
//
// A closed day is kept rather than dropped. "Closed on Sunday" is information a
// visitor came for, and a missing Sunday only tells them nobody filled it in.
// ---------------------------------------------------------------------------

function hours_days(): array
{
    return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
}

/**
 * Read stored hours into a predictable seven-day array.
 *
 * Returns [] when nothing usable is stored, so callers can test it as a
 * boolean. Anything malformed — a hand-edited row, an older shape — is treated
 * as absent rather than half-rendered.
 */
function hours_parse(?string $json): array
{
    $raw = json_decode((string)$json, true);
    if (!is_array($raw)) return [];

    $byDay = [];
    foreach ($raw as $entry) {
        if (!is_array($entry)) continue;
        $day = (string)($entry['d'] ?? '');
        if (!in_array($day, hours_days(), true)) continue;
        $byDay[$day] = $entry;
    }
    if (!$byDay) return [];

    $out = [];
    $any = false;
    foreach (hours_days() as $day) {
        $e    = $byDay[$day] ?? [];
        $open = !empty($e['open']);
        $from = hours_clean((string)($e['from'] ?? ''));
        $to   = hours_clean((string)($e['to'] ?? ''));
        // Open with no times is not a statement anybody can act on, so it is
        // recorded as closed rather than published as an empty promise.
        if ($open && ($from === '' || $to === '')) $open = false;
        if ($open) $any = true;
        $out[] = ['d' => $day, 'open' => $open, 'from' => $from, 'to' => $to];
    }
    // Seven closed days is what an untouched form posts. Nobody means it.
    return $any ? $out : [];
}

/** "HH:MM" or '' — anything else is not a time this will publish. */
function hours_clean(string $t): string
{
    $t = trim($t);
    return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t) ? $t : '';
}

/** Read the seven day rows off a posted form. Returns JSON, or null. */
function hours_from_post(): ?string
{
    $out = [];
    foreach (hours_days() as $day) {
        $key  = strtolower($day);
        $out[] = [
            'd'    => $day,
            'open' => !empty($_POST['hours_open'][$key]),
            'from' => hours_clean((string)($_POST['hours_from'][$key] ?? '')),
            'to'   => hours_clean((string)($_POST['hours_to'][$key] ?? '')),
        ];
    }
    $parsed = hours_parse(json_encode($out));
    return $parsed ? json_encode($parsed, JSON_UNESCAPED_SLASHES) : null;
}

/** "9:00 am – 5:30 pm", for a person reading the storefront. */
function hours_label(array $day): string
{
    if (empty($day['open'])) return 'Closed';
    // Open all day is worth saying in words rather than as 00:00 – 23:59.
    if ($day['from'] === '00:00' && in_array($day['to'], ['23:59', '00:00'], true)) return 'Open 24 hours';
    return hours_time($day['from']) . ' – ' . hours_time($day['to']);
}

function hours_time(string $hhmm): string
{
    [$h, $m] = array_pad(explode(':', $hhmm), 2, '00');
    $h  = (int)$h;
    $ap = $h < 12 ? 'am' : 'pm';
    $h12 = $h % 12 === 0 ? 12 : $h % 12;
    return $m === '00' ? "$h12$ap" : "$h12:$m$ap";
}

function resolve_city(string $countryCode, string $regionSlug, string $cityName): ?int
{
    $country = row('SELECT * FROM countries WHERE code = ?', [$countryCode]);
    if (!$country || $cityName === '') return null;

    $regionId = null;
    if ($country['code'] === 'US') {
        if ($regionSlug === '') return null;
        $region = region_by_slug('US', $regionSlug);
        if (!$region) return null;
        $regionId = (int)$region['id'];
    }
    $slug = slugify($cityName);
    if ($slug === '') return null;

    $existing = $regionId
        ? city_in_region($regionId, $slug)
        : city_in_country($country['code'], $slug);
    if ($existing) return (int)$existing['id'];

    q('INSERT INTO cities (country_code, region_id, name, slug) VALUES (?,?,?,?)',
      [$country['code'], $regionId, mb_substr(trim($cityName), 0, 140), $slug]);
    return (int)db()->lastInsertId();
}

/**
 * Read + validate the listing form. Returns [data, errors].
 *
 * $exceptId is the listing being edited, so its own website does not read as a
 * duplicate of itself. $enforce is false for staff forms — admins curate the
 * blocklist and must be able to edit a listing that trips it.
 */
function listing_form_data(array $user, array $plan, int $exceptId = 0, bool $enforce = true): array
{
    $errors = [];
    $name = mb_substr(post('name'), 0, 180);
    if ($name === '') $errors[] = 'Business name is required.';

    $catId = post('category_id');
    if ($catId === '' || !category_by_id($catId)) $errors[] = 'Please choose a category.';

    $cityId = resolve_city(strtoupper(post('country')), post('region'), post('city'));
    if (!$cityId) $errors[] = 'Please choose a valid country' . (strtoupper(post('country')) === 'US' ? ', state' : '') . ' and city.';

    // A length guide, not a guillotine: the description is cut at the end of the
    // sentence that crosses the limit, so what is stored always reads as
    // finished prose. See sentence_cap().
    $maxDesc = $plan['enhanced'] ? 5000 : 300;
    // What the business actually is, as Schema.org names it. Optional — a
    // listing with no type is marked up as a plain LocalBusiness, which is
    // true of all of them. An unrecognised value is dropped rather than
    // stored: it would go straight into @type, and a type search engines
    // cannot resolve is worse than the general one they can.
    $bizType = post('business_type');
    $data = [
        'name'          => $name,
        'category_id'   => $catId,
        'business_type' => schema_type_valid($bizType) ? $bizType : null,
        'city_id'       => $cityId,
        'tagline'     => mb_substr(post('tagline'), 0, 255),
        'description' => sentence_cap(post('description'), $maxDesc),
        'website'     => clean_url(post('website')),
        'address'     => mb_substr(post('address'), 0, 255),
        'founded'     => (int)post('founded') ?: null,
    ];
    // Everything below is a paid feature. The keys are left out entirely rather
    // than set to null, so callers can tell "the form did not offer this" from
    // "the member cleared it" and leave existing values alone on a downgrade.
    if ($plan['enhanced']) {
        $data['phone']     = mb_substr(post('phone'), 0, 40);
        $data['email']     = filter_var(post('email'), FILTER_VALIDATE_EMAIL) ?: null;
        $data['video_url'] = clean_url(post('video_url'));
        // Postcodes vary too much between countries to validate beyond a length
        // and a character set — "SW1A 1AA", "83702" and "75008" are all correct
        // and none of them looks like the others.
        $data['postcode']  = mb_substr(preg_replace('/[^A-Za-z0-9 \-]/', '', post('postcode')), 0, 20) ?: null;
        // Hours are written only when the form rendered the rows, so a form
        // without them cannot wipe a week somebody typed in.
        if (isset($_POST['hours_open']) || isset($_POST['hours_from'])) {
            $data['hours'] = hours_from_post();
        }
    }

    // The long-form Profile section, paid tiers only. Like the review links it
    // is written only when the form rendered the field, so a form without it
    // cannot wipe 1,500 words.
    if (!empty($plan['profile']) && array_key_exists('profile', $_POST)) {
        $data['profile'] = profile_cap(post('profile')) ?: null;
    }

    // Social and review links belong to every tier — the setup wizard collects
    // them from everyone. They are only written when the form actually rendered
    // the fields, though: writing them unconditionally would let a form that
    // omits them silently wipe what the wizard collected.
    $posted_any = function (array $keys, string $prefix): bool {
        foreach ($keys as $k) {
            if (array_key_exists($prefix . $k, $_POST)) return true;
        }
        return false;
    };
    $collect = function (array $keys, string $prefix): ?string {
        $out = [];
        foreach ($keys as $k) {
            $v = clean_url(post($prefix . $k));
            if ($v) $out[$k] = mb_substr($v, 0, 255);
        }
        return $out ? json_encode($out) : null;
    };

    $netKeys = array_keys(social_nets());
    if ($posted_any($netKeys, 'social_')) {
        $data['social'] = $collect($netKeys, 'social_');
    }
    $reviewKeys = array_keys(wizard_reviews());
    if ($posted_any($reviewKeys, 'review_')) {
        $data['review_links'] = $collect($reviewKeys, 'review_');
    }

    if ($enforce) {
        if (is_blocked_domain($data['website'])) {
            $errors[] = 'That website cannot be listed on this directory. If you believe this is a mistake, contact us.';
        } elseif ($dupe = listing_with_domain($data['website'], $exceptId)) {
            // Naming the existing listing turns a dead end into a claim, which
            // is what an owner whose business is already listed actually needs.
            $errors[] = domain_taken_message($dupe, (string)normalize_domain($data['website']));
        }
        if (is_blocked_email($data['email'] ?? null)) {
            $errors[] = 'That contact email cannot be used on this directory.';
        }
    }

    return [$data, $errors];
}

/**
 * Handle logo + gallery uploads and removals for a saved listing.
 *
 * Images are a paid feature — a free plan gets neither a logo nor a gallery, so
 * there is nothing here to do and an upload posted by hand is ignored.
 */
function handle_listing_images(int $bizId, array $plan, array &$errors): void
{
    if (!$plan['enhanced']) return;
    $biz = row('SELECT logo_url FROM businesses WHERE id = ?', [$bizId]);

    // logo
    if (!empty($_POST['remove_logo']) && $biz['logo_url']) {
        delete_upload($biz['logo_url']);
        q('UPDATE businesses SET logo_url = NULL WHERE id = ?', [$bizId]);
    }
    if (!empty($_FILES['logo']['name'])) {
        $url = save_image($_FILES['logo'], $bizId, 'logo', 400, $errors);
        if ($url) {
            delete_upload($biz['logo_url']);
            q('UPDATE businesses SET logo_url = ? WHERE id = ?', [$url, $bizId]);
        }
    }

    // gallery
    foreach ((array)($_POST['remove_gallery'] ?? []) as $gid) {
        $g = row('SELECT * FROM gallery WHERE id = ? AND business_id = ?', [(int)$gid, $bizId]);
        if ($g) { delete_upload($g['url']); q('DELETE FROM gallery WHERE id = ?', [$g['id']]); }
    }
    $have = (int)scalar('SELECT COUNT(*) FROM gallery WHERE business_id = ?', [$bizId]);
    $slots = max(0, 6 - $have);
    $files = array_slice(files_list('gallery'), 0, $slots);
    if (count(files_list('gallery')) > $slots) $errors[] = 'Gallery is limited to 6 photos — extra files were skipped.';
    foreach ($files as $i => $f) {
        $url = save_image($f, $bizId, 'gallery', 1600, $errors);
        if ($url) q('INSERT INTO gallery (business_id, url, sort) VALUES (?,?,?)', [$bizId, $url, $have + $i]);
    }
}

// ---------------------------------------------------------------------------
// MonsterScore — how well a listing is set up to be found, 0 to 100.
//
// Computed from the listing row itself so it is deterministic and explainable:
// a complete profile scores higher than a bare one, verification and real
// reviews count for trust, and a paid tier plus linked socials widen reach.
// Nothing here is random — the same listing always scores the same.
// ---------------------------------------------------------------------------
function monster_score(array $b): int
{
    // Completeness — is there enough on the page to rank and to convince? (45)
    $score = 0;
    if (!empty($b['logo_url']))  $score += 7;
    $desc = mb_strlen(trim((string)($b['description'] ?? '')));
    $score += $desc >= 140 ? 12 : ($desc >= 60 ? 7 : ($desc > 0 ? 3 : 0));
    if (!empty($b['tagline']))   $score += 5;
    if (!empty($b['phone']))     $score += 5;
    if (!empty($b['website']))   $score += 6;
    if (!empty($b['email']))     $score += 4;
    if (!empty($b['address']))   $score += 6;

    // Trust — verification and what customers have actually said. (35)
    if (!empty($b['verified']))  $score += 15;
    $reviews = (int)($b['review_count'] ?? 0);
    $score += min(10, $reviews);
    if ($reviews > 0) $score += (int)round((float)($b['rating'] ?? 0) / 5 * 10);

    // Reach — placement and linked channels. (20)
    $tier = $b['tier'] ?? 'free';
    $score += $tier === 'featured' ? 12 : ($tier === 'pro' ? 8 : 0);
    $social = json_decode((string)($b['social'] ?? ''), true);
    if (is_array($social) && array_filter($social)) $score += 8;

    return max(0, min(100, $score));
}

/** Band for colouring a score: strong | good | fair. */
function monster_score_band(int $score): string
{
    return $score >= 75 ? 'strong' : ($score >= 45 ? 'good' : 'fair');
}
