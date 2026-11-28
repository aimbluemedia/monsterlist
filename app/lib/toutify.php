<?php
// ---------------------------------------------------------------------------
// The Toutify membership band that sits above the footer.
//
// One free Toutify account covers every tool, so each tool's site carries the
// same panel listing all of them, with the one you are standing on marked.
//
// ===========================================================================
// ADDRESSES. A product with no url still gets its card — it just is not a
// link yet, because a wrong link looks right until somebody clicks it.
//
// Filled in on the owner's instruction. SearchMonster is .org, which the
// owner gave directly; the other three are .com and were guessed, so the
// pattern is demonstrably not uniform and a guessed one may well be wrong.
// None has been reached from here — contentvendor.com did not resolve from
// this container, which proves nothing either way about the live site but
// does mean nobody has clicked them. Worth one click each after a deploy.
// Official Directories stays empty.
// ===========================================================================
//
// 'key' is matched against TOUTIFY_HERE to decide which card says "you are
// here". That card is never a link — it is this site.
//
// 'blurb' is the full sentence for the band above the footer; 'short' is the
// two or three words the hero strip uses, where six cards share one row and a
// sentence would not fit. Both come from here so the two never drift apart.
// ---------------------------------------------------------------------------

const TOUTIFY_HERE = 'monsterlist';

function toutify_products(): array
{
    return [
        ['key'  => 'leadcrazy',
         'name' => 'LeadCrazy',
         'blurb' => 'Turn visitors into quote requests with the Million Dollar Lead Form.',
         'short' => 'Lead conversion',
         'icon' => 'form',
         'url'  => 'https://leadcrazy.com'],

        ['key'  => 'searchmonster',
         'name' => 'SearchMonster',
         'blurb' => 'Show up when customers ask AI who to hire.',
         'short' => 'AI search & free listing',
         'icon' => 'search',
         'url'  => 'https://searchmonster.org'],

        ['key'  => 'monsterlist',
         'name' => 'MonsterList',
         'blurb' => 'Get listed in directories across the web, free.',
         'short' => 'Free listings everywhere',
         'icon' => 'list',
         'url'  => ''],

        ['key'  => 'promomonster',
         'name' => 'PromoMonster',
         'blurb' => 'Ask every customer for a review, reply to what comes back, show it off.',
         'short' => 'Reviews & reputation',
         'icon' => 'star',
         'url'  => 'https://promomonster.com'],

        ['key'  => 'contentvendor',
         'name' => 'ContentVendor',
         'blurb' => 'Posts and pages written for you, on a schedule.',
         'short' => 'AI posts & content',
         'icon' => 'megaphone',
         'url'  => 'https://contentvendor.com'],

        ['key'  => 'directories',
         'name' => 'Official Directories',
         'blurb' => 'Claim your free spot in official state directories.',
         'short' => 'Free state directory listings',
         'icon' => 'shield',
         'url'  => ''],
    ];
}

/**
 * The little line icon on a product card.
 *
 * Inline SVG rather than an image file or an emoji: it inherits colour from the
 * card, so one set works on the blue band without a second light-on-dark copy,
 * and it costs no extra request. Unknown names draw nothing rather than a
 * broken box.
 */
function toutify_icon(string $name): string
{
    $paths = [
        'form'      => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'list'      => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'star'      => '<path d="M12 3l2.6 5.6 6.4.8-4.7 4.3 1.2 6.3L12 17l-5.5 3 1.2-6.3L3 9.4l6.4-.8z"/>',
        'megaphone' => '<path d="M3 11v2a1 1 0 001 1h3l6 4V6L7 10H4a1 1 0 00-1 1z"/><path d="M17 9a4 4 0 010 6"/>',
        'shield'    => '<path d="M12 3l8 3v6c0 4.4-3.2 7.9-8 9-4.8-1.1-8-4.6-8-9V6z"/>',
    ];
    if (!isset($paths[$name])) return '';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" '
         . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}

// ---------------------------------------------------------------------------
// Which Toutify tools are active.
//
// THERE ARE TWO ANSWERS, and they are not the same question.
//
//   businesses.toutify_sites  — the sites ONE WEBSITE is on, which is what
//                               Toutify now reports in website.sites on
//                               website.joined, website.updated, the
//                               "websites" claim and GET /api/v1/sites/
//                               websites. Per listing. Authoritative.
//
//   users.toutify_tools       — flags against the MEMBER, set by staff by
//                               hand. The older, coarser answer, kept because
//                               it is the only thing a listing that is not
//                               linked to a Toutify website has.
//
// A member can have one website on SearchMonster and another on nothing, so
// the per-listing answer is the true one and the storefront prefers it. The
// member-level flags are the fallback, and stay the whole story for a listing
// with no toutify_website_id.
// ---------------------------------------------------------------------------

/** The four sister tools a member can have active, in display order. */
function toutify_tool_keys(): array
{
    return ['searchmonster', 'contentvendor', 'promomonster', 'leadcrazy'];
}

function toutify_tools_ready(): bool
{
    static $ok = null;
    if ($ok === null) $ok = column_exists('users', 'toutify_tools');
    return $ok;
}

