<?php
// ---------------------------------------------------------------------------
// Toutify (toutify.com) — the parent account system that holds every member's
// password and signs them in over OpenID Connect.
//
// This file is the client: configuration, discovery, JWT verification, the
// session register that makes back-channel logout possible, and the small
// helpers the routes and the import scripts share. The routes themselves are
// in app/controllers/toutify.php.
//
// WHY THIS IS HAND-WRITTEN rather than jumbojett/openid-connect-php:
// MonsterList has no Composer, no vendor/ and no autoloader, and it deploys by
// uploading the repository to shared hosting over FTP — where `composer
// install` is not something that can be relied on. Stripe and Anthropic are
// already called the same way, with cURL and json_decode. Adding a dependency
// manager for one feature would be a bigger change to how this site is built
// and shipped than the feature itself.
//
// NOTHING HERE READS OR WRITES users.password_hash. Toutify passwords are never
// seen by MonsterList, and MonsterList's own passwords are never sent to
// Toutify except by scripts/toutify-link --import-missing, which is explicit
// about it.
// ---------------------------------------------------------------------------

/** Every Toutify setting, read from app/config.php. Empty string when unset. */
function toutify_cfg(string $key, string $default = ''): string
{
    $c = $GLOBALS['config']['toutify'] ?? [];
    $v = trim((string)($c[$key] ?? ''));
    return $v !== '' ? $v : $default;
}

function toutify_issuer(): string      { return rtrim(toutify_cfg('issuer', 'https://toutify.com'), '/'); }
function toutify_client_id(): string   { return toutify_cfg('client_id', 'monsterlist'); }
function toutify_client_secret(): string { return toutify_cfg('client_secret'); }
function toutify_webhook_secret(): string { return toutify_cfg('webhook_secret'); }

/**
 * 'off', 'test' or 'live'.
 *
 * Anything unrecognised — including a missing setting — reads as 'off', so a
 * site that has never heard of Toutify behaves exactly as it did before. The
 * switch back is this one value: set it to 'test' and today's login, sign-up
 * and password reset are in charge again.
 */
function toutify_mode(): string
{
    $m = strtolower(toutify_cfg('mode', 'off'));
    return in_array($m, ['test', 'live'], true) ? $m : 'off';
}

/** Is the integration configured enough to attempt a sign-in at all? */
function toutify_ready(): bool
{
    return toutify_mode() !== 'off'
        && toutify_client_secret() !== ''
        && column_exists('users', 'toutify_sub');
}

/**
 * Everything standing between the current setup and Toutify signing people in.
 *
 * Returns a list of [what, ok, what to do about it]. Empty means it is working.
 * This exists because the failure is silent by design: toutify_ready() keeps
 * the local login form when anything is missing, which is the right thing to do
 * and also indistinguishable, from outside, from "the switch did not work".
 */
function toutify_blockers(): array
{
    $out = [];
    $mode = toutify_mode();
    $out[] = ['Mode', $mode !== 'off',
        $mode === 'off'
            ? "Set 'mode' => 'test' (or 'live') in the toutify block of app/config.php."
            : "Currently '$mode'."];
    $out[] = ['Client secret', toutify_client_secret() !== '',
        "Paste Toutify's client secret into app/config.php. Note the shape: a 'toutify' "
      . "array, not TOUTIFY_* environment variables — nothing here reads those."];
    $out[] = ['Webhook secret', toutify_webhook_secret() !== '',
        'Needed for member and website updates to arrive. Sign-in works without it.'];
    $hasCols = column_exists('users', 'toutify_sub') && table_exists('toutify_sessions');
    $out[] = ['Database upgraded', $hasCols,
        'Run database/upgrade-all.sql from this release in phpMyAdmin. Until then the '
      . 'local login stays in charge no matter what mode says.'];
    if ($mode === 'live') {
        $out[] = ['Live sign-in active', toutify_ready(),
            toutify_ready() ? 'The login page sends people to Toutify.'
                            : 'Mode is live, but something above is missing, so the local login form is '
                            . 'still being served. Nobody is locked out.'];
    }
    return $out;
}

