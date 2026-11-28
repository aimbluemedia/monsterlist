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
// Which Toutify tools a member has active.
//
// IMPORTANT, and the reason the toggles start off: Toutify does not currently
// tell us. Its discovery document lists every claim it publishes — sub, name,
// email, phone_number, business_name, website, tier, account_status, websites
// — and not one of them says which products a member has turned on. The
// webhook sends member.* and website.* events, and nothing about products
// either.
//
// So the state is stored here, and two readers are wired to pick it up the
// moment Toutify starts sending it: toutify_tools_from_claims() below, called
// on every sign-in and member webhook, and the product.* webhook types. Until
// then both are inert and staff set the flags by hand on the member page.
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
 * The tool flags of whoever owns a listing, for the public storefront.
 *
 * The storefront is about the business on it, not the person reading it, so
 * the status shown there is the owner's. Returns null for an unclaimed
 * listing — there is nobody to report on, and a column of "Not active"
 * against a business with no account at all reads as a judgement of the
 * business rather than a statement about software.
 */
function toutify_tools_for_business(array $b): ?array
{
    $ownerId = (int)($b['owner_id'] ?? 0);
    if ($ownerId <= 0) return null;
    $owner = row('SELECT toutify_tools FROM users WHERE id = ?', [$ownerId]);
    return $owner ? toutify_tools_for($owner) : null;
}
