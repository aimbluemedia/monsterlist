<?php
// ---------------------------------------------------------------------------
// /auth/toutify/... — signing in through Toutify, and the two server-to-server
// endpoints it calls back on.
//
//   GET  /auth/toutify/login                start the flow (?signup=1, ?login_hint=)
//   GET  /auth/toutify/callback             come back from Toutify, sign in
//   GET  /auth/toutify/initiate             Toutify's dashboard sends members here
//   GET  /auth/toutify/logout               end both sessions
//   POST /auth/toutify/backchannel-logout   Toutify ends a session
//   POST /auth/toutify/webhook              member and website changes
//   GET  /auth/toutify/test                 hidden page for checking it works
//
// The client, the JWT verification and the session register are in
// app/lib/toutify_oidc.php.
//
// None of this is reachable while toutify.mode is absent or 'off'.
// ---------------------------------------------------------------------------

$tfAction = $segments[2] ?? '';

// The test page is the exception: it is the thing that says WHY the rest is not
// working, so hiding it whenever the rest is not working makes it useless at
// the only moment it is wanted. Everything else behaves as if it does not exist.
if (!toutify_ready() && !in_array($tfAction, ['webhook', 'test'], true)) {
    not_found();
}
if ($tfAction === 'test' && toutify_mode() === 'off' && !is_admin()) {
    not_found();
}

// ---------------------------------------------------------------------------
// Applying what Toutify tells us
// ---------------------------------------------------------------------------

/**
 * Copy the member-level claims onto a user row.
 *
 * `updated_at` is a fence, not decoration: events and sign-ins arrive out of
 * order, and without it a retried webhook from ten minutes ago overwrites a
 * change made since. Anything older than what we already applied is dropped.
 */
function toutify_apply_member(array $user, array $c, bool $force = false): void
{
    $seen = (int)($user['toutify_updated_at'] ?? 0);
    $now  = (int)($c['updated_at'] ?? 0);
    if (!$force && $now > 0 && $seen > 0 && $now < $seen) return;

    $sets = [];
    $args = [];
    $map  = [
        'name'          => 'name',
        'email'         => 'email',
        'business_name' => 'business_name',
        'phone_number'  => 'phone',
    ];
    foreach ($map as $claim => $col) {
        if (!array_key_exists($claim, $c)) continue;        // omitted means "unchanged"
        if (!column_exists('users', $col)) continue;
        $v = trim((string)$c[$claim]);
        if ($col === 'email' && $v === '') continue;         // never blank the login
        if ($col === 'name'  && $v === '') continue;
        $sets[] = "$col = ?";
        $args[] = $v !== '' ? mb_substr($v, 0, $col === 'phone' ? 40 : 180) : null;
    }
    if (isset($c['account_status'])) {
        $sets[] = 'toutify_status = ?';
        $args[] = mb_substr((string)$c['account_status'], 0, 20);
        // MonsterList has no 'closed'. Suspended is the nearest thing that
        // actually stops a login, which is the part that matters.
        $sets[] = 'status = ?';
        $args[] = $c['account_status'] === 'active' ? 'active' : 'suspended';
    }
    if ($now > 0) { $sets[] = 'toutify_updated_at = ?'; $args[] = $now; }
    if (!$sets) return;

    $args[] = (int)$user['id'];
    q('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?', $args);
}

/** Toutify's country name or code → the two-letter code MonsterList stores. */
function toutify_country_code(?string $country): string
{
    $c = trim((string)$country);
    if ($c === '') return '';
    if (preg_match('/^[A-Za-z]{2}$/', $c)) return strtoupper($c);
    $r = row('SELECT code FROM countries WHERE LOWER(name) = ?', [strtolower($c)]);
    return $r ? (string)$r['code'] : '';
}

/**
 * A city_id for Toutify's city/state/country strings.
 *
 * MonsterList does not store a city on the listing — it stores city_id, and the
 * state and country hang off that. So the strings have to be turned back into
 * a row. The state is matched on its name or its short code; when it matches
 * nothing the city is filed directly under the country, which is a shape the
 * routing already handles.
 */
