<?php
// ---------------------------------------------------------------------------
// Blocklist + duplicate-domain checks.
//
// Two separate protections that both key off a listing's website:
//   * blocklist — emails staff have barred from signing up, and domains barred
//     from being submitted. Filled from the Listings page ("Reject + block")
//     or by hand at /superadmin/blocked.
//   * duplicate detection — the same domain cannot be listed twice, so one
//     business cannot get several entries by signing up under new emails.
//
// Staff bypass both: they are the ones curating the list, and they need to be
// able to fix a listing that trips its own rules.
// ---------------------------------------------------------------------------

/**
 * Reduce a website or pasted URL to a bare comparable host: no scheme, no
 * "www.", no port, no path, lowercased. Returns null if nothing usable is left.
 *
 * "https://WWW.Acme.com:8080/about" and "acme.com" both become "acme.com".
 */
function normalize_domain(?string $url): ?string
{
    $url = trim((string)$url);
    if ($url === '') return null;
    $host = preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $url);
    $host = explode('/', $host)[0];
    $host = explode('?', $host)[0];
    $host = explode('@', $host);          // strip any user:pass@ prefix
    $host = end($host);
    $host = explode(':', $host)[0];       // strip :port
    $host = preg_replace('#^www\.#i', '', $host);
    $host = strtolower(trim($host, ". \t\n\r"));
    // Unicode domains are real, but DNS only carries ASCII. Fold café.com to
    // xn--caf-dma.com so the two are one account rather than two, and so the
    // shape check below has something it can judge. Where the intl extension
    // is missing — shared hosting sometimes leaves it out — the name stays as
    // typed and domain_is_valid() will turn it down, which is the honest
    // outcome: better refused than accepted and never matched again.
    if (preg_match('/[^\x20-\x7e]/', $host) && function_exists('idn_to_ascii')) {
        $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        if (is_string($ascii) && $ascii !== '') $host = strtolower($ascii);
    }
    // Must look like a hostname: at least one dot, no spaces, and inside the
    // 253 characters a hostname is allowed to be. Over-length is refused
    // rather than trimmed to fit — cutting the tail off a name invents a
    // different one, and a shortened domain passes every check afterwards
    // while matching nothing that exists.
    if ($host === '' || strpos($host, '.') === false || preg_match('/\s/', $host)) return null;
    if (strlen($host) > 253) return null;
    return $host;
}

/**
 * Is this a well-formed hostname?
 *
 * Shape only — it says nothing about whether the domain exists or resolves.
 * normalize_domain() gets a string into the right form; this decides whether
 * the result is a name at all, which "a..b.com", "-bad.com" and "trailing-.com"
 * are not, however reasonable they look at a glance.
 */
function domain_is_valid(?string $host): bool
{
    $host = (string)$host;
    if ($host === '' || strlen($host) > 253) return false;
    $labels = explode('.', $host);
    if (count($labels) < 2) return false;
    foreach ($labels as $label) {
        // 63 characters per label, letters/digits/hyphen, and never a hyphen at
        // either end.
        if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/i', $label)) return false;
    }
    // The last label is the TLD: letters, or the xn-- form a unicode one folds
    // to. Digits there would make it an IP address, which is not a website.
    return (bool)preg_match('/^([a-z]{2,63}|xn--[a-z0-9-]{2,59})$/i', (string)end($labels));
}

/** The domain part of an email address, lowercased. */
function email_domain(?string $email): ?string
{
    $at = strrpos((string)$email, '@');
    if ($at === false) return null;
    $d = strtolower(trim(substr((string)$email, $at + 1)));
    return $d === '' ? null : $d;
}

function is_blocked_email(?string $email): bool
{
    $email = strtolower(trim((string)$email));
    if ($email === '') return false;
    return (bool)scalar('SELECT 1 FROM blocklist WHERE kind = "email" AND value = ? LIMIT 1', [$email]);
}

/**
 * A host and every parent it could be a subdomain of:
 * "shop.acme.co.uk" -> ["shop.acme.co.uk", "acme.co.uk", "co.uk"].
 *
 * Building these in PHP keeps the lookup a plain IN () of equalities. The
 * alternative — CONCAT/LIKE against every stored row — cannot use the index,
 * and putting a placeholder on the left of LIKE is exactly the kind of thing
 * MySQL and MariaDB disagree about under native prepared statements.
 */
function domain_candidates(string $domain): array
{
    $parts = explode('.', $domain);
    $out   = [];
    for ($i = 0, $n = count($parts) - 1; $i < $n; $i++) {
        $out[] = implode('.', array_slice($parts, $i));
    }
    return $out ?: [$domain];
}

/**
 * Blocked if the exact host is listed, or if it is a subdomain of a listed
 * host — blocking "acme.com" also stops "shop.acme.com".
 */
function is_blocked_domain(?string $domain): bool
{
    $domain = normalize_domain($domain);
    if ($domain === null) return false;
    $cands = domain_candidates($domain);
    $in    = implode(',', array_fill(0, count($cands), '?'));
    return (bool)scalar(
        "SELECT 1 FROM blocklist WHERE kind = 'domain' AND value IN ($in) LIMIT 1",
        $cands
    );
}

