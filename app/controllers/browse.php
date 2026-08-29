<?php
// /browse — server-rendered drill-down explorer (SEO-crawlable, JS only filters).

$countries = all_countries();
$cats      = categories_all();

// The trades under each category. schema_types() is the live list when the
// subcategory table is installed and the built-in one before that, so this
// page fills in either way rather than showing sixteen bare headings.
$subs = [];
foreach ($cats as $c) $subs[$c['id']] = schema_types()[$c['id']] ?? [];

$meta = [
    'title'       => 'Browse the directory — ' . setting('site_name'),
    'description' => 'Browse small businesses by country, state and city, or jump straight to a category or trade. '
                     . count($cats) . ' categories, ' . array_sum(array_map('count', $subs))
                     . ' trades and ' . count($countries) . ' countries covered.',
    'canonical'   => site_url('/browse'),
    'jsonld'      => [jsonld_breadcrumbs([
        ['name' => 'Home', 'path' => '/'],
        ['name' => 'Browse', 'path' => '/browse'],
    ])],
];

view('browse', compact('meta', 'countries', 'cats', 'subs'));
