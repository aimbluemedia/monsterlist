<?php
// Geography lookups. URL scheme:
//   /us                     country page (states, plus any region-less cities)
//   /us/arizona             region page (cities)
//   /us/arizona/phoenix     city listings
//   /de/berlin              city listings — a city with no region above it
// Plus /{...}/{business-slug} for storefronts.
//
// The region tier is per-city, not per-country: a country may hold some cities
// under a region and others directly, and both shapes are addressable at the
// same time. That is deliberate. A region that would only ever contain the
// single city it is named after — Berlin, Tokyo, Dubai — earns nothing but a
// repeated path segment, so those cities keep the short URL, while Munich sits
// under Bavaria where the extra level says something a searcher cares about.
// Routing therefore asks the database what exists rather than assuming a
// country is two-tier or three-tier.

function country_by_slug(string $slug): ?array
{
    if (!preg_match('/^[a-z]{2}$/', $slug)) return null;
    return row('SELECT * FROM countries WHERE code = ?', [strtoupper($slug)]);
}

function region_by_slug(string $countryCode, string $slug): ?array
{
    return row('SELECT * FROM regions WHERE country_code = ? AND slug = ?', [$countryCode, $slug]);
}

function region_by_id(int $id): ?array
{
    return row('SELECT * FROM regions WHERE id = ?', [$id]);
}

function city_in_region(int $regionId, string $slug): ?array
{
    return row('SELECT * FROM cities WHERE region_id = ? AND slug = ?', [$regionId, $slug]);
}

function city_in_country(string $countryCode, string $slug): ?array
{
    return row('SELECT * FROM cities WHERE country_code = ? AND region_id IS NULL AND slug = ?', [$countryCode, $slug]);
}

/**
 * A city by slug anywhere in the country, region or not.
 *
 * Only for working out where an old URL should now point: a city that gains a
 * region moves from /{cc}/{city} to /{cc}/{region}/{city}, and the old path
 * has to keep resolving — to a redirect — or every link and indexed result
 * pointing at it turns into a 404.
 */
function city_by_slug_any(string $countryCode, string $slug): ?array
{
    return row(
        'SELECT ci.*, r.slug AS region_slug
           FROM cities ci LEFT JOIN regions r ON r.id = ci.region_id
          WHERE ci.country_code = ? AND ci.slug = ?
          ORDER BY ci.listing_count DESC, ci.id LIMIT 1', [$countryCode, $slug]);
}

function regions_of(string $countryCode): array
{
    return rows('SELECT * FROM regions WHERE country_code = ? ORDER BY name', [$countryCode]);
}

/** Every region, keyed by country code — for the region picker on listing forms. */
function regions_by_country(): array
{
    $out = [];
    foreach (rows('SELECT * FROM regions ORDER BY country_code, name') as $r) {
        $out[$r['country_code']][] = $r;
    }
    return $out;
}

/** Does this country have any region tier at all? Cached: called per request per country. */
function country_has_regions(string $countryCode): bool
{
    static $seen = [];
    if (!array_key_exists($countryCode, $seen)) {
        $seen[$countryCode] = (int)scalar('SELECT COUNT(*) FROM regions WHERE country_code = ?', [$countryCode]) > 0;
    }
    return $seen[$countryCode];
}

/** Canonical path for a region row. */
function region_path(array $region): string
{
    return '/' . strtolower($region['country_code']) . '/' . $region['slug'];
}

function cities_of_region(int $regionId): array
{
    return rows('SELECT * FROM cities WHERE region_id = ? ORDER BY name', [$regionId]);
}

function cities_of_country(string $countryCode): array
{
    return rows('SELECT * FROM cities WHERE country_code = ? AND region_id IS NULL ORDER BY name', [$countryCode]);
}

function popular_countries(): array
{
    return rows('SELECT * FROM countries WHERE is_popular = 1 ORDER BY popularity DESC, name');
}

function all_countries(): array
{
    return rows('SELECT * FROM countries ORDER BY name');
}

function popular_states(): array
{
    return rows('SELECT * FROM regions WHERE country_code = "US" AND is_popular = 1 ORDER BY name');
}

function popular_cities(int $limit = 12): array
{
    return rows(
        'SELECT ci.*, co.name AS country_name, co.flag, r.name AS region_name, r.slug AS region_slug, co.code AS ccode
         FROM cities ci
         JOIN countries co ON co.code = ci.country_code
         LEFT JOIN regions r ON r.id = ci.region_id
         WHERE ci.is_popular = 1
         ORDER BY ci.listing_count DESC, ci.name
         LIMIT ' . (int)$limit
    );
}

/** Canonical path for a city row (joined or not). */
function city_path(array $city): string
{
    $cc = strtolower($city['country_code'] ?? $city['ccode']);
    if (!empty($city['region_id'])) {
        $regionSlug = $city['region_slug'] ?? scalar('SELECT slug FROM regions WHERE id = ?', [$city['region_id']]);
        return "/$cc/$regionSlug/{$city['slug']}";
    }
    return "/$cc/{$city['slug']}";
}

/** City with country + region names joined, by id. */
function city_full(int $cityId): ?array
{
    return row(
        'SELECT ci.*, co.name AS country_name, co.flag, co.code AS ccode,
                r.name AS region_name, r.slug AS region_slug, r.code AS region_code
         FROM cities ci
         JOIN countries co ON co.code = ci.country_code
         LEFT JOIN regions r ON r.id = ci.region_id
         WHERE ci.id = ?', [$cityId]
    );
}

function categories_all(): array
{
    return rows('SELECT * FROM categories ORDER BY popularity DESC, label');
}

function category_by_id(string $id): ?array
{
    return row('SELECT * FROM categories WHERE id = ?', [$id]);
}
