<?php
// ---------------------------------------------------------------------------
// Member intake: accounts created by staff or by an API call, rather than by
// somebody filling in the sign-up form.
//
// The two halves are deliberately separate. Creating the account is cheap,
// instant and safe to do in bulk. Building its listing costs an AI call and a
// fetch of somebody else's website, and is worth looking at before it goes out
// — so it is a button a person presses, one row at a time, not something that
// happens the moment a row arrives.
//
// users.intake_at marks an account as having come in this way, and is what the
// queue is built from. users.intake_note holds the last thing that went wrong
// building the listing, so a failed row explains itself instead of just sitting
// there.
// ---------------------------------------------------------------------------

/** Is the intake schema present? Same graceful-degradation rule as tokens. */
function intake_ready(): bool
{
    static $ok = null;
    if ($ok === null) $ok = column_exists('users', 'intake_at') && column_exists('users', 'intake_note');
    return $ok;
}

/**
 * The API key, created on first use.
 *
 * Kept in settings rather than config.php so it can be rotated from the panel
 * by the person who needs to rotate it, without an FTP client.
 */
function intake_api_key(): string
{
    $key = setting('intake_api_key');
    if ($key === '') {
        $key = bin2hex(random_bytes(24));
        setting_save('intake_api_key', $key);
    }
    return $key;
}

function intake_api_key_rotate(): string
{
    $key = bin2hex(random_bytes(24));
    setting_save('intake_api_key', $key);
    return $key;
}

/** A readable password for an account somebody else will be told about. */
function intake_password(): string
{
    // No l/I/1/O/0: these get read aloud, typed from a screenshot, and copied
    // out of an email by hand.
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $out = '';
    for ($i = 0; $i < 12; $i++) $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    return $out;
}

/**
 * Create one member from email + password + domain.
 *
 * Returns [user array or null, errors array]. The password is NOT stored in
 * readable form anywhere — it is hashed on the way in, and the caller gets the
 * plain one back exactly once to pass on. There is no screen that can show it
 * again later, which is the point.
 */
function intake_create_member(string $email, string $password, string $domain): array
{
    $errors = [];
    $email  = mb_strtolower(trim($email));
    $domain = trim($domain);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '"' . $email . '" is not a valid email address.';
    } elseif (row('SELECT id FROM users WHERE email = ?', [$email])) {
        $errors[] = $email . ' already has an account.';
    }

    // The same two calls the sign-up form makes, rather than a second opinion
    // written here: intake and signup have to agree on what a domain is, or the
    // duplicate checks between them stop matching.
    $host = normalize_domain($domain);
    if ($host === null || !domain_is_valid($host)) {
        $errors[] = '"' . $domain . '" is not a domain name.';
        $host = '';
    }

    if ($password === '') $password = intake_password();
    if (strlen($password) < 8) $errors[] = 'Password for ' . $email . ' must be at least 8 characters.';

    if ($errors) return [null, $errors];

    // The domain doubles as the display name until the member sets one, which
    // is exactly what the sign-up form does. `name` is the narrower column of
    // the two, so it is cut to fit: a display label losing its tail is nothing,
    // where the same domain overflowing the column is a fatal insert.
    q('INSERT INTO users (email, password_hash, name, website, intake_at) VALUES (?,?,?,?,NOW())',
      [$email, password_hash($password, PASSWORD_DEFAULT), mb_substr($host, 0, 140), $host]);

    $user = row('SELECT * FROM users WHERE email = ?', [$email]);
    $user['plain_password'] = $password;   // for this request only, never stored
    return [$user, []];
}

/** Parse the bulk box: one member per line, "email, password, domain". */
function intake_parse_bulk(string $text): array
{
    $rows = [];
    foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        // Comma, tab or semicolon — whichever the spreadsheet it came from used.
        $parts = array_map('trim', preg_split('/\s*[,;\t]\s*/', $line));
        // Two columns means the password was left out, so one gets generated.
        if (count($parts) === 2) $parts = [$parts[0], '', $parts[1]];
        if (count($parts) < 3) { $rows[] = ['error' => 'Could not read "' . $line . '" — expected email, password, domain.']; continue; }
        $rows[] = ['email' => $parts[0], 'password' => $parts[1], 'domain' => $parts[2]];
    }
    return $rows;
}

