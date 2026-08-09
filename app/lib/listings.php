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

    $maxDesc = $plan['enhanced'] ? 5000 : 300;
    $data = [
        'name'        => $name,
        'category_id' => $catId,
        'city_id'     => $cityId,
        'tagline'     => mb_substr(post('tagline'), 0, 255),
        'description' => mb_substr(post('description'), 0, $maxDesc),
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
        $social = [];
        foreach (array_keys(social_nets()) as $net) {
            $v = clean_url(post('social_' . $net));
            if ($v) $social[$net] = $v;
        }
        $data['social'] = $social ? json_encode($social) : null;
    }

    // Review-profile links are only set when the form actually rendered those
    // fields. Writing them unconditionally would let a form that omits them
    // (the member listing form) silently wipe what the setup wizard collected.
    $reviewKeys = array_keys(wizard_reviews());
    $hasReviewFields = false;
    foreach ($reviewKeys as $site) {
        if (array_key_exists('review_' . $site, $_POST)) { $hasReviewFields = true; break; }
    }
    if ($hasReviewFields) {
        $reviews = [];
        foreach ($reviewKeys as $site) {
            $v = clean_url(post('review_' . $site));
            if ($v) $reviews[$site] = $v;
        }
        $data['review_links'] = $reviews ? json_encode($reviews) : null;
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
