<?php
// /browse — server-rendered drill-down explorer (SEO-crawlable, JS only filters).

$countries = all_countries();
$cats      = categories_all();

$meta = [
    'title'       => 'Browse the directory — ' . setting('site_name'),
    'description' => 'Browse small businesses by country, state and city, or jump straight to a category. ' . count($countries) . ' countries covered.',
    'canonical'   => site_url('/browse'),
    'jsonld'      => [jsonld_breadcrumbs([
        ['name' => 'Home', 'path' => '/'],
        ['name' => 'Browse', 'path' => '/browse'],
    ])],
];

view('browse', compact('meta', 'countries', 'cats'));
