<?php
// ---------------------------------------------------------------------------
// scripts/toutify-listings — tell Toutify which websites a member has here.
//
//   php scripts/toutify-listings.php --emails=a@x.com,b@y.com [--dry-run]
//   php scripts/toutify-listings.php --all [--dry-run]
//
// Only listings whose owner already has a toutify_sub (run scripts/toutify-link
// first) and that have a URL. Everything else is counted and skipped.
//
// Toutify matches on the address: a listing whose URL it already holds for that
// member is linked to the existing website rather than duplicated. So running
// this again is safe, and the second run reports `unchanged` rather than
// creating anything.
//
// The city, state and country are NOT columns on a listing here — they hang off
// cities, and the state off regions — so they are joined back together below.
// ---------------------------------------------------------------------------

if (PHP_SAPI !== 'cli') { http_response_code(404); exit("Not found\n"); }

$ML_WEB_ROOT = dirname(__DIR__);
require $ML_WEB_ROOT . '/app/bootstrap.php';

const TW_PAGE = 500;

$opts = ['emails' => null, 'all' => false, 'dry-run' => false];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--all')                { $opts['all'] = true; continue; }
    if ($arg === '--dry-run')            { $opts['dry-run'] = true; continue; }
    if (strpos($arg, '--emails=') === 0) { $opts['emails'] = substr($arg, 9); continue; }
    fwrite(STDERR, "Unknown option: $arg\n"); exit(2);
}
if (!$opts['all'] && $opts['emails'] === null) {
    fwrite(STDERR, "Give --all or --emails=a@x.com,b@y.com. Add --dry-run to change nothing.\n");
    exit(2);
}
$dry = (bool)$opts['dry-run'];

if (toutify_client_secret() === '') { fwrite(STDERR, "No Toutify client secret in app/config.php.\n"); exit(1); }
if (!column_exists('businesses', 'toutify_website_id')) {
    fwrite(STDERR, "The database has no businesses.toutify_website_id yet. Run database/upgrade-toutify.sql first.\n");
    exit(1);
}

// ---------- which listings ----------
$where  = ["u.toutify_sub IS NOT NULL", "b.website IS NOT NULL", "TRIM(b.website) <> ''"];
$params = [];
if ($opts['emails'] !== null) {
    $list = array_values(array_filter(array_map('trim', explode(',', $opts['emails'])), 'strlen'));
    if (!$list) { fwrite(STDERR, "--emails was empty.\n"); exit(2); }
    $where[] = 'LOWER(TRIM(u.email)) IN (' . implode(',', array_fill(0, count($list), '?')) . ')';
    foreach ($list as $e) $params[] = strtolower($e);
}
$sqlWhere = implode(' AND ', $where);
$joins = 'FROM businesses b
          JOIN users u      ON u.id = b.owner_id
          LEFT JOIN cities ci  ON ci.id = b.city_id
          LEFT JOIN regions r  ON r.id = ci.region_id';

$total = (int)scalar("SELECT COUNT(*) $joins WHERE $sqlWhere", $params);

