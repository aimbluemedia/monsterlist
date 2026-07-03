<?php
// /category/{cat} — category landing page (SEO: one indexable page per category).

$cat = category_by_id($catSlug);
if (!$cat) not_found();

$page  = page_param();
$list  = listings_for_category($cat['id'], $page);
$total = listings_count_category($cat['id']);
$pages = max(1, (int)ceil($total / 20));
if ($page > $pages) not_found();

$site = setting('site_name');
$meta = [
    'title'       => $cat['label'] . " businesses — $site" . ($page > 1 ? " (page $page)" : ''),
    'description' => "Find trusted " . strtolower($cat['label']) . " businesses worldwide. $total verified listings with reviews and contact details on $site.",
    'canonical'   => site_url('/category/' . $cat['id'] . ($page > 1 ? "?page=$page" : '')),
    'jsonld'      => [
        jsonld_breadcrumbs([
            ['name' => 'Home', 'path' => '/'],
            ['name' => 'Browse', 'path' => '/browse'],
            ['name' => $cat['label'], 'path' => '/category/' . $cat['id']],
        ]),
        jsonld_itemlist(array_map(fn($b) => ['name' => $b['name'], 'path' => business_path($b)], $list)),
    ],
];

view('category', compact('meta', 'cat', 'list', 'total', 'page', 'pages'));