/** Emails allowed to sign in with Toutify while mode is 'test'. Lower-cased. */
function toutify_test_allowlist(): array
{
    $raw = toutify_cfg('test_allowlist');
    if ($raw === '') return [];
    $out = [];
    foreach (preg_split('/[,\s]+/', $raw) as $e) {
        $e = strtolower(trim($e));
        if ($e !== '') $out[] = $e;
    }
    return $out;
}

function toutify_allowed_in_test(string $email): bool
{
    return in_array(strtolower(trim($email)), toutify_test_allowlist(), true);
}

// ---------------------------------------------------------------------------
// HTTP
// ---------------------------------------------------------------------------

/**
 * The Authorization header for every server-to-server call.
 *
 * client_secret_basic, with both halves URL-encoded before they are joined —
 * that is what the OAuth spec asks for and what Toutify expects. Getting this
 * wrong fails as a flat 401 with nothing to go on, so it lives in one place.
 */
function toutify_basic_header(): string
{
    return 'Authorization: Basic ' . base64_encode(
        rawurlencode(toutify_client_id()) . ':' . rawurlencode(toutify_client_secret()));
}

/**
 * One HTTP call to Toutify. Returns [status, decoded body or raw string, error].
 *
 * $retries applies to transport failures and 5xx only. A 4xx is an answer —
 * retrying a rejected request just asks to be rejected again, more slowly.
 */