function toutify_city_id(?string $city, ?string $state, ?string $country): ?int
{
    $cc = toutify_country_code($country);
    if ($cc === '' || trim((string)$city) === '') return null;

    $regionSlug = '';
    $st = trim((string)$state);
    if ($st !== '') {
        $r = row('SELECT slug FROM regions WHERE country_code = ? AND (LOWER(name) = ? OR LOWER(code) = ?)',
                 [$cc, strtolower($st), strtolower($st)]);
        if ($r) $regionSlug = (string)$r['slug'];
    }
    return resolve_city($cc, $regionSlug, (string)$city);
}

/**
 * Create or update the listing(s) for one Toutify website.
 *
 * Matched first on the website id, then on a URL the member already has — so a
 * listing somebody added here by hand is adopted rather than duplicated. Only
 * if neither matches is a listing created.
 */
function toutify_apply_website(array $user, array $w): void
{
    $wid = trim((string)($w['id'] ?? ''));
    if ($wid === '') return;
    $when = (int)($w['updated_at'] ?? 0);

    $listings = rows('SELECT * FROM businesses WHERE toutify_website_id = ?', [$wid]);
    if (!$listings) {
        $key = toutify_url_key($w['url'] ?? '');
        if ($key !== '') {
            foreach (rows('SELECT * FROM businesses WHERE owner_id = ?', [(int)$user['id']]) as $b) {
                if (toutify_url_key($b['website']) === $key) { $listings[] = $b; break; }
            }
        }
    }

    $url   = trim((string)($w['url'] ?? ''));
    $name  = trim((string)($w['business_name'] ?? ''));
    $phone = trim((string)($w['phone_number'] ?? ''));
    $tier  = trim((string)($w['tier'] ?? ''));

    if (!$listings) {
        if ($url === '') return;                      // nothing to build a listing from
        $cityId = toutify_city_id($w['city'] ?? null, $w['state'] ?? null, $w['country'] ?? null);
        if ($cityId === null) {
            // A listing with no city cannot have a URL on this site — uq_biz is
            // (city_id, slug) and every public path goes through the city. Park
            // the id on nothing rather than inserting a row that cannot be
            // reached; the next event with an address will create it properly.
            error_log('MonsterList: Toutify website ' . $wid . ' has no usable city, listing not created');
            return;
        }
        $title = $name !== '' ? $name : (display_host($url) ?: 'New listing');
        $slug  = unique_business_slug($title, $cityId);
        q('INSERT INTO businesses (owner_id, name, slug, city_id, status, website, phone, address, postcode,
                                   toutify_website_id, toutify_tier, toutify_updated_at)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
          [(int)$user['id'], mb_substr($title, 0, 180), $slug, $cityId,
           // Pending, not live: a listing that arrived from another system has
           // not been through this site's moderation, and that is the one
           // promise the directory makes about every page on it.
           'pending',
           mb_substr($url, 0, 255), $phone !== '' ? mb_substr($phone, 0, 40) : null,
           trim((string)($w['street'] ?? '')) !== '' ? mb_substr(trim((string)$w['street']), 0, 255) : null,
           trim((string)($w['zip'] ?? '')) !== '' ? mb_substr(trim((string)$w['zip']), 0, 20) : null,
           $wid, $tier !== '' ? mb_substr($tier, 0, 20) : null, $when ?: null]);
        refresh_city_count($cityId);
        return;
    }

    foreach ($listings as $b) {
        $seen = (int)($b['toutify_updated_at'] ?? 0);
        if ($when > 0 && $seen > 0 && $when < $seen) continue;
        $sets = ['toutify_website_id = ?', 'toutify_removed = 0'];
        $args = [$wid];
        foreach ([['url', 'website', 255], ['business_name', 'name', 180], ['phone_number', 'phone', 40],
                  ['street', 'address', 255], ['zip', 'postcode', 20]] as [$claim, $col, $len]) {
            if (!array_key_exists($claim, $w)) continue;
            $v = trim((string)$w[$claim]);
            if ($v === '' && in_array($col, ['website', 'name'], true)) continue;
            $sets[] = "$col = ?";
            $args[] = $v !== '' ? mb_substr($v, 0, $len) : null;
        }
        if ($tier !== '') { $sets[] = 'toutify_tier = ?'; $args[] = mb_substr($tier, 0, 20); }
        if ($when > 0)    { $sets[] = 'toutify_updated_at = ?'; $args[] = $when; }
        $args[] = (int)$b['id'];
        q('UPDATE businesses SET ' . implode(', ', $sets) . ' WHERE id = ?', $args);
    }
}

