<?php
// Geo catch-all:
//   /{country}                          country page
//   /us/{state}   |  /{cc}/{city}       state page | city listings
//   /us/{state}/{city} | /{cc}/{city}/{biz}   city listings | storefront
//   /us/{state}/{city}/{biz}            storefront
// $segments provided by index.php.

$country = country_by_slug($segments[0]);
if (!$country) not_found();

$cc     = strtolower($country['code']);
$isUS   = $country['code'] === 'US';
$site   = setting('site_name');
$n      = count($segments);

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
    if ($region) $crumbs[] = ['name' => $region['name'], 'path' => '/us/' . $region['slug']];
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
    if ($region) $crumbs[] = ['name' => $region['name'], 'path' => '/us/' . $region['slug']];
    $crumbs[] = ['name' => $city['name'], 'path' => $cityPath];
    $crumbs[] = ['name' => $b['name'], 'path' => $path];

    $site  = setting('site_name');
    $where = $city['name'] . ($region ? ', ' . $region['name'] : '') . ', ' . $country['name'];
    $meta = [
        'title'       => $b['name'] . " — " . ($b['category_label'] ?: 'Local business') . " in $where | $site",
        'description' => $b['tagline'] ?: mb_substr((string)$b['description'], 0, 160) ?: "{$b['name']} in $where. Contact details, reviews and more on $site.",
        'canonical'   => site_url($path),
        'og'          => ['type' => 'business.business'],
        'jsonld'      => [jsonld_local_business($b, $cityFull, $path), jsonld_breadcrumbs($crumbs)],
    ];
    view('business', compact('meta', 'country', 'region', 'city', 'b', 'gallery', 'services', 'products', 'reviews', 'crumbs', 'path'));
}

// ---------- route by segment count ----------

if ($n === 1) {
    // Country page
    $regions = $isUS ? regions_of('US') : [];
    $cities  = $isUS ? [] : cities_of_country($country['code']);
    $meta = [
        'title'       => "Small businesses in {$country['name']} — $site",
        'description' => "Browse local small businesses across {$country['name']} by " . ($isUS ? 'state and city' : 'city') . " on $site.",
        'canonical'   => site_url("/$cc"),
        'jsonld'      => [jsonld_breadcrumbs([
            ['name' => 'Home', 'path' => '/'],
            ['name' => $country['name'], 'path' => "/$cc"],
        ])],
    ];
    view('country', compact('meta', 'country', 'regions', 'cities', 'isUS'));

} elseif ($n === 2) {
    if ($isUS) {
        $region = region_by_slug('US', $segments[1]);
        if (!$region) not_found();
        $cities = cities_of_region((int)$region['id']);
        $meta = [
            'title'       => "Local businesses in {$region['name']} — $site",
            'description' => "Find small businesses across {$region['name']}: pick a city to see trusted local listings on $site.",
            'canonical'   => site_url("/us/{$region['slug']}"),
            'jsonld'      => [jsonld_breadcrumbs([
                ['name' => 'Home', 'path' => '/'],
                ['name' => 'United States', 'path' => '/us'],
                ['name' => $region['name'], 'path' => "/us/{$region['slug']}"],
            ])],
        ];
        view('state', compact('meta', 'country', 'region', 'cities'));
    } else {
        $city = city_in_country($country['code'], $segments[1]);
        if (!$city) not_found();
        render_city_page($country, null, $city);
    }

} elseif ($n === 3) {
    if ($isUS) {
        $region = region_by_slug('US', $segments[1]);
        if (!$region) not_found();
        $city = city_in_region((int)$region['id'], $segments[2]);
        if (!$city) not_found();
        render_city_page($country, $region, $city);
    } else {
        $city = city_in_country($country['code'], $segments[1]);
        if (!$city) not_found();
        render_storefront($country, null, $city, $segments[2]);
    }

} elseif ($n === 4 && $isUS) {
    $region = region_by_slug('US', $segments[1]);
    if (!$region) not_found();
    $city = city_in_region((int)$region['id'], $segments[2]);
    if (!$city) not_found();
    render_storefront($country, $region, $city, $segments[3]);

} else {
    not_found();
}