function toutify_http(string $method, string $url, ?array $json = null, array $headers = [], int $retries = 0): array
{
    $attempt = 0;
    while (true) {
        $ch = curl_init($url);
        $h  = array_merge(['Accept: application/json'], $headers);
        if ($json !== null) $h[] = 'Content-Type: application/json';
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $h,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($json !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = $raw === false ? curl_error($ch) : '';
        curl_close($ch);

        $transportFailed = $raw === false;
        if (($transportFailed || $code >= 500) && $attempt < $retries) {
            $attempt++;
            sleep(min(8, 1 << $attempt));   // 2s, 4s, 8s
            continue;
        }
        if ($transportFailed) return [0, null, $err ?: 'request failed'];
        $decoded = json_decode((string)$raw, true);
        return [$code, $decoded === null ? (string)$raw : $decoded, ''];
    }
}

/** Form-encoded POST, for the token endpoint. */
function toutify_post_form(string $url, array $fields, array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_HTTPHEADER     => array_merge(
            ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'], $headers),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $raw  = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = $raw === false ? curl_error($ch) : '';
    curl_close($ch);
    if ($raw === false) return [0, null, $err ?: 'request failed'];
    $decoded = json_decode((string)$raw, true);
    return [$code, $decoded === null ? (string)$raw : $decoded, ''];
}

// ---------------------------------------------------------------------------
// Discovery and keys
// ---------------------------------------------------------------------------

/**
 * The provider's configuration, cached in settings for an hour.
 *
 * Cached because every sign-in needs it and a fetch per sign-in makes the
 * login page depend on a second network round trip. An hour is short enough
 * that a rotated endpoint is picked up the same morning.
 */
function toutify_discover(): array
{
    return toutify_cached_json('toutify_discovery', toutify_issuer() . '/.well-known/openid-configuration', 3600);
}

function toutify_jwks(): array
{
    $d = toutify_discover();
    $uri = (string)($d['jwks_uri'] ?? (toutify_issuer() . '/.well-known/jwks.json'));
    return toutify_cached_json('toutify_jwks', $uri, 3600);
}

function toutify_cached_json(string $settingKey, string $url, int $ttl): array
{
    $raw = setting($settingKey);
    if ($raw !== '') {
        $c = json_decode($raw, true);
        if (is_array($c) && (int)($c['at'] ?? 0) + $ttl > time() && is_array($c['body'] ?? null)) {
            return $c['body'];
        }
    }
    [$code, $body] = toutify_http('GET', $url, null, [], 2);
    if ($code === 200 && is_array($body)) {
        setting_save($settingKey, json_encode(['at' => time(), 'body' => $body]));
        return $body;
    }
    // A stale copy beats no copy: a provider that is briefly down should not
    // take sign-in down with it.
    if ($raw !== '') {
        $c = json_decode($raw, true);
        if (is_array($c['body'] ?? null)) return $c['body'];
    }
    return [];
}

function toutify_endpoint(string $name, string $fallbackPath): string
{
    $d = toutify_discover();
    $v = (string)($d[$name] ?? '');
    return $v !== '' ? $v : toutify_issuer() . $fallbackPath;
}

// ---------------------------------------------------------------------------
// JWT
// ---------------------------------------------------------------------------

function toutify_b64url_decode(string $s): string
{
    $s = strtr($s, '-_', '+/');
    $pad = strlen($s) % 4;
    if ($pad) $s .= str_repeat('=', 4 - $pad);
    return (string)base64_decode($s, true);
}

function toutify_b64url_encode(string $s): string
{
    return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
}

/** A JWK (RSA or EC) as a PEM public key, for openssl_verify. */
function toutify_jwk_to_pem(array $jwk): ?string
{
    if (($jwk['kty'] ?? '') === 'RSA') {
        $n = toutify_b64url_decode((string)($jwk['n'] ?? ''));
        $e = toutify_b64url_decode((string)($jwk['e'] ?? ''));
        if ($n === '' || $e === '') return null;
        $der = toutify_der_seq(
            toutify_der_int($n) . toutify_der_int($e));
        // SubjectPublicKeyInfo wrapper for rsaEncryption
        $algo = toutify_der_seq(
            "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01" . "\x05\x00");
        $spki = toutify_der_seq($algo . toutify_der_bitstring($der));
        return "-----BEGIN PUBLIC KEY-----\n"
             . chunk_split(base64_encode($spki), 64, "\n")
             . "-----END PUBLIC KEY-----\n";
    }
    return null;   // EC keys are not issued by Toutify; add here if that changes
}

function toutify_der_len(int $n): string
{
    if ($n < 0x80) return chr($n);
    $b = ltrim(pack('N', $n), "\x00");
    return chr(0x80 | strlen($b)) . $b;
}
function toutify_der_seq(string $body): string { return "\x30" . toutify_der_len(strlen($body)) . $body; }
function toutify_der_int(string $bytes): string
{
    $bytes = ltrim($bytes, "\x00");
    if ($bytes === '') $bytes = "\x00";
    if (ord($bytes[0]) & 0x80) $bytes = "\x00" . $bytes;   // keep it positive
    return "\x02" . toutify_der_len(strlen($bytes)) . $bytes;
}
function toutify_der_bitstring(string $body): string
{
    return "\x03" . toutify_der_len(strlen($body) + 1) . "\x00" . $body;
}

/**
 * Verify a JWT against Toutify's published keys and return its claims.
 *
 * Returns [claims, ''] or [null, why]. Every failure says what failed, because
 * "invalid token" during a sign-in problem is worth nothing to whoever is
 * reading the log at the time.
 */
function toutify_verify_jwt(string $jwt, array $expect = []): array
{
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return [null, 'not a three-part JWT'];
    [$h64, $p64, $s64] = $parts;

    $header = json_decode(toutify_b64url_decode($h64), true);
    $claims = json_decode(toutify_b64url_decode($p64), true);
    if (!is_array($header) || !is_array($claims)) return [null, 'header or payload is not JSON'];

    $alg = (string)($header['alg'] ?? '');
    if ($alg !== 'RS256') return [null, "unexpected alg '$alg' (only RS256 is accepted)"];
    if (isset($expect['typ']) && (string)($header['typ'] ?? '') !== $expect['typ']) {
        return [null, "unexpected typ '" . (string)($header['typ'] ?? '') . "'"];
    }

    $jwks = toutify_jwks();
    $kid  = (string)($header['kid'] ?? '');
    $pem  = null;
    foreach (($jwks['keys'] ?? []) as $jwk) {
        if ($kid !== '' && (string)($jwk['kid'] ?? '') !== $kid) continue;
        $pem = toutify_jwk_to_pem($jwk);
        if ($pem !== null) break;
    }
    if ($pem === null) return [null, $kid === '' ? 'no usable signing key published' : "no published key with kid '$kid'"];

    $ok = openssl_verify($h64 . '.' . $p64, toutify_b64url_decode($s64), $pem, OPENSSL_ALGO_SHA256);
    if ($ok !== 1) return [null, 'signature does not verify'];

    $skew = 60;
    $now  = time();
    if ((string)($claims['iss'] ?? '') !== toutify_issuer()) return [null, 'issuer mismatch'];
    $aud = $claims['aud'] ?? '';
    $aud = is_array($aud) ? $aud : [$aud];
    if (!in_array(toutify_client_id(), $aud, true)) return [null, 'audience mismatch'];
    if (isset($claims['exp']) && $now > (int)$claims['exp'] + $skew) return [null, 'token has expired'];
    if (isset($claims['iat']) && (int)$claims['iat'] > $now + $skew)  return [null, 'token is issued in the future'];
    if (isset($expect['nonce']) && (string)($claims['nonce'] ?? '') !== $expect['nonce']) return [null, 'nonce mismatch'];

    return [$claims, ''];
}

// ---------------------------------------------------------------------------
// The session register
// ---------------------------------------------------------------------------

function toutify_sessions_ready(): bool
{
    static $ok = null;
    if ($ok === null) $ok = table_exists('toutify_sessions');
    return $ok;
}

/** Remember that this browser's session belongs to a Toutify sub and sid. */
function toutify_session_register(string $sessionId, ?string $sid, string $sub): void
{
    if (!toutify_sessions_ready()) return;
    q('INSERT INTO toutify_sessions (session_id, sid, sub, created_at) VALUES (?,?,?,?)
       ON DUPLICATE KEY UPDATE sid = VALUES(sid), sub = VALUES(sub), created_at = VALUES(created_at), revoked_at = NULL',
      [$sessionId, $sid !== '' ? $sid : null, $sub, time()]);
}

/**
 * Mark sessions ended. With a sid, just that one browser; without, every
 * session that sub has anywhere. Returns how many rows were marked.
 */
function toutify_session_revoke(string $sub, ?string $sid = null): int
{
    if (!toutify_sessions_ready()) return 0;
    if ($sid !== null && $sid !== '') {
        return q('UPDATE toutify_sessions SET revoked_at = ? WHERE sub = ? AND sid = ? AND revoked_at IS NULL',
                 [time(), $sub, $sid])->rowCount();
    }
    return q('UPDATE toutify_sessions SET revoked_at = ? WHERE sub = ? AND revoked_at IS NULL',
             [time(), $sub])->rowCount();
}

/** Has this browser's session been ended from the other side? */
function toutify_session_revoked(string $sessionId): bool
{
    if (!toutify_sessions_ready()) return false;
    return (int)scalar('SELECT COUNT(*) FROM toutify_sessions WHERE session_id = ? AND revoked_at IS NOT NULL',
                       [$sessionId]) > 0;
}

function toutify_session_forget(string $sessionId): void
{
    if (!toutify_sessions_ready()) return;
    q('DELETE FROM toutify_sessions WHERE session_id = ?', [$sessionId]);
}

// ---------------------------------------------------------------------------
// Small shared helpers
// ---------------------------------------------------------------------------

/** A URL reduced to the part worth comparing: no scheme, no www, no trailing slash. */
function toutify_url_key(?string $url): string
{
    $u = strtolower(trim((string)$url));
    if ($u === '') return '';
    $u = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $u);
    $u = preg_replace('#^www\.#', '', (string)$u);
    return rtrim((string)$u, '/');
}

/** MonsterList's tier for a Toutify tier. See the note in the report. */
function toutify_tier_to_local(?string $tier): ?string
{
    switch (strtolower(trim((string)$tier))) {
        case 'free':    return 'free';
        case 'pro':     return 'pro';
        case 'premium': return 'featured';   // "Featured" in the data, "Premium" on screen
        case 'elite':   return 'featured';   // no MonsterList equivalent; nearest is the top one
    }
    return null;
}

/** Toutify's account_status for a MonsterList user row. */
function toutify_status_from_local(array $user): string
{
    return ($user['status'] ?? 'active') === 'suspended' ? 'suspended' : 'active';
}