/** The member row for a set of claims: by sub, then by email, then created. */
function toutify_member_for(array $c): ?array
{
    $sub   = trim((string)($c['sub'] ?? ''));
    $email = strtolower(trim((string)($c['email'] ?? '')));
    if ($sub === '') return null;

    $u = row('SELECT * FROM users WHERE toutify_sub = ?', [$sub]);
    if ($u) return $u;

    if ($email !== '') {
        // Only a member nobody has claimed yet: a row that already carries a
        // different sub belongs to a different Toutify account, and quietly
        // taking it over would hand one person another person's listings.
        $u = row('SELECT * FROM users WHERE LOWER(TRIM(email)) = ? AND toutify_sub IS NULL', [$email]);
        if ($u) {
            q('UPDATE users SET toutify_sub = ? WHERE id = ? AND toutify_sub IS NULL', [$sub, (int)$u['id']]);
            $u['toutify_sub'] = $sub;
            return $u;
        }
    }

    if ($email === '') return null;
    // Created the way sign-up creates one, minus the password: the column is
    // NOT NULL, so it gets a value no password can ever produce rather than an
    // empty string, which password_verify() would still be asked about.
    $name = trim((string)($c['name'] ?? '')) ?: ($email !== '' ? explode('@', $email)[0] : 'Member');
    q('INSERT INTO users (email, password_hash, name, toutify_sub) VALUES (?,?,?,?)',
      [$email, '!toutify-no-local-password', mb_substr($name, 0, 140), $sub]);
    return row('SELECT * FROM users WHERE id = ?', [(int)db()->lastInsertId()]);
}

// ---------------------------------------------------------------------------
// Routes
// ---------------------------------------------------------------------------

