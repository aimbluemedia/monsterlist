<?php
// ---------------------------------------------------------------------------
// scripts/toutify-link — match MonsterList members to their Toutify accounts.
//
//   php scripts/toutify-link.php --emails=a@x.com,b@y.com [--dry-run]
//   php scripts/toutify-link.php --all [--dry-run]
//   php scripts/toutify-link.php --all --import-missing [--dry-run]
//
// WITHOUT --import-missing it sends link_only: external_id and email, nothing
// else. Toutify matches the member by email, records that they use MonsterList,
// and creates nobody. Members it does not recognise come back as not_found.
//
// WITH --import-missing it asks Toutify to CREATE the accounts that came back
// not_found, sending name, business name, phone, status, join date and — where
// MonsterList stores one — the bcrypt password hash, so those members keep the
// password they already use. A member whose hash is not bcrypt is sent an empty
// one and sets a password through Toutify's "Forgot password".
//
// Safe to run again: a member that is already linked comes back `unchanged`,
// and the only thing written on this side is users.toutify_sub.
//
// Only role='member' accounts are sent. Staff sign in at /superadmin/login,
// which this integration does not touch.
// ---------------------------------------------------------------------------

if (PHP_SAPI !== 'cli') { http_response_code(404); exit("Not found\n"); }

$ML_WEB_ROOT = dirname(__DIR__);
require $ML_WEB_ROOT . '/app/bootstrap.php';

const TL_PAGE = 500;

// ---------- options ----------
$opts = ['emails' => null, 'all' => false, 'dry-run' => false, 'import-missing' => false];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--all')                  { $opts['all'] = true; continue; }
    if ($arg === '--dry-run')              { $opts['dry-run'] = true; continue; }
    if ($arg === '--import-missing')       { $opts['import-missing'] = true; continue; }
    if (strpos($arg, '--emails=') === 0)   { $opts['emails'] = substr($arg, 9); continue; }
    fwrite(STDERR, "Unknown option: $arg\n");
    exit(2);
}
if (!$opts['all'] && $opts['emails'] === null) {
    fwrite(STDERR, "Give --all or --emails=a@x.com,b@y.com. Add --dry-run to change nothing.\n");
    exit(2);
}
$dry = (bool)$opts['dry-run'];

if (toutify_client_secret() === '') {
    fwrite(STDERR, "No Toutify client secret in app/config.php — nothing to authenticate with.\n");
    exit(1);
}
if (!column_exists('users', 'toutify_sub')) {
    fwrite(STDERR, "The database has no users.toutify_sub yet. Run database/upgrade-all.sql first.\n");
    exit(1);
}

// ---------- who to send ----------
$where  = ["u.role = 'member'", "u.email IS NOT NULL", "TRIM(u.email) <> ''"];
$params = [];
if ($opts['emails'] !== null) {
    $list = array_values(array_filter(array_map('trim', explode(',', $opts['emails'])), 'strlen'));
    if (!$list) { fwrite(STDERR, "--emails was empty.\n"); exit(2); }
    $where[] = 'LOWER(TRIM(u.email)) IN (' . implode(',', array_fill(0, count($list), '?')) . ')';
    foreach ($list as $e) $params[] = strtolower($e);
}
$sqlWhere = implode(' AND ', $where);

$total = (int)scalar("SELECT COUNT(*) FROM users u WHERE $sqlWhere", $params);
$problems = [];
$counts = ['sent' => 0, 'linked' => 0, 'unchanged' => 0, 'created' => 0,
           'not_found' => 0, 'error' => 0, 'saved' => 0, 'no_password' => 0];

echo ($dry ? "DRY RUN — nothing will be written on either side.\n" : "")
   . "Toutify: " . toutify_issuer() . "  client: " . toutify_client_id() . "\n"
   . ($opts['import-missing'] ? "Mode: link, then CREATE the ones Toutify does not have.\n"
                              : "Mode: link only. Toutify creates nobody.\n")
   . "Members to send: $total\n\n";
if ($total === 0) exit(0);

