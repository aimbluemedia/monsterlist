<?php
// Category pages — the programmatic-SEO backbone:
//   /category/{cat}                       global
//   /category/{cat}/{cc}                  country level
//   /category/{cat}/us/{state}            US state level
//   /category/{cat}/{cc}/{city}           intl city level
//   /category/{cat}/us/{state}/{city}     US city level
// Pages with zero listings render but are noindexed (no thin-page penalty).

$cat = category_by_id($catSlug);
if (!$cat) not_found();

$site = setting('site_name');
$n    = count($segments);

// ---- resolve location scope ----
$country = null; $region = null; $city = null;
if ($n >= 3) {
    $country = country_by_slug($segments[2]);
    if (!$country) not_found();
    $isUS = $country['code'] === 'US';
    if ($n === 4) {
        if ($isUS) { $region = region_by_slug('US', $segments[3]); if (!$region) not_found(); }
        else       { $city = city_in_country($country['code'], $segments[3]); if (!$city) not_found(); }
    } elseif ($n === 5) {
        if (!$isUS) not_found();
        $region = region_by_slug('US', $segments[3]);
        if (!$region) not_found();
        $city = city_in_region((int)$region['id'], $segments[4]);
        if (!$city) not_found();
    }
}

// ---- canonical path + place label ----
$catBase = '/category/' . $cat['id'];
$locPath = $catBase;
$place   = null;
if ($country) {
    $locPath .= '/' . strtolower($country['code']);
    $place = $country['name'];
    if ($region)  { $locPath .= '/' . $region['slug']; $place = $region['name']; }
    if ($city)    { $locPath .= '/' . $city['slug'];   $place = $city['name'] . ($region ? ', ' . $region['code'] : ', ' . $country['name']); }
}

// ---- optional subcategory filter ----
// A query parameter rather than a path segment: the segments after the
// category are already spoken for by country, state and city, so
// /category/home/plumber would be indistinguishable from a country code.
// Checked against the live list, so an invented ?type= is ignored rather
// than quietly returning nothing.
$typeKey   = trim((string)($_GET['type'] ?? ''));
$typeLabel = $typeKey !== '' ? listing_type_label($typeKey) : '';
if ($typeKey !== '' && $typeLabel === '') { $typeKey = ''; }

// ---- listings query, scoped ----
$where  = ['b.category_id = ?', 'b.status = "live"'];
$params = [$cat['id']];
if ($typeKey !== '') { $where[] = 'b.business_type = ?'; $params[] = $typeKey; }
if ($city)        { $where[] = 'b.city_id = ?';        $params[] = $city['id']; }
elseif ($region)  { $where[] = 'ci.region_id = ?';     $params[] = $region['id']; }
elseif ($country) { $where[] = 'ci.country_code = ?';  $params[] = $country['code']; }
$whereSql = implode(' AND ', $where);

$page    = page_param();
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$total   = (int)scalar("SELECT COUNT(*) FROM businesses b JOIN cities ci ON ci.id = b.city_id WHERE $whereSql", $params);
$pages   = max(1, (int)ceil($total / $perPage));
if ($page > $pages) not_found();