/**
 * Members added this way that still need something doing, newest first.
 *
 * A queue is work outstanding, so an approved member leaves it: once the
 * listing is live there is nothing left here to press. Rejected ones stay —
 * that listing is decided but the account is not, and somebody still has to
 * delete it or fix it and resubmit.
 *
 * There is no way to see the finished ones from here, deliberately: once a
 * listing is live it is an ordinary listing and an ordinary member, and the
 * Listings and Members pages already hold them better than a second copy on
 * this one would.
 */
function intake_queue(int $limit = 200): array
{
    if (!intake_ready()) return [];
    return rows(
        "SELECT u.id, u.email, u.website, u.intake_at, u.intake_note, u.plan,
                b.id AS business_id, b.name AS business_name, b.status AS business_status,
                b.city_id, b.category_id
           FROM users u
           LEFT JOIN businesses b ON b.owner_id = u.id
          WHERE u.intake_at IS NOT NULL AND (b.id IS NULL OR b.status <> 'live')
          ORDER BY u.intake_at DESC, u.id DESC
          LIMIT " . max(1, $limit));
}

/**
 * Read the member's website with AI and create their listing from it.
 *
 * Always lands as `pending`, never live: this is a machine's reading of
 * somebody else's website, and it goes past a person before the public sees
 * it. A category or city the AI could not work out is left empty rather than
 * guessed — both columns allow it — so the listing still gets made and the
 * gaps are visible in the staff editor.
 *
 * Returns [business id or 0, error string].
 */
function intake_build_listing(array $user): array
{
    if (empty($user['website'])) return [0, 'This member has no domain to read.'];

    $existing = row('SELECT id FROM businesses WHERE owner_id = ?', [(int)$user['id']]);
    if ($existing) return [(int)$existing['id'], ''];   // already built; nothing to redo

    $fields = ai_extract_listing((string)$user['website'], $aiError);
    if ($fields === null) {
        $err = $aiError ?: 'AI could not read that website.';
        q('UPDATE users SET intake_note = ? WHERE id = ?', [mb_substr($err, 0, 255), (int)$user['id']]);
        return [0, $err];
    }
    return [intake_create_listing($user, $fields), ''];
}

/**
 * Turn a set of extracted fields into this member's listing.
 *
 * Separate from the call that fetches them so the mapping — city resolution,
 * the slug, the blank-means-blank rules — can be exercised without a website
 * and an API key on the other end of it.
 */
function intake_create_listing(array $user, array $fields): int
{
    $name = $fields['name'] !== '' ? $fields['name'] : (string)$user['website'];
    $cityId = null;
    if ($fields['country'] !== '' && $fields['city'] !== '') {
        $cityId = resolve_city($fields['country'], (string)$fields['region'], (string)$fields['city']);
    }
    // The slug is unique per city, and a listing with no city yet has no city
    // to be unique within — 0 is the bucket those share until staff place them.
    $slug = unique_business_slug($name, (int)$cityId);

    q('INSERT INTO businesses (owner_id, name, slug, category_id, business_type, city_id, tagline, description,
                               phone, website, email, address, founded, status, tier)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,"pending",?)',
      [(int)$user['id'], $name, $slug,
       $fields['category_id'] !== '' ? $fields['category_id'] : null,
       // Already checked against schema_types() in ai_postprocess.
       !empty($fields['business_type']) ? $fields['business_type'] : null,
       $cityId,
       $fields['tagline'], $fields['description'], $fields['phone'],
       $fields['website'], $fields['email'], $fields['address'],
       $fields['founded'] !== '' ? (int)$fields['founded'] : null,
       (string)$user['plan']]);
    $bizId = (int)db()->lastInsertId();

    // The services the AI found. On the member's own path these are offered as
    // suggestions in the wizard; nobody is here to accept them, so they go
    // straight on, and staff can delete any that are wrong.
    // De-duplicated here as well as in ai_postprocess: this function takes
    // whatever it is handed, and a listing showing "Bread, Cakes, Bread" reads
    // as a broken site rather than a thorough one.
    $seen = [];
    foreach (array_slice((array)($fields['services'] ?? []), 0, 12) as $svc) {
        $svc = mb_substr(trim((string)$svc), 0, 180);
        $key = mb_strtolower($svc);
        if ($svc === '' || isset($seen[$key])) continue;
        $seen[$key] = true;
        q('INSERT INTO services (business_id, name) VALUES (?,?)', [$bizId, $svc]);
    }

    if ($cityId) refresh_city_count((int)$cityId);
    q('UPDATE users SET intake_note = NULL WHERE id = ?', [(int)$user['id']]);
    return $bizId;
}
