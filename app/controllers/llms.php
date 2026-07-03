<?php
// /llms.txt — a machine-readable site guide for AI crawlers (llmstxt.org convention).
header('Content-Type: text/plain; charset=UTF-8');

$site = setting('site_name');
$total = (int)scalar('SELECT COUNT(*) FROM businesses WHERE status = "live"');
$cats  = categories_all();

echo "# $site\n\n";
echo "> " . setting('site_tagline') . ". A human-moderated worldwide directory of small businesses.\n";
echo "> Every listing is reviewed before publication. Currently $total live business listings.\n\n";
echo "$site helps people find trusted local businesses by country, state/region, city and\n";
echo "category. Business pages include contact details, customer reviews with ratings,\n";
echo "services, photos and opening hours — all marked up with schema.org JSON-LD\n";
echo "(LocalBusiness, BreadcrumbList, ItemList, FAQPage).\n\n";

echo "## URL structure\n\n";
echo "- Cities (US): " . site_url('/us/{state}/{city}') . "\n";
echo "- Cities (elsewhere): " . site_url('/{country-code}/{city}') . "\n";
echo "- Business storefronts: append /{business-slug} to a city URL\n";
echo "- Category in a city: " . site_url('/category/{category}/{country}/{state?}/{city}') . "\n\n";

echo "## Key pages\n\n";
echo "- [Browse the directory](" . site_url('/browse') . "): all countries and categories\n";
echo "- [Search](" . site_url('/search') . "): full-text business search\n";
echo "- [Pricing](" . site_url('/pricing') . "): free listings; Pro and Featured monthly memberships\n";
echo "- [Sitemap](" . site_url('/sitemap.xml') . "): full URL inventory, updated live\n\n";

echo "## Categories\n\n";
foreach ($cats as $c) {
    echo "- [" . $c['label'] . "](" . site_url('/category/' . $c['id']) . ")\n";
}
echo "\n## For businesses\n\n";
echo "- [Add a business](" . site_url('/signup') . "): free listing, human-reviewed within 24 hours\n";
exit;