// ---------- pass one: link ----------
$notFound = [];     // members Toutify did not recognise, for --import-missing
$lastId = 0;
while (true) {
    // Keyset paging rather than OFFSET: this writes to the same table it is
    // reading, and OFFSET over a moving result set skips rows.
    $page = rows("SELECT u.id, u.email, u.name, u.business_name, u.phone, u.status,
                         u.password_hash, u.created_at, u.toutify_sub
                    FROM users u WHERE $sqlWhere AND u.id > ?
                   ORDER BY u.id LIMIT " . TL_PAGE, array_merge($params, [$lastId]));
    if (!$page) break;
    $lastId = (int)end($page)['id'];

    $members = [];
    foreach ($page as $m) {
        $members[] = ['external_id' => (string)$m['id'], 'email' => (string)$m['email']];
    }
    $counts['sent'] += count($members);

    [$code, $body, $err] = toutify_http('POST', toutify_issuer() . '/api/v1/import',
        ['link_only' => true, 'dry_run' => $dry, 'members' => $members],
        [toutify_basic_header()], 3);

    if ($code !== 200 || !is_array($body)) {
        fwrite(STDERR, "Request failed (HTTP $code) " . ($err ?: (is_string($body) ? substr($body, 0, 300) : '')) . "\n");
        foreach ($page as $m) $problems[] = [$m['id'], $m['email'], 'request_failed', "HTTP $code"];
        $counts['error'] += count($page);
        continue;
    }

    $byId = [];
    foreach ($page as $m) $byId[(string)$m['id']] = $m;
    foreach (($body['results'] ?? []) as $r) {
        $ext    = (string)($r['external_id'] ?? '');
        $action = (string)($r['action'] ?? 'error');
        $sub    = (string)($r['sub'] ?? '');
        $member = $byId[$ext] ?? null;
        if (isset($counts[$action])) $counts[$action]++;

        if (in_array($action, ['linked', 'unchanged'], true) && $sub !== '') {
            if (!$dry) {
                q('UPDATE users SET toutify_sub = ? WHERE id = ? AND (toutify_sub IS NULL OR toutify_sub = ?)',
                  [$sub, (int)$ext, $sub]);
                $counts['saved']++;
            }
        } elseif ($action === 'not_found') {
            if ($member) $notFound[] = $member;
            $problems[] = [$ext, $member['email'] ?? '', 'not_found', ''];
        } else {
            $problems[] = [$ext, $member['email'] ?? '', $r['error'] ?? 'error', $r['error_description'] ?? ''];
        }
    }
    echo "  linked so far: {$counts['linked']}  unchanged: {$counts['unchanged']}"
       . "  not_found: {$counts['not_found']}  errors: {$counts['error']}\n";
}

// ---------- pass two: create the ones Toutify does not have ----------
if ($opts['import-missing'] && $notFound) {
    echo "\nImporting " . count($notFound) . " member(s) Toutify did not recognise.\n";
    foreach (array_chunk($notFound, TL_PAGE) as $chunk) {
        $members = [];
        foreach ($chunk as $m) {
            // Only a bcrypt hash travels. Anything else — argon2, an old
            // format, an empty column — is sent as "" and the member sets a
            // password on Toutify. Plain text is never sent under any
            // circumstances; there is nowhere here that could produce it.
            $hash = (string)$m['password_hash'];
            $isBcrypt = (bool)preg_match('/^\$2[aby]\$/', $hash);
            if (!$isBcrypt) $counts['no_password']++;

            $members[] = [
                'external_id'    => (string)$m['id'],
                'email'          => (string)$m['email'],
                'name'           => (string)($m['name'] ?? ''),
                'business_name'  => (string)($m['business_name'] ?? ''),
                'phone'          => (string)($m['phone'] ?? ''),
                'password_hash'  => $isBcrypt ? $hash : '',
                'account_status' => toutify_status_from_local($m),
                'member_since'   => substr((string)$m['created_at'], 0, 10),
            ];
        }

        [$code, $body, $err] = toutify_http('POST', toutify_issuer() . '/api/v1/import',
            ['dry_run' => $dry, 'members' => $members], [toutify_basic_header()], 3);

        if ($code !== 200 || !is_array($body)) {
            fwrite(STDERR, "Import failed (HTTP $code) " . ($err ?: '') . "\n");
            foreach ($chunk as $m) $problems[] = [$m['id'], $m['email'], 'import_failed', "HTTP $code"];
            continue;
        }
        foreach (($body['results'] ?? []) as $r) {
            $ext    = (string)($r['external_id'] ?? '');
            $action = (string)($r['action'] ?? 'error');
            $sub    = (string)($r['sub'] ?? '');
            if (isset($counts[$action])) $counts[$action]++;
            if (in_array($action, ['created', 'linked', 'unchanged'], true) && $sub !== '') {
                if (!$dry) {
                    q('UPDATE users SET toutify_sub = ? WHERE id = ? AND toutify_sub IS NULL', [$sub, (int)$ext]);
                    $counts['saved']++;
                }
            } else {
                $problems[] = [$ext, '', $r['error'] ?? 'error', $r['error_description'] ?? ''];
            }
        }
    }
}

// ---------- problems ----------
if ($problems) {
    $csv = $ML_WEB_ROOT . '/toutify-link-problems.csv';
    $fh  = fopen($csv, 'w');
    fputcsv($fh, ['member_id', 'email', 'error', 'error_description']);
    foreach ($problems as $p) fputcsv($fh, $p);
    fclose($fh);
}

echo "\n" . str_repeat('-', 56) . "\n";
foreach (['sent' => 'sent', 'linked' => 'linked', 'unchanged' => 'already linked',
          'created' => 'created on Toutify', 'not_found' => 'not found on Toutify',
          'error' => 'errors', 'saved' => 'toutify_sub written here'] as $k => $label) {
    printf("  %-26s %d\n", $label, $counts[$k]);
}
if ($opts['import-missing']) {
    printf("  %-26s %d\n", 'imported with no password', $counts['no_password']);
    if ($counts['no_password'] > 0) {
        echo "    (those members use \"Forgot password\" on Toutify to set one)\n";
    }
}
if ($problems) echo "\n  Problems written to toutify-link-problems.csv (" . count($problems) . " rows)\n";
if ($dry)      echo "\n  DRY RUN — nothing was written.\n";
