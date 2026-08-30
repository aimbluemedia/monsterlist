<?php
// Geo catch-all:
//   /{cc}                          country page
//   /{cc}/{region}                 region page   — cities in it
//   /{cc}/{city}                   city listings — a city with no region
//   /{cc}/{region}/{city}          city listings
//   /{cc}/{city}/{biz}             storefront
//   /{cc}/{region}/{city}/{biz}    storefront
//
// The same segment count means different things depending on what the second
// segment turns out to be, so each shape is resolved by lookup rather than by
// assuming the country is two- or three-tier. Regions are tried first: a
// country only has one because some city sits under it, and a region slug that
// also names a region-less city in the same country cannot happen, because such
// a region is never created (see app/lib/geo.php).
//
// $segments provided by index.php.

$country = country_by_slug($segments[0]);
if (!$country) not_found();

$cc     = strtolower($country['code']);
$site   = setting('site_name');
$n      = count($segments);

/**
 * The old, region-less path for a city that has since gained a region.
 * Sends the visitor — and the crawler holding the indexed URL — to where the
 * page now lives, permanently, rather than to a 404.
 */
function moved_city(array $country, string $slug, string $tail = ''): void
{
    $city = city_by_slug_any($country['code'], $slug);
    if (!$city || empty($city['region_id'])) not_found();
    redirect(city_path($city + ['country_code' => $country['code']]) . $tail, 301);
}

// ---------- helpers to render the three page types ----------

function render_city_page(array $country, ?array $region, array $city): void
{
    $site  = setting('site_name');
    $page  = page_param();
    $list  = listings_for_city((int)$city['id'], $page);
    $total = listings_count_city((int)$city['id']);
    $pages = max(1, (int)ceil($total / 20));
    if ($page > $pages) not_found();
    $path  = city_path($city + ['country_code' => $country['code'], 'region_slug' => $region['slug'] ?? null]);

    $crumbs = [['name' => 'Home', 'path' => '/'], ['name' => $country['name'], 'path' => '/' . strtolower($country['code'])]];
    if ($region) $crumbs[] = ['name' => $region['name'], 'path' => region_path($region + ['country_code' => $country['code']])];
    $crumbs[] = ['name' => $city['name'], 'path' => $path];

    $items = array_map(fn($b) => ['name' => $b['name'], 'path' => $path . '/' . $b['slug']], $list);

    // category chips → category×city landing pages
    $cityCats = rows(
        'SELECT c.id, c.label, c.icon, COUNT(*) cnt
         FROM businesses b JOIN categories c ON c.id = b.category_id
         WHERE b.city_id = ? AND b.status = "live"
         GROUP BY c.id ORDER BY cnt DESC, c.label', [$city['id']]);
    $catCityBase = '/category/%s/' . strtolower($country['code']) . ($region ? '/' . $region['slug'] : '') . '/' . $city['slug'];

    $where = $city['name'] . ($region ? ', ' . $region['name'] : '') . ', ' . $country['name'];
    $meta = [
        'title'       => "Local businesses in $where — $site" . ($page > 1 ? " (page $page)" : ''),
        'description' => "Discover $total trusted local businesses in $where. Reviews, contact details and websites — free on $site.",
        'canonical'   => site_url($path . ($page > 1 ? "?page=$page" : '')),
        'jsonld'      => [jsonld_breadcrumbs($crumbs), jsonld_itemlist($items)],
    ];
    view('city', compact('meta', 'country', 'region', 'city', 'list', 'total', 'page', 'pages', 'path', 'crumbs', 'cityCats', 'catCityBase'));
}

function render_storefront(array $country, ?array $region, array $city, string $bizSlug): void
{
    $b = business_in_city((int)$city['id'], $bizSlug);
    if (!$b) not_found();

    // Review submission (logged-in members only)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_rating'])) {
        csrf_check();
        $u = require_login();
        $rating = min(5, max(1, (int)$_POST['review_rating']));
        $body   = mb_substr(post('review_body'), 0, 2000);
        q('INSERT INTO reviews (business_id, user_id, author_name, rating, body) VALUES (?,?,?,?,?)',
          [$b['id'], $u['id'], $u['name'], $rating, $body]);
        refresh_rating((int)$b['id']);
        flash_set('success', 'Thanks — your review is live.');
        redirect($_SERVER['REQUEST_URI']);
    }

    track_event((int)$b['id'], 'view');
    // Read the totals AFTER recording this visit, so the number on the page
    // includes the load that is rendering it — a counter that lags by one looks
    // broken to the one person most likely to be watching it: the owner.
    $eventTotals = listing_event_totals((int)$b['id']);

    $cityFull = $city + [
        'country_code' => $country['code'],
        'region_code'  => $region['code'] ?? null,
        'region_name'  => $region['name'] ?? null,
    ];
    $cityPath = city_path($city + ['country_code' => $country['code'], 'region_slug' => $region['slug'] ?? null]);
    $path     = $cityPath . '/' . $b['slug'];

    $gallery  = rows('SELECT * FROM gallery  WHERE business_id = ? ORDER BY sort, id', [$b['id']]);
    $services = rows('SELECT * FROM services WHERE business_id = ? ORDER BY id', [$b['id']]);
    $products = rows('SELECT * FROM products WHERE business_id = ? ORDER BY id', [$b['id']]);
    $reviews  = rows('SELECT * FROM reviews  WHERE business_id = ? AND status = "live" ORDER BY created_at DESC LIMIT 30', [$b['id']]);

    $crumbs = [['name' => 'Home', 'path' => '/'], ['name' => $country['name'], 'path' => '/' . strtolower($country['code'])]];
    if ($region) $crumbs[] = ['name' => $region['name'], 'path' => region_path($region + ['country_code' => $country['code']])];
    $crumbs[] = ['name' => $city['name'], 'path' => $cityPath];
    $crumbs[] = ['name' => $b['name'], 'path' => $path];

    $site  = setting('site_name');
    $where = $city['name'] . ($region ? ', ' . $region['name'] : '') . ', ' . $country['name'];
    $meta = [
        'title'       => $b['name'] . " — " . ($b['category_label'] ?: 'Local business') . " in $where | $site",
        // The About text first — it is what the business actually says about
        // itself, and it is what a searcher is deciding on. Trimmed to whole
        // words inside the ~160 characters a result snippet shows. The tagline
        // is the fallback, and a generated line the last resort.
        'description' => meta_excerpt((string)$b['description'], 160)
            ?: $b['tagline']
            ?: "{$b['name']} in $where. Contact details, reviews and more on $site.",
        'canonical'   => site_url($path),
        'og'          => ['type' => 'business.business'],
        'jsonld'      => [jsonld_local_business($b, $cityFull, $path), jsonld_breadcrumbs($crumbs)],
    ];
    view('business', compact('meta', 'country', 'region', 'city', 'b', 'gallery', 'services', 'products', 'reviews', 'crumbs', 'path', 'eventTotals'));
}