$list = rows(
    "SELECT b.*, c.label AS category_label, c.icon AS category_icon,
            ci.name AS city_name, ci.slug AS city_slug, ci.country_code, ci.region_id,
            r.slug AS region_slug, r.name AS region_name, co.name AS country_name
     FROM businesses b
     LEFT JOIN categories c ON c.id = b.category_id
     JOIN cities ci ON ci.id = b.city_id
     JOIN countries co ON co.code = ci.country_code
     LEFT JOIN regions r ON r.id = ci.region_id
     WHERE $whereSql
     ORDER BY " . TIER_ORDER . ", b.rating DESC
     LIMIT $perPage OFFSET $offset", $params);

// ---- internal links: same category nearby + other categories here ----
$nearby = [];
if ($city) {
    // other cities (same region/country) where this category has live listings
    $scopeSql    = $region ? 'ci.region_id = ?' : 'ci.country_code = ? AND ci.region_id IS NULL';
    $scopeParam  = $region ? $region['id'] : $country['code'];
    $nearby = rows(
        "SELECT ci.name, ci.slug, ci.country_code, ci.region_id, r.slug AS region_slug, COUNT(*) cnt
         FROM businesses b JOIN cities ci ON ci.id = b.city_id LEFT JOIN regions r ON r.id = ci.region_id
         WHERE b.category_id = ? AND b.status = 'live' AND $scopeSql AND ci.id != ?
         GROUP BY ci.id ORDER BY cnt DESC, ci.name LIMIT 12",
        [$cat['id'], $scopeParam, $city['id']]);
}
$otherCats = $city ? rows(
    "SELECT c.id, c.label, c.icon, COUNT(*) cnt
     FROM businesses b JOIN categories c ON c.id = b.category_id
     WHERE b.city_id = ? AND b.status = 'live' AND c.id != ?
     GROUP BY c.id ORDER BY cnt DESC LIMIT 12", [$city['id'], $cat['id']]) : [];

// ---- unique intro copy (deterministic variation per cat+place) ----
$catLower = strtolower($cat['label']);
$in       = $place ? "in $place" : 'worldwide';
$v        = crc32($cat['id'] . '|' . ($place ?? 'global'));
$openers = [
    "Looking for trusted $catLower $in? You're in the right place.",
    "Compare the best $catLower options $in — reviewed and verified.",
    "Find reliable $catLower $in without the guesswork.",
    "Your shortlist for $catLower $in starts here.",
];
$middles = [
    "Every business on $site is checked by a real person before it goes live, so you only see legitimate local providers.",
    "All $site listings pass human moderation before publication — no scraped junk, no ghost businesses.",
    "Each listing here was reviewed by our moderation team, and verified members carry the green badge.",
];
$closers = $total > 0
    ? ["Browse all $total listing" . ($total === 1 ? '' : 's') . " below, compare ratings from real customers, and contact businesses directly — no middleman, no fees.",
       "There " . ($total === 1 ? "is 1 provider" : "are $total providers") . " listed below. Check their ratings, storefronts and contact details, then reach out directly."]
    : ["No providers are listed here yet — if you run a $catLower business $in, a free listing takes five minutes and puts you in front of customers searching right now."];
$intro = $openers[$v % 4] . ' ' . $middles[$v % 3] . ' ' . $closers[$v % count($closers)];

// ---- FAQ (rendered + JSON-LD) ----
$placeOr = $place ?? 'your area';
$faq = [
    ["How do I choose the best " . $catLower . " provider in $placeOr?",
     "Compare ratings and reviews from real customers, look for the Verified badge (identity-checked businesses), and check each storefront for photos, services and pricing before you make contact."],
    ["Are these $catLower businesses verified?",
     "Every listing on $site is human-moderated before it goes live. Businesses marked Verified have additionally confirmed their identity with our team."],
    ["How much does it cost to contact a business on $site?",
     "Nothing. $site is free for visitors — you contact businesses directly by phone, email or their website, with no middleman or referral fees."],
];
if ($total > 0) {
    $faq[] = ["How many $catLower providers are listed in $placeOr?",
              "There " . ($total === 1 ? "is currently 1 $catLower provider" : "are currently $total $catLower providers") . " listed in $placeOr on $site, and new businesses are added after moderation every week."];
}

// The other trades on this shelf, so a filtered page offers the siblings and
// an unfiltered one offers the way in. Empty when subcategories are not
// installed, which is what keeps this working before the upgrade SQL is run.
$subcats = [];
foreach ((schema_types()[$cat['id']] ?? []) as $k => $label) $subcats[$k] = $label;

// ---- breadcrumbs + meta ----
$crumbs = [['name' => 'Home', 'path' => '/'], ['name' => $cat['label'], 'path' => $catBase]];
if ($country) $crumbs[] = ['name' => $country['name'], 'path' => "$catBase/" . strtolower($country['code'])];
if ($region)  $crumbs[] = ['name' => $region['name'], 'path' => "$catBase/us/{$region['slug']}"];
if ($city)    $crumbs[] = ['name' => $city['name'], 'path' => $locPath];

$titlePlace = $place ? " in $place" : '';
// A subcategory page is its own page: the heading, the title and the canonical
// all name it, so it does not read as a duplicate of the category above it.
$titleWhat  = $typeLabel !== '' ? $typeLabel : $cat['label'];
$typeQs     = $typeKey !== '' ? 'type=' . rawurlencode($typeKey) : '';
$meta = [
    'title'       => $titleWhat . $titlePlace . " — " . ($total > 0 ? "$total trusted listing" . ($total === 1 ? '' : 's') : 'find local businesses') . " | $site" . ($page > 1 ? " (page $page)" : ''),
    'description' => "Find trusted " . mb_strtolower($titleWhat) . " businesses$titlePlace. " . ($total > 0 ? "$total verified listing" . ($total === 1 ? '' : 's') . " with reviews, ratings and direct contact details" : "Human-moderated local listings with reviews and direct contact details") . " on $site.",
    'canonical'   => site_url($locPath . (($typeQs || $page > 1)
                        ? '?' . implode('&', array_filter([$typeQs, $page > 1 ? "page=$page" : ''])) : '')),
    'robots'      => $total === 0 ? 'noindex, follow' : null,
    'jsonld'      => [
        jsonld_breadcrumbs($crumbs),
        jsonld_itemlist(array_map(fn($b) => ['name' => $b['name'], 'path' => business_path($b)], $list)),
        jsonld_faq($faq),
    ],
];
$meta = array_filter($meta, fn($x) => $x !== null);

view('category', compact('meta', 'cat', 'list', 'total', 'page', 'pages',
    'country', 'region', 'city', 'place', 'locPath', 'crumbs', 'intro', 'faq', 'nearby', 'otherCats',
    'typeKey', 'typeLabel', 'subcats'));