/** Add an entry. Silently keeps the first one if the value is already blocked. */
function blocklist_add(string $kind, ?string $value, ?string $reason = null, ?int $by = null): bool
{
    if (!in_array($kind, ['email', 'domain'], true)) return false;
    $value = $kind === 'email'
        ? strtolower(trim((string)$value))
        : (string)normalize_domain($value);
    if ($value === '') return false;
    if ($kind === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) return false;

    q('INSERT INTO blocklist (kind, value, reason, created_by) VALUES (?,?,?,?)
       ON DUPLICATE KEY UPDATE reason = COALESCE(VALUES(reason), reason)',
      [$kind, mb_substr($value, 0, 190), $reason !== null ? mb_substr($reason, 0, 255) : null, $by]);
    return true;
}

function blocklist_remove(int $id): void
{
    q('DELETE FROM blocklist WHERE id = ?', [$id]);
}

function blocklist_all(): array
{
    return rows('SELECT b.*, u.email AS added_by
                 FROM blocklist b LEFT JOIN users u ON u.id = b.created_by
                 ORDER BY b.kind, b.value');
}

/**
 * Why a domain that already has a listing can't be used again, phrased for the
 * person typing it. A rejected listing keeps blocking the domain — that is the
 * point of rejecting it — so say so rather than claiming it is still in review.
 */
function domain_taken_message(array $dupe, string $domain): string
{
    if ($dupe['status'] === 'live') {
        return $domain . ' is already listed as "' . $dupe['name']
             . '". If that is your business, claim it rather than adding it again.';
    }
    if ($dupe['status'] === 'rejected') {
        return $domain . ' was reviewed and not accepted for this directory. '
             . 'If you think that was a mistake, please contact us.';
    }
    return $domain . ' has already been submitted and is awaiting review. You only need to submit it once.';
}

/**
 * An existing account for this domain, or null.
 *
 * Matches in both directions so subdomains cannot be used to open a second
 * account for one business: signing up as shop.acme.com collides with an
 * existing acme.com, and vice versa. Sibling subdomains do NOT collide, so
 * separate shops on a shared host (mystore.shopify.com vs otherstore...)
 * are still independent.
 */
function account_with_domain(?string $domain): ?array
{
    $domain = normalize_domain($domain);
    if ($domain === null) return null;
    // Exact match or an ancestor of the new domain, via plain equality...
    $cands = domain_candidates($domain);
    $in    = implode(',', array_fill(0, count($cands), '?'));
    // ...or an existing account that is itself a subdomain of the new domain.
    // Only this side needs LIKE, with the placeholder in its normal position.
    $params   = $cands;
    $params[] = '%.' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $domain);
    return row(
        "SELECT id, email, website FROM users
          WHERE website IS NOT NULL AND website <> ''
            AND (website IN ($in) OR website LIKE ?)
          LIMIT 1",
        $params
    );
}

/**
 * Everything about a listing that can be blocked: the owner account's email,
 * the listing's own contact email, and its website domain.
 */
function listing_block_targets(array $biz): array
{
    $out = [];
    if (!empty($biz['owner_id'])) {
        $owner = row('SELECT email FROM users WHERE id = ?', [(int)$biz['owner_id']]);
        if ($owner && !empty($owner['email'])) $out[] = ['email', strtolower($owner['email'])];
    }
    if (!empty($biz['email'])) $out[] = ['email', strtolower((string)$biz['email'])];
    $domain = normalize_domain($biz['website'] ?? null);
    if ($domain !== null) $out[] = ['domain', $domain];

    // De-duplicate: owner and contact email are often the same address.
    $seen = [];
    return array_values(array_filter($out, function ($t) use (&$seen) {
        $k = $t[0] . ':' . $t[1];
        if (isset($seen[$k])) return false;
        $seen[$k] = true;
        return true;
    }));
}

/** Block a rejected listing's emails and domain. Returns what was blocked. */
function listing_block(array $biz, ?int $by = null): array
{
    // Plain quotes: this string is stored, and a install whose MySQL connection
    // is not utf8mb4 would mangle typographic ones into mojibake on the way in.
    $reason = 'Listing "' . mb_substr((string)$biz['name'], 0, 120) . '" rejected';
    $done   = [];
    foreach (listing_block_targets($biz) as [$kind, $value]) {
        if (blocklist_add($kind, $value, $reason, $by)) $done[] = $value;
    }
    return $done;
}

/** Lift the blocks a rejection created. Returns what was unblocked. */
function listing_unblock(array $biz): array
{
    $done = [];
    foreach (listing_block_targets($biz) as [$kind, $value]) {
        $st = q('DELETE FROM blocklist WHERE kind = ? AND value = ?', [$kind, $value]);
        if ($st->rowCount() > 0) $done[] = $value;
    }
    return $done;
}

/**
 * Another listing already using this domain, or null. $exceptId skips the
 * listing being edited so saving it does not collide with itself.
 */
function listing_with_domain(?string $website, int $exceptId = 0): ?array
{
    $domain = normalize_domain($website);
    if ($domain === null) return null;
    // Compare on the stored value with scheme and www stripped in SQL, so an
    // old row saved as "https://www.acme.com/" still matches "acme.com".
    return row(
        "SELECT id, name, status, slug FROM businesses
          WHERE id <> ?
            AND website IS NOT NULL AND website <> ''
            AND TRIM(TRAILING '/' FROM
                  SUBSTRING_INDEX(
                    REPLACE(REPLACE(REPLACE(LOWER(website),'https://',''),'http://',''),'www.',''),
                    '/', 1)) = ?
          LIMIT 1",
        [$exceptId, $domain]
    );
}
