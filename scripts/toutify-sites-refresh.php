<?php
// ---------------------------------------------------------------------------
// scripts/toutify-sites-refresh — fill in which Toutify sites each listing's
// website is on.
//
//   php scripts/toutify-sites-refresh.php [--dry-run]
//
// A ONE-OFF. From here on, website.joined and website.updated carry the same
// information and app/controllers/toutify.php saves it as it arrives, so this
// exists to catch up the listings that were linked before Toutify started
// sending it. Running it again is harmless — it writes the same values.
//
// It pages through
//     GET {issuer}/api/v1/sites/websites?limit=500&cursor=<next_cursor>
// with HTTP Basic (the client id and secret from app/config.php) until
// next_cursor comes back empty, and saves each website's sites onto every
// listing carrying that toutify_website_id.
//
// A website Toutify returns that no listing here is linked to is counted and
// skipped: this script links nothing. scripts/toutify-listings does that.
// ---------------------------------------------------------------------------

if (PHP_SAPI !== 'cli') { http_response_code(404); exit("Not found\n"); }

$ML_WEB_ROOT = dirname(__DIR__);
require $ML_WEB_ROOT . '/app/bootstrap.php';

const TSR_PAGE = 500;
// A runaway cursor — one that never empties, or that Toutify hands back
// unchanged — would otherwise spin for ever against a live API.
const TSR_MAX_PAGES = 1000;

$dry = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') { $dry = true; continue; }
    fwrite(STDERR, "Unknown option: $arg\nUsage: php scripts/toutify-sites-refresh.php [--dry-run]\n");
    exit(2);
}

if (toutify_client_secret() === '') {
    fwrite(STDERR, "No Toutify client secret in app/config.php.\n");
    exit(1);
}
if (!column_exists('businesses', 'toutify_sites')) {
    fwrite(STDERR, "The database has no businesses.toutify_sites yet. "
                 . "Run database/upgrade-hostinger.sql first.\n");
    exit(1);
}

$linked = (int)scalar("SELECT COUNT(*) FROM businesses
                        WHERE toutify_website_id IS NOT NULL AND TRIM(toutify_website_id) <> ''");

echo ($dry ? "DRY RUN — nothing will be written.\n" : "")
   . "Toutify: " . toutify_issuer() . "  client: " . toutify_client_id() . "\n"
   . "Listings linked to a Toutify website here: $linked\n\n";

$pages = 0;
$seen = 0;          // websites Toutify returned
$withSites = 0;     // ...of those, ones carrying a "sites" array
$matched = 0;       // ...of those, ones some listing here is linked to
$updated = 0;       // listings written (or that would be written)
$unknown = [];      // service keys Toutify sent that no card here shows
$cursor = '';

while (true) {
    $url = toutify_issuer() . '/api/v1/sites/websites?limit=' . TSR_PAGE
         . ($cursor !== '' ? '&cursor=' . rawurlencode($cursor) : '');

    [$code, $body, $err] = toutify_http('GET', $url, null, [toutify_basic_header()], 3);
    if ($code !== 200 || !is_array($body)) {
        fwrite(STDERR, "Request failed (HTTP $code) "
             . ($err ?: (is_string($body) ? substr($body, 0, 300) : '')) . "\n");
        exit(1);
    }

    // The array's key is not documented, so the four plausible spellings are
    // all accepted. Guessing wrong would otherwise report a clean run of zero.
    $list = null;
    foreach (['websites', 'data', 'results', 'items'] as $k) {
        if (isset($body[$k]) && is_array($body[$k])) { $list = $body[$k]; break; }
    }
    if ($list === null) {
        fwrite(STDERR, "No websites array in the response. Keys: "
             . implode(', ', array_keys($body)) . "\n");
        exit(1);
    }

    foreach ($list as $w) {
        if (!is_array($w)) continue;
        $seen++;
        $wid = trim((string)($w['id'] ?? ''));
        if ($wid === '') continue;

        // Null means this website came back without a "sites" array at all,
        // which is "nobody said" and must not blank what is stored.
        $sites = toutify_sites_from_website($w);
        if ($sites === null) continue;
        $withSites++;

        foreach ($sites as $k) {
            if (!in_array($k, toutify_tool_keys(), true) && $k !== TOUTIFY_HERE) $unknown[$k] = true;
        }

        $n = (int)scalar('SELECT COUNT(*) FROM businesses WHERE toutify_website_id = ?', [$wid]);
        if ($n === 0) continue;
        $matched++;

        if ($dry) {
            $updated += $n;
        } else {
            $st = q('UPDATE businesses SET toutify_sites = ? WHERE toutify_website_id = ?',
                    [toutify_sites_encode($sites), $wid]);
            // rowCount() counts rows CHANGED, so a second run reports 0 for a
            // listing that already holds the same value. $n is what was
            // written to, which is the number the run is actually about.
            $updated += $n;
            unset($st);
        }
    }

    $pages++;
    echo "  page $pages: " . count($list) . " websites   listings written so far: $updated\n";

    $next = trim((string)($body['next_cursor'] ?? ''));
    if ($next === '' || $next === $cursor) break;
    if ($pages >= TSR_MAX_PAGES) {
        fwrite(STDERR, "Stopped at " . TSR_MAX_PAGES . " pages — the cursor is not ending.\n");
        break;
    }
    $cursor = $next;
}

echo "\n" . str_repeat('-', 56) . "\n";
printf("  %-34s %d\n", 'websites returned by Toutify', $seen);
printf("  %-34s %d\n", 'of those, carrying a sites list', $withSites);
printf("  %-34s %d\n", 'of those, linked to a listing here', $matched);
printf("  %-34s %d\n", $dry ? 'listings that WOULD be updated' : 'listings updated', $updated);
if ($unknown) {
    echo "\n  Service keys Toutify sent that no card here shows: "
       . implode(', ', array_keys($unknown)) . "\n"
       . "  They are stored as they came; add them to toutify_products() to show them.\n";
}
if ($dry) echo "\n  DRY RUN — nothing was written.\n";
