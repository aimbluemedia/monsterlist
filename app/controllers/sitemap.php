<?php
// Dynamic sitemaps:
//   /sitemap.xml            → index pointing to the parts below
//   /sitemap-static.xml     → home, browse, pricing, categories, countries
//   /sitemap-geo-N.xml      → states + cities (10k URLs per part)
//   /sitemap-biz-N.xml      → live business storefronts (10k per part)
header('Content-Type: application/xml; charset=UTF-8');

const SM_CHUNK = 10000;

function sm_url(string $loc, string $freq = 'weekly', string $prio = '0.5'): string
{
    return "<url><loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc><changefreq>$freq</changefreq><priority>$prio</priority></url>";
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

if ($path === '/sitemap.xml') {
    $geoCount = (int)scalar('SELECT COUNT(*) FROM cities') + (int)scalar('SELECT COUNT(*) FROM regions');
    $bizCount = (int)scalar('SELECT COUNT(*) FROM businesses WHERE status = "live"');
    $catGeo   = (int)scalar('SELECT COUNT(DISTINCT category_id, city_id) FROM businesses WHERE status = "live"');
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    echo '<sitemap><loc>' . site_url('/sitemap-static.xml') . '</loc></sitemap>';
    for ($i = 1; $i <= max(1, ceil($geoCount / SM_CHUNK)); $i++) {
        echo '<sitemap><loc>' . site_url("/sitemap-geo-$i.xml") . '</loc></sitemap>';
    }
    for ($i = 1; $i <= max(1, ceil($bizCount / SM_CHUNK)); $i++) {
        echo '<sitemap><loc>' . site_url("/sitemap-biz-$i.xml") . '</loc></sitemap>';
    }
    for ($i = 1; $i <= max(1, ceil($catGeo / SM_CHUNK)); $i++) {
        echo '<sitemap><loc>' . site_url("/sitemap-catgeo-$i.xml") . '</loc></sitemap>';
    }
    echo '</sitemapindex>';
    exit;
}

echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

if ($path === '/sitemap-static.xml') {
    echo sm_url(site_url('/'), 'daily', '1.0');
    echo sm_url(site_url('/browse'), 'weekly', '0.8');
    echo sm_url(site_url('/pricing'), 'monthly', '0.7');
    echo sm_url(site_url('/about'), 'monthly', '0.4');
    foreach (rows('SELECT id FROM categories') as $c) {
        echo sm_url(site_url('/category/' . $c['id']), 'daily', '0.7');
    }
    foreach (rows('SELECT code FROM countries') as $c) {
        echo sm_url(site_url('/' . strtolower($c['code'])), 'weekly', '0.6');
    }

} elseif (preg_match('#^/sitemap-geo-(\d+)\.xml$#', $path, $m)) {
    $offset = ((int)$m[1] - 1) * SM_CHUNK;
    // regions first, then cities — stable order for stable pagination
    $items = rows(
        "(SELECT CONCAT('/', LOWER(country_code), '/', slug) AS p, 1 AS ord, id FROM regions)
         UNION ALL
         (SELECT CONCAT('/', LOWER(ci.country_code), IF(ci.region_id IS NULL, '', CONCAT('/', r.slug)), '/', ci.slug) AS p, 2 AS ord, ci.id
          FROM cities ci LEFT JOIN regions r ON r.id = ci.region_id)
         ORDER BY ord, id LIMIT " . SM_CHUNK . " OFFSET $offset");
    foreach ($items as $it) echo sm_url(site_url($it['p']), 'weekly', '0.6');

} elseif (preg_match('#^/sitemap-biz-(\d+)\.xml$#', $path, $m)) {
    $offset = ((int)$m[1] - 1) * SM_CHUNK;
    $items = rows(
        "SELECT CONCAT('/', LOWER(ci.country_code), IF(ci.region_id IS NULL, '', CONCAT('/', r.slug)), '/', ci.slug, '/', b.slug) AS p
         FROM businesses b
         JOIN cities ci ON ci.id = b.city_id
         LEFT JOIN regions r ON r.id = ci.region_id
         WHERE b.status = 'live'
         ORDER BY b.id LIMIT " . SM_CHUNK . " OFFSET $offset");
    foreach ($items as $it) echo sm_url(site_url($it['p']), 'weekly', '0.7');

} elseif (preg_match('#^/sitemap-catgeo-(\d+)\.xml$#', $path, $m)) {
    // category×city pages — only combos that actually have live listings
    $offset = ((int)$m[1] - 1) * SM_CHUNK;
    $items = rows(
        "SELECT DISTINCT CONCAT('/category/', b.category_id, '/', LOWER(ci.country_code),
                IF(ci.region_id IS NULL, '', CONCAT('/', r.slug)), '/', ci.slug) AS p
         FROM businesses b
         JOIN cities ci ON ci.id = b.city_id
         LEFT JOIN regions r ON r.id = ci.region_id
         WHERE b.status = 'live' AND b.category_id IS NOT NULL
         ORDER BY p LIMIT " . SM_CHUNK . " OFFSET $offset");
    foreach ($items as $it) echo sm_url(site_url($it['p']), 'daily', '0.8');
}

echo '</urlset>';
exit;