// ---------- route by segment count ----------

if ($n === 1) {
    // Country page: the regions, and any cities that sit directly under the
    // country. Most countries show one or the other; a few show both.
    $regions = regions_of($country['code']);
    $cities  = cities_of_country($country['code']);
    $how = $regions ? (($country['code'] === 'US' ? 'state' : 'region') . ' and city') : 'city';
    $meta = [
        'title'       => "Small businesses in {$country['name']} — $site",
        'description' => "Browse local small businesses across {$country['name']} by $how on $site.",
        'canonical'   => site_url("/$cc"),
        'jsonld'      => [jsonld_breadcrumbs([
            ['name' => 'Home', 'path' => '/'],
            ['name' => $country['name'], 'path' => "/$cc"],
        ])],
    ];
    view('country', compact('meta', 'country', 'regions', 'cities'));

} elseif ($n === 2) {
    // /{cc}/{region} or /{cc}/{city}
    if ($region = region_by_slug($country['code'], $segments[1])) {
        $cities = cities_of_region((int)$region['id']);
        $path   = region_path($region);
        $meta = [
            'title'       => "Local businesses in {$region['name']} — $site",
            'description' => "Find small businesses across {$region['name']}: pick a city to see trusted local listings on $site.",
            'canonical'   => site_url($path),
            'jsonld'      => [jsonld_breadcrumbs([
                ['name' => 'Home', 'path' => '/'],
                ['name' => $country['name'], 'path' => "/$cc"],
                ['name' => $region['name'], 'path' => $path],
            ])],
        ];
        view('state', compact('meta', 'country', 'region', 'cities', 'path'));
    } elseif ($city = city_in_country($country['code'], $segments[1])) {
        render_city_page($country, null, $city);
    } else {
        moved_city($country, $segments[1]);
    }

} elseif ($n === 3) {
    // /{cc}/{region}/{city} or /{cc}/{city}/{biz}
    if ($region = region_by_slug($country['code'], $segments[1])) {
        $city = city_in_region((int)$region['id'], $segments[2]);
        if (!$city) not_found();
        render_city_page($country, $region, $city);
    } elseif ($city = city_in_country($country['code'], $segments[1])) {
        render_storefront($country, null, $city, $segments[2]);
    } else {
        moved_city($country, $segments[1], '/' . $segments[2]);
    }

} elseif ($n === 4) {
    // /{cc}/{region}/{city}/{biz}
    $region = region_by_slug($country['code'], $segments[1]);
    if (!$region) not_found();
    $city = city_in_region((int)$region['id'], $segments[2]);
    if (!$city) not_found();
    render_storefront($country, $region, $city, $segments[3]);

} else {
    not_found();
}
