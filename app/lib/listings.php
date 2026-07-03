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
