<?php
// Homepage: hero search, categories, featured + new members, top locations.

$cats      = categories_all();
$featured  = featured_businesses(6);
$newest    = newest_businesses(6);
$popCities = popular_cities(12);
$popStates = popular_states();
$popCtries = popular_countries();

$meta = [
    'title'       => setting('site_name') . ' — ' . setting('site_tagline'),
    'description' => 'Find trusted local small businesses worldwide. Browse by country, state and city — or search thousands of verified listings on ' . setting('site_name') . '.',
    'canonical'   => site_url('/'),
    'jsonld'      => [jsonld_website(), jsonld_organization()],
];

view('home', compact('meta', 'cats', 'featured', 'newest', 'popCities', 'popStates', 'popCtries'));
