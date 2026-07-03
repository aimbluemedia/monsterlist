<?php
// Geography lookups. URL scheme:
//   /us                     country page (states)
//   /us/arizona             state page (cities)
//   /us/arizona/phoenix     city listings
//   /gb                     country page (cities — 2-tier)
//   /gb/london              city listings
// Plus /{...}/{business-slug} for storefronts.

function country_by_slug(string $slug): ?array
{
    if (!preg_match('/^[a-z]{2}$/', $slug)) return null;
    return row('SELECT * FROM countries WHERE code = ?', [strtoupper($slug)]);
}

function region_by_slug(string $countryCode, string $slug): ?array
{
    return row('SELECT * FROM regions WHERE country_code = ? AND slug = ?', [$countryCode, $slug]);
}

function city_in_region(int $regionId, string $slug): ?array
{
    return row('SELECT * FROM cities WHERE region_id = ? AND slug = ?', [$regionId, $slug]);
}

function city_in_country(string $countryCode, string $slug): ?array
{
    return row('SELECT * FROM cities WHERE country_code = ? AND region_id IS NULL AND slug = ?', [$countryCode, $slug]);
}

function regions_of(string $countryCode): array
{
    return rows('SELECT * FROM regions WHERE country_code = ? ORDER BY name', [$countryCode]);
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
