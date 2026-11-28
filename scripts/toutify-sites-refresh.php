<?php
// ---------------------------------------------------------------------------
// scripts/toutify-sites-refresh — fill in which Toutify sites each listing's
// website is on.
//
//   php scripts/toutify-sites-refresh.php [--dry-run]
//
// NO COMMAND LINE? The same thing is a button on
// /superadmin/diagnostics — "Fetch which Toutify sites each listing is on".
// Both run toutify_sites_refresh() in app/lib/toutify.php, so they do exactly
// the same work and report the same numbers.
//
// A ONE-OFF. From here on website.joined and website.updated carry the same
// information and app/controllers/toutify.php saves it as it arrives, so this
// exists to catch up the listings linked before Toutify started sending it.
// Running it again is harmless — it writes the same values.
//
// It pages GET {issuer}/api/v1/sites/websites?limit=500&cursor=<next_cursor>
// with HTTP Basic until next_cursor comes back empty.
//
// A website Toutify returns that no listing here is linked to is counted and
// skipped: this script links nothing. scripts/toutify-listings does that.
// ---------------------------------------------------------------------------

if (PHP_SAPI !== 'cli') { http_response_code(404); exit("Not found\n"); }

require dirname(__DIR__) . '/app/bootstrap.php';

$dry = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') { $dry = true; continue; }
    fwrite(STDERR, "Unknown option: $arg\nUsage: php scripts/toutify-sites-refresh.php [--dry-run]\n");
    exit(2);
}

$linked = (int)scalar("SELECT COUNT(*) FROM businesses
                        WHERE toutify_website_id IS NOT NULL AND TRIM(toutify_website_id) <> ''");

echo ($dry ? "DRY RUN — nothing will be written.\n" : "")
   . 'Toutify: ' . toutify_issuer() . '  client: ' . toutify_client_id() . "\n"
   . "Listings linked to a Toutify website here: $linked\n\n";

$r = toutify_sites_refresh($dry);

if ($r['error'] !== '') { fwrite(STDERR, $r['error'] . "\n"); exit(1); }

echo str_repeat('-', 56) . "\n";
printf("  %-34s %d\n", 'pages fetched',                      $r['pages']);
printf("  %-34s %d\n", 'websites returned by Toutify',       $r['seen']);
printf("  %-34s %d\n", 'of those, carrying a sites list',    $r['with_sites']);
printf("  %-34s %d\n", 'of those, linked to a listing here', $r['matched']);
printf("  %-34s %d\n", $dry ? 'listings that WOULD be updated' : 'listings updated', $r['updated']);

if ($r['matched'] === 0 && $linked > 0) {
    echo "\n  Nothing matched. Toutify returned no website whose id is on a listing here —\n"
       . "  check that the ids in businesses.toutify_website_id came from this same\n"
       . "  Toutify account.\n";
}
if ($linked === 0) {
    echo "\n  No listing here carries a toutify_website_id, so there was nothing to match\n"
       . "  on. Run scripts/toutify-listings.php first — that is what sets them.\n";
}
if ($r['unknown']) {
    echo "\n  Service keys Toutify sent that no card here shows: " . implode(', ', $r['unknown']) . "\n"
       . "  They are stored as they came; add them to toutify_products() to show them.\n";
}
if ($r['stopped'] !== '') echo "\n  " . $r['stopped'] . "\n";
if ($dry)                 echo "\n  DRY RUN — nothing was written.\n";