/** Does businesses.toutify_sites exist yet? Code deploys before the SQL runs. */
function toutify_sites_ready(): bool
{
    static $ok = null;
    if ($ok === null) $ok = column_exists('businesses', 'toutify_sites');
    return $ok;
}

/**
 * The service keys out of one website payload's "sites".
 *
 * Toutify sends [{"service": "searchmonster", "name": "SearchMonster"}, ...].
 * A bare list of strings is accepted too, because that is the other shape this
 * would plausibly be given and reading both costs one line.
 *
 * Returns NULL when the payload carries no "sites" at all — which is the case
 * on website.removed and website.deleted — and that null is the difference
 * between "on no sites" and "nobody said", so it must not be flattened to [].
 */
function toutify_sites_from_website(array $w): ?array
{
    if (!array_key_exists('sites', $w) || !is_array($w['sites'])) return null;
    $keys = [];
    foreach ($w['sites'] as $s) {
        $k = strtolower(trim(is_array($s) ? (string)($s['service'] ?? '') : (string)$s));
        if ($k !== '' && !in_array($k, $keys, true)) $keys[] = $k;
    }
    return $keys;
}

/**
 * The service keys stored on a listing, or null when none ever were.
 *
 * Null and [] mean different things here as well: null is a listing nothing
 * has been saved for yet (the column is new, or the backfill has not run),
 * and [] is a website Toutify says is on no sites at all.
 */
function toutify_sites_for(array $b): ?array
{
    $raw = $b['toutify_sites'] ?? null;
    if ($raw === null || trim((string)$raw) === '') return null;
    $v = json_decode((string)$raw, true);
    if (!is_array($v)) return null;
    $keys = [];
    foreach ($v as $k) {
        $k = strtolower(trim((string)$k));
        if ($k !== '' && !in_array($k, $keys, true)) $keys[] = $k;
    }
    return $keys;
}

/** The stored form: a JSON array of service keys. */
function toutify_sites_encode(array $keys): string
{
    return json_encode(array_values($keys));
}

/** Decoded flags for a user row: ['searchmonster' => true, ...]. */
function toutify_tools_for(array $u): array
{
    $raw  = json_decode((string)($u['toutify_tools'] ?? ''), true);
    $out  = [];
    foreach (toutify_tool_keys() as $k) {
        $out[$k] = is_array($raw) && !empty($raw[$k]);
    }
    return $out;
}

function toutify_tool_set(int $userId, string $key, bool $on): void
{
    if (!toutify_tools_ready() || !in_array($key, toutify_tool_keys(), true)) return;
    $u = row('SELECT toutify_tools FROM users WHERE id = ?', [$userId]);
    if (!$u) return;
    $flags = toutify_tools_for($u);
    $flags[$key] = $on;
    q('UPDATE users SET toutify_tools = ? WHERE id = ?',
      [json_encode(array_map('intval', $flags)), $userId]);
}

/**
 * Read tool flags out of a claim set, if one ever carries them.
 *
 * Accepts the two shapes a provider is likely to use — a list of active
 * product keys, or an object of key => bool — under either of the two names
 * this would plausibly be given. Returns null when the claims say nothing,
 * which is the case today and must leave the stored flags alone rather than
 * blanking them.
 */
function toutify_tools_from_claims(array $claims): ?array
{
    foreach (['tools', 'products'] as $name) {
        if (!array_key_exists($name, $claims)) continue;
        $v = $claims[$name];
        if (!is_array($v)) continue;
        $flags = [];
        foreach (toutify_tool_keys() as $k) {
            $flags[$k] = array_is_list($v)
                ? in_array($k, array_map('strval', $v), true)
                : !empty($v[$k]);
        }
        return $flags;
    }
    return null;
}

/**
 * Which tools to show active on one listing's storefront.
 *
 * A listing linked to a Toutify website reports THAT WEBSITE's sites: the
 * storefront is a page about one business, and a member with two websites can
 * easily have one of them on SearchMonster and the other on nothing. Anything
 * read off the member would put the same four answers on both pages.
 *
 * Everything else falls back to the member-level flags, which is what this
 * did before and remains the only answer available for a listing that has no
 * Toutify website behind it.
 *
 * Returns null for an unclaimed listing — there is nobody to report on, and a
 * column of "Not active" against a business with no account at all reads as a
 * judgement of the business rather than a statement about software.
 */
function toutify_tools_for_business(array $b): ?array
{
    if (trim((string)($b['toutify_website_id'] ?? '')) !== '') {
        $sites = toutify_sites_for($b);
        // null, not []: a linked listing we have never been told about keeps
        // the old behaviour rather than flipping four cards off on the
        // strength of a column that has simply not been filled in yet.
        if ($sites !== null) {
            $out = [];
            foreach (toutify_tool_keys() as $k) $out[$k] = in_array($k, $sites, true);
            return $out;
        }
    }

    $ownerId = (int)($b['owner_id'] ?? 0);
    if ($ownerId <= 0) return null;
    $owner = row('SELECT toutify_tools FROM users WHERE id = ?', [$ownerId]);
    return $owner ? toutify_tools_for($owner) : null;
}
