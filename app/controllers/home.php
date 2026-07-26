<?php
// Homepage: hero search, categories, featured + new members, top locations.

$cats      = categories_all();
$featured  = featured_businesses(6);
$newest    = newest_businesses(6);
$popCities = popular_cities(12);
$popStates = popular_states();
$popCtries = popular_countries();

$stats = [
    'listings'  => (int)scalar('SELECT COUNT(*) FROM businesses WHERE status = "live"'),
    'countries' => (int)scalar('SELECT COUNT(*) FROM countries'),
    'categories'=> count($cats),
];

$planList = plans();
$site = setting('site_name');
$meta = [
    'title'       => "$site — free AI-powered promotion for your small business",
    'description' => "Join $site free and let AI build your business page in seconds. Get found on Google, ChatGPT, Claude and Perplexity — plus reviews, photos, social links and analytics. 100% free to join.",
    'canonical'   => site_url('/'),
    'jsonld'      => [jsonld_website(), jsonld_organization()],
];

view('home', compact('meta', 'cats', 'featured', 'newest', 'popCities', 'popStates', 'popCtries', 'stats', 'planList'));
