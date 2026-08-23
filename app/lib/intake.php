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
    if ($ok === null) {
        $ok = column_exists('users', 'intake_at') && column_exists('users', 'intake_note')
              && table_exists('intake_domains');
    }
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
    // The domain they arrived with is their first queued one. Everything after
    // this — the queue, the Build button, the errors — works off that row, so
    // an account without one would be invisible to the page that made it.
    q('INSERT INTO intake_domains (user_id, domain) VALUES (?,?)', [(int)$user['id'], $host]);
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
 * Domains waiting to become listings, newest first.
 *
 * One row per DOMAIN, not per member: a member can own several websites, and
 * each is its own piece of work. Built when the join was one listing per
 * member, this returned a member twice the moment they had two.
 *
 * A domain leaves the queue when its listing is live. Rejected ones stay —
 * that listing is decided but the domain is not, and somebody still has to
 * drop it or fix it and resubmit.
 */
function intake_queue(int $limit = 200): array
{
    if (!intake_ready()) return [];
    return rows(
        "SELECT d.id, d.user_id, d.domain, d.note, d.created_at, d.business_id,
                u.email, u.name AS member_name, u.plan,
                b.name AS business_name, b.status AS business_status,
                b.city_id, b.category_id
           FROM intake_domains d
           JOIN users u ON u.id = d.user_id
           LEFT JOIN businesses b ON b.id = d.business_id
          WHERE b.id IS NULL OR b.status <> 'live'
          ORDER BY d.created_at DESC, d.id DESC
          LIMIT " . max(1, $limit));
}

/** One queue row. */
function intake_domain(int $id): ?array
{
    if (!intake_ready()) return null;
    return row('SELECT * FROM intake_domains WHERE id = ?', [$id]);
}

/**
 * Queue a domain against an existing member.
 *
 * Returns [row id or 0, error]. The domain goes through the same two calls the
 * sign-up form uses, so intake and signup agree on what a domain is, and it is
 * refused if it is already queued, already an account, or already a listing —
 * all three would end in two storefronts for one business.
 */
function intake_add_domain(int $userId, string $domain, int $byUserId = 0): array
{
    if (!intake_ready()) return [0, 'Member intake is not installed — run database/upgrade-all.sql.'];

    $member = row('SELECT * FROM users WHERE id = ?', [$userId]);
    if (!$member) return [0, 'No such member.'];

    $host = normalize_domain($domain);
    if ($host === null || !domain_is_valid($host)) return [0, '"' . $domain . '" is not a domain name.'];

    if ($existing = row('SELECT d.*, u.email FROM intake_domains d JOIN users u ON u.id = d.user_id WHERE d.domain = ?', [$host])) {
        return [0, $host . ' is already queued for ' . $existing['email'] . '.'];
    }
    if ($dupe = listing_with_domain($host)) {
        return [0, $host . ' is already listed as "' . $dupe['name'] . '".'];
    }
    if ($acct = account_with_domain($host)) {
        if ((int)$acct['id'] !== $userId) return [0, $host . ' is registered to ' . $acct['email'] . '.'];
    }

    q('INSERT INTO intake_domains (user_id, domain, added_by) VALUES (?,?,?)',
      [$userId, $host, $byUserId ?: null]);
    // An account that gained a domain this way is an intake account from now
    // on, whether it started as one or not — that is what puts it in the queue.
    if (empty($member['intake_at'])) q('UPDATE users SET intake_at = NOW() WHERE id = ?', [$userId]);
    return [(int)db()->lastInsertId(), ''];
}

/** Drop a queued domain. Only while it has no listing — that is the point. */
function intake_remove_domain(int $id): bool
{
    $d = intake_domain($id);
    if (!$d || $d['business_id']) return false;
    q('DELETE FROM intake_domains WHERE id = ?', [$id]);
    return true;
}

/**
 * Members a domain can be added to, with what they already own.
 *
 * Everyone, not only intake accounts: staff adding a second website for a
 * member who signed up themselves is the ordinary case, not the exception.
 */
function intake_members(): array
{
    if (!intake_ready()) return [];
    return rows(
        "SELECT u.id, u.email, u.name, u.plan,
                (SELECT COUNT(*) FROM businesses b WHERE b.owner_id = u.id) AS listing_count,
                (SELECT COUNT(*) FROM intake_domains d WHERE d.user_id = u.id AND d.business_id IS NULL) AS queued
           FROM users u
          WHERE u.role = 'member' AND u.status = 'active'
          ORDER BY u.email");
}

/**
 * Find members by email, name, or by a domain they hold.
 *
 * Searching a domain has to reach the member who owns it, which means looking
 * in three places: the account's own domain, the websites on their listings,
 * and anything already queued. Somebody typing a domain into this box does not
 * know or care which of the three it currently lives in.
 */
function intake_find_members(string $q): array
{
    if (!intake_ready()) return [];
    $q = trim($q);
    if ($q === '') return [];
    $like = '%' . $q . '%';
    // A pasted URL is reduced to its host first, so "https://acme.com/about"
    // finds the member holding acme.com.
    $host = normalize_domain($q);
    $hostLike = $host !== null ? '%' . $host . '%' : $like;

    return rows(
        "SELECT u.id, u.email, u.name, u.plan,
                (SELECT COUNT(*) FROM businesses b WHERE b.owner_id = u.id) AS listing_count,
                (SELECT COUNT(*) FROM intake_domains d WHERE d.user_id = u.id AND d.business_id IS NULL) AS queued
           FROM users u
          WHERE u.role = 'member' AND u.status = 'active'
            AND (u.email LIKE ? OR u.name LIKE ? OR u.website LIKE ?
                 OR EXISTS (SELECT 1 FROM businesses b  WHERE b.owner_id = u.id AND b.website LIKE ?)
                 OR EXISTS (SELECT 1 FROM intake_domains d WHERE d.user_id = u.id AND d.domain LIKE ?))
          ORDER BY u.email
          LIMIT 25", [$like, $like, $hostLike, $hostLike, $hostLike]);
}

/**
 * Every domain a member holds, whatever state it is in.
 *
 * Queued rows and listings are two different tables and a member's websites are
 * spread across both — a listing they made themselves has no queue row at all.
 * Showing one without the other is how a domain gets added twice.
 */
function intake_member_domains(int $userId): array
{
    if (!intake_ready()) return [];
    $out = [];

    foreach (rows(
        'SELECT d.*, b.name AS business_name, b.status AS business_status
           FROM intake_domains d
           LEFT JOIN businesses b ON b.id = d.business_id
          WHERE d.user_id = ? ORDER BY d.id DESC', [$userId]) as $d) {
        $out[mb_strtolower((string)$d['domain'])] = [
            'domain'   => $d['domain'],
            'state'    => $d['business_id'] ? (string)$d['business_status'] : 'queued',
            'listing'  => $d['business_name'],
            'biz_id'   => (int)$d['business_id'],
            'note'     => $d['note'],
            'queue_id' => (int)$d['id'],
        ];
    }

    // Listings whose website never went through the queue — anything the member
    // typed in themselves.
    foreach (rows('SELECT id, name, website, status FROM businesses WHERE owner_id = ? ORDER BY id DESC', [$userId]) as $b) {
        $host = normalize_domain($b['website'] ?? null);
        if ($host === null) continue;
        $key = mb_strtolower($host);
        if (isset($out[$key])) continue;
        $out[$key] = [
            'domain'   => $host,
            'state'    => (string)$b['status'],
            'listing'  => $b['name'],
            'biz_id'   => (int)$b['id'],
            'note'     => null,
            'queue_id' => 0,
        ];
    }
    return array_values($out);
}

/**
 * Read a queued domain with AI and create the listing for it.
 *
 * Always lands as `pending`, never live: this is a machine's reading of
 * somebody else's website, and it goes past a person before the public sees
 * it. A category or city the AI could not work out is left empty rather than
 * guessed — both columns allow it — so the listing still gets made and the
 * gaps are visible in the staff editor.
 *
 * Returns [business id or 0, error string].
 */
function intake_build_listing(array $d): array
{
    $user = row('SELECT * FROM users WHERE id = ?', [(int)$d['user_id']]);
    if (!$user) return [0, 'That member no longer exists.'];
    if (empty($d['domain'])) return [0, 'This row has no domain to read.'];
    if (!empty($d['business_id'])) return [(int)$d['business_id'], ''];   // already built

    $fields = ai_extract_listing((string)$d['domain'], $aiError);
    if ($fields === null) {
        $err = $aiError ?: 'AI could not read that website.';
        q('UPDATE intake_domains SET note = ? WHERE id = ?', [mb_substr($err, 0, 255), (int)$d['id']]);
        return [0, $err];
    }
    // Built for THIS domain, not for whatever the account was registered with:
    // a member can own several, and they are different businesses.
    $user['website'] = $d['domain'];
    $bizId = intake_create_listing($user, $fields);
    q('UPDATE intake_domains SET business_id = ?, note = NULL WHERE id = ?', [$bizId, (int)$d['id']]);
    return [$bizId, ''];
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