// Worth saying out loud, because the difference between this and what somebody
// expects is almost always one of these two.
$noSub = (int)scalar("SELECT COUNT(*) FROM businesses b JOIN users u ON u.id = b.owner_id
                       WHERE u.toutify_sub IS NULL", []);
$noUrl = (int)scalar("SELECT COUNT(*) FROM businesses b JOIN users u ON u.id = b.owner_id
                       WHERE u.toutify_sub IS NOT NULL AND (b.website IS NULL OR TRIM(b.website) = '')", []);

echo ($dry ? "DRY RUN — nothing will be written on either side.\n" : "")
   . "Toutify: " . toutify_issuer() . "  client: " . toutify_client_id() . "\n"
   . "Listings to send: $total\n"
   . "  skipped, owner not linked yet: $noSub   (run scripts/toutify-link first)\n"
   . "  skipped, no website URL:       $noUrl\n\n";
if ($total === 0) exit(0);

$problems = [];
$counts = ['sent' => 0, 'created' => 0, 'linked' => 0, 'unchanged' => 0, 'error' => 0, 'saved' => 0];
$lastId = 0;

while (true) {
    // Keyset paging: this writes to businesses while reading it, and OFFSET
    // over a result set that is moving underneath you skips rows.
    $page = rows("SELECT b.id, b.owner_id, b.website, b.name, b.phone, b.address, b.postcode,
                         b.created_at, ci.name AS city_name, r.name AS region_name, r.code AS region_code,
                         ci.country_code, u.toutify_sub AS owner_sub
                  $joins WHERE $sqlWhere AND b.id > ?
                  ORDER BY b.id LIMIT " . TW_PAGE, array_merge($params, [$lastId]));
    if (!$page) break;
    $lastId = (int)end($page)['id'];

    $websites = [];
    foreach ($page as $b) {
        // Empty is null, not "": an empty string is a value, and Toutify would
        // take it as "this business has no name" rather than "we do not know".
        $v = fn($x) => ($x !== null && trim((string)$x) !== '') ? trim((string)$x) : null;
        $websites[] = [
            'external_id'   => (string)$b['id'],
            'owner'         => (string)$b['owner_id'],
            // Carried by the join above, not looked up per row: one query per
            // listing is 695 round trips on a full run, for a value the page
            // query already had in hand.
            'owner_sub'     => (string)$b['owner_sub'],
            'url'           => $v($b['website']),
            'business_name' => $v($b['name']),
            'phone_number'  => $v($b['phone']),
            'street'        => $v($b['address']),
            'city'          => $v($b['city_name']),
            // The short code where there is one — "AZ" is what an address
            // carries — and the full name otherwise, since most countries
            // outside the US do not abbreviate their regions.
            'state'         => $v($b['region_code']) ?? $v($b['region_name']),
            'zip'           => $v($b['postcode']),
            'country'       => $v($b['country_code']),
            'created_at'    => substr((string)$b['created_at'], 0, 10),
        ];
    }
    $counts['sent'] += count($websites);

    [$code, $body, $err] = toutify_http('POST', toutify_issuer() . '/api/v1/import/websites',
        ['dry_run' => $dry, 'websites' => $websites], [toutify_basic_header()], 3);

    if ($code !== 200 || !is_array($body)) {
        fwrite(STDERR, "Request failed (HTTP $code) " . ($err ?: (is_string($body) ? substr($body, 0, 300) : '')) . "\n");
        foreach ($page as $b) $problems[] = [$b['id'], $b['website'], 'request_failed', "HTTP $code"];
        $counts['error'] += count($page);
        continue;
    }

    $byId = [];
    foreach ($page as $b) $byId[(string)$b['id']] = $b;
    foreach (($body['results'] ?? []) as $r) {
        $ext    = (string)($r['external_id'] ?? '');
        $action = (string)($r['action'] ?? 'error');
        $wid    = (string)($r['website_id'] ?? '');
        if (isset($counts[$action])) $counts[$action]++;

        if ($action !== 'error' && $wid !== '') {
            if (!$dry) {
                q('UPDATE businesses SET toutify_website_id = ? WHERE id = ?', [$wid, (int)$ext]);
                $counts['saved']++;
            }
        } else {
            $problems[] = [$ext, $byId[$ext]['website'] ?? '',
                           $r['error'] ?? 'error', $r['error_description'] ?? ''];
        }
    }
    echo "  created: {$counts['created']}  linked: {$counts['linked']}"
       . "  unchanged: {$counts['unchanged']}  errors: {$counts['error']}\n";
}

if ($problems) {
    $csv = $ML_WEB_ROOT . '/toutify-listing-problems.csv';
    $fh  = fopen($csv, 'w');
    fputcsv($fh, ['listing_id', 'url', 'error', 'error_description']);
    foreach ($problems as $p) fputcsv($fh, $p);
    fclose($fh);
}

echo "\n" . str_repeat('-', 56) . "\n";
foreach (['sent' => 'sent', 'created' => 'created on Toutify', 'linked' => 'linked to an existing website',
          'unchanged' => 'already linked', 'error' => 'errors',
          'saved' => 'toutify_website_id written here'] as $k => $label) {
    printf("  %-32s %d\n", $label, $counts[$k]);
}
if ($problems) {
    echo "\n  Problems written to toutify-listing-problems.csv (" . count($problems) . " rows)\n";
    echo "  url_owned_by_another_member is settled by staff on Toutify, not here.\n";
}
if ($dry) echo "\n  DRY RUN — nothing was written.\n";