if ($tfAction === 'login') {
    $verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    $state    = bin2hex(random_bytes(16));
    $nonce    = bin2hex(random_bytes(16));
    $_SESSION['tf_pkce']  = $verifier;
    $_SESSION['tf_state'] = $state;
    $_SESSION['tf_nonce'] = $nonce;
    $_SESSION['tf_at']    = time();

    $params = [
        'client_id'             => toutify_client_id(),
        'redirect_uri'          => site_url('/auth/toutify/callback'),
        'response_type'         => 'code',
        'scope'                 => 'openid profile email phone toutify websites',
        'state'                 => $state,
        'nonce'                 => $nonce,
        'code_challenge'        => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
    ];
    if (($_GET['signup'] ?? '') === '1')          $params['prompt'] = 'create';
    if (trim((string)($_GET['login_hint'] ?? ''))) $params['login_hint'] = trim((string)$_GET['login_hint']);

    redirect(toutify_endpoint('authorization_endpoint', '/oauth/authorize') . '?' . http_build_query($params));

} elseif ($tfAction === 'initiate') {
    // Toutify's dashboard sends members here. The issuer is checked because
    // this is a URL anybody can type, and following an unknown one would be
    // starting a sign-in with a provider we have never heard of.
    if ((string)($_GET['iss'] ?? '') !== toutify_issuer()) {
        http_response_code(400);
        exit('Unknown issuer.');
    }
    redirect('/auth/toutify/login');

} elseif ($tfAction === 'callback') {
    $fail = function (string $why) {
        error_log('MonsterList: Toutify sign-in failed — ' . $why);
        flash_set('error', 'Sorry — signing in through Toutify did not work. Please try again.');
        redirect('/login');
    };

    if (trim((string)($_GET['error'] ?? '')) !== '') $fail('provider returned ' . $_GET['error']);
    $state = (string)($_GET['state'] ?? '');
    if ($state === '' || !hash_equals((string)($_SESSION['tf_state'] ?? ''), $state)) $fail('state did not match');
    if (time() - (int)($_SESSION['tf_at'] ?? 0) > 1800) $fail('the sign-in took longer than 30 minutes');

    $verifier = (string)($_SESSION['tf_pkce'] ?? '');
    $nonce    = (string)($_SESSION['tf_nonce'] ?? '');
    unset($_SESSION['tf_state'], $_SESSION['tf_pkce'], $_SESSION['tf_nonce'], $_SESSION['tf_at']);

    $code = (string)($_GET['code'] ?? '');
    if ($code === '' || $verifier === '') $fail('no code came back');

    [$st, $tok] = toutify_post_form(toutify_endpoint('token_endpoint', '/oauth/token'), [
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'redirect_uri'  => site_url('/auth/toutify/callback'),
        'code_verifier' => $verifier,
    ], [toutify_basic_header()]);
    if ($st !== 200 || !is_array($tok) || empty($tok['id_token'])) $fail("token exchange returned HTTP $st");

    [$claims, $why] = toutify_verify_jwt((string)$tok['id_token'], ['nonce' => $nonce]);
    if ($claims === null) $fail('ID token rejected: ' . $why);
    $sub = (string)($claims['sub'] ?? '');
    if ($sub === '') $fail('no sub in the ID token');

    // UserInfo is the fuller picture — the websites list in particular.
    if (!empty($tok['access_token'])) {
        [$ust, $info] = toutify_http('GET', toutify_endpoint('userinfo_endpoint', '/oauth/userinfo'), null,
            ['Authorization: Bearer ' . $tok['access_token']], 2);
        if ($ust === 200 && is_array($info)) {
            // The subject has to agree, or we would be merging one person's
            // profile into another's session.
            if ((string)($info['sub'] ?? '') !== $sub) $fail('UserInfo sub did not match the ID token');
            $claims = array_merge($claims, $info);
        }
    }

    if ((string)($claims['account_status'] ?? 'active') !== 'active') {
        flash_set('error', 'That account is not active. Please contact support.');
        redirect('/login');
    }

    $email = strtolower(trim((string)($claims['email'] ?? '')));
    if (toutify_mode() === 'test' && !toutify_allowed_in_test($email)) {
        flash_set('error', 'Please log in here for now.');
        redirect('/login');
    }

    $user = toutify_member_for($claims);
    if (!$user) $fail('no email in the claims, so no member could be matched or created');

    toutify_apply_member($user, $claims);
    foreach ((array)($claims['websites'] ?? []) as $w) {
        if (is_array($w)) toutify_apply_website($user, $w);
    }

    $user = row('SELECT * FROM users WHERE id = ?', [(int)$user['id']]);
    login_user($user);                       // the same session start the normal login uses
    $_SESSION['tf_id_token'] = (string)($tok['id_token'] ?? '');
    $_SESSION['tf_sid']      = (string)($claims['sid'] ?? '');
    $_SESSION['tf_sub']      = $sub;
    toutify_session_register(session_id(), (string)($claims['sid'] ?? ''), $sub);
    redirect('/members');

} elseif ($tfAction === 'logout') {
    $idToken = (string)($_SESSION['tf_id_token'] ?? '');
    $sub     = (string)($_SESSION['tf_sub'] ?? '');
    $sid     = (string)($_SESSION['tf_sid'] ?? '');
    toutify_session_forget(session_id());
    if ($sub !== '') toutify_session_revoke($sub, $sid !== '' ? $sid : null);
    logout_user();

    $end = toutify_endpoint('end_session_endpoint', '/oauth/logout');
    redirect($end . '?' . http_build_query(array_filter([
        'id_token_hint'            => $idToken,
        'post_logout_redirect_uri' => site_url('/'),
        'state'                    => bin2hex(random_bytes(8)),
        'client_id'                => toutify_client_id(),
    ])));

} elseif ($tfAction === 'backchannel-logout') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
    $token = (string)($_POST['logout_token'] ?? '');
    if ($token === '') { http_response_code(400); exit('no logout_token'); }

    [$c, $why] = toutify_verify_jwt($token, ['typ' => 'logout+jwt']);
    if ($c === null)                     { http_response_code(400); exit('invalid: ' . $why); }
    if (isset($c['nonce']))              { http_response_code(400); exit('a logout token must not carry a nonce'); }
    $events = (array)($c['events'] ?? []);
    if (!array_key_exists('http://schemas.openid.net/event/backchannel-logout', $events)) {
        http_response_code(400); exit('not a back-channel logout event');
    }
    $sub = (string)($c['sub'] ?? '');
    $sid = (string)($c['sid'] ?? '');
    if ($sub !== '') toutify_session_revoke($sub, $sid !== '' ? $sid : null);
    // 200 even when nothing matched: "that session is already gone" is success.
    http_response_code(200);
    header('Cache-Control: no-store');
    exit('ok');

} elseif ($tfAction === 'webhook') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
    // RAW first. Anything that parses the body before the signature is checked
    // is verifying a re-encoding of the bytes rather than the bytes.
    $raw = file_get_contents('php://input');
    $sigHeader = (string)($_SERVER['HTTP_TOUTIFY_SIGNATURE'] ?? '');

    $t = ''; $v1 = '';
    foreach (explode(',', $sigHeader) as $part) {
        $kv = explode('=', trim($part), 2);
        if (count($kv) !== 2) continue;
        if ($kv[0] === 't')  $t  = $kv[1];
        if ($kv[0] === 'v1') $v1 = $kv[1];
    }
    $secret = toutify_webhook_secret();
    if ($secret === '' || $t === '' || $v1 === '') { http_response_code(400); exit('bad signature header'); }
    if (abs(time() - (int)$t) > 300)               { http_response_code(400); exit('timestamp out of range'); }
    $expect = hash_hmac('sha256', $t . '.' . $raw, $secret);
    if (!hash_equals($expect, strtolower($v1)))    { http_response_code(400); exit('signature mismatch'); }

    $body = json_decode($raw, true);
    if (!is_array($body)) { http_response_code(400); exit('body is not JSON'); }
    $id   = trim((string)($body['id'] ?? ''));
    $type = trim((string)($body['type'] ?? ''));
    if ($id === '') { http_response_code(400); exit('no event id'); }

    // The id goes in FIRST. Toutify retries for 24 hours, so the same event
    // arrives more than once as a matter of course, and the primary key is what
    // makes applying it twice impossible rather than merely unlikely.
    try {
        q('INSERT INTO toutify_events (event_id, type, received_at) VALUES (?,?,?)', [$id, mb_substr($type, 0, 40), time()]);
    } catch (Throwable $e) {
        http_response_code(200);
        exit('already handled');
    }

    try {
        db()->beginTransaction();
        $m = (array)($body['member'] ?? []);
        $w = (array)($body['website'] ?? []);

        if (in_array($type, ['member.updated', 'member.reactivated', 'member.suspended', 'member.closed'], true)) {
            $u = row('SELECT * FROM users WHERE toutify_sub = ?', [(string)($m['sub'] ?? '')]);
            if ($u) {
                toutify_apply_member($u, $m);
                if (in_array($type, ['member.suspended', 'member.closed'], true)) {
                    toutify_session_revoke((string)$m['sub']);
                }
            }

        } elseif ($type === 'member.deleted') {
            $sub = (string)($m['sub'] ?? ($body['sub'] ?? ''));
            if ($sub !== '') {
                // Nothing is deleted here. A member who left Toutify still has
                // listings, reviews and a history on this site.
                q('UPDATE users SET toutify_status = ?, status = ? WHERE toutify_sub = ?', ['deleted', 'suspended', $sub]);
                toutify_session_revoke($sub);
            }

        } elseif (in_array($type, ['website.joined', 'website.updated'], true)) {
            $u = $m ? toutify_member_for($m) : null;
            if (!$u && !empty($w['owner'])) {
                $u = row('SELECT * FROM users WHERE toutify_sub = ?', [(string)$w['owner']]);
            }
            if ($u) {
                if ($m) toutify_apply_member($u, $m);
                toutify_apply_website($u, $w);
            }

        } elseif (in_array($type, ['website.removed', 'website.deleted'], true)) {
            $wid = trim((string)($w['id'] ?? ''));
            if ($wid !== '') {
                // Hidden, not deleted, and not moved out of 'live' either — so
                // that putting it back is one flag and nothing else was lost.
                q('UPDATE businesses SET toutify_removed = 1, toutify_updated_at = ? WHERE toutify_website_id = ?',
                  [(int)($w['updated_at'] ?? time()), $wid]);
            }
        }
        // Any other type — site.test included — is accepted and ignored.

        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        error_log('MonsterList: Toutify webhook ' . $id . ' failed — ' . $e->getMessage());
        // The event id row is rolled back with everything else, so the retry
        // Toutify sends over the next 24 hours gets a fresh attempt.
        http_response_code(500);
        exit('could not apply');
    }
    http_response_code(200);
    exit('ok');

} elseif ($tfAction === 'test') {
    $me   = current_user();
    $meta = ['title' => 'Toutify sign-in test', 'robots' => 'noindex, nofollow'];
    view('auth/toutify-test', compact('meta', 'me'));

} else {
    not_found();
}
