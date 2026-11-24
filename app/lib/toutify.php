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
// ContentVendor, PromoMonster and LeadCrazy were filled in on the owner's
// instruction. They have NOT been reached from here: contentvendor.com did
// not resolve from this container, which proves nothing either way about the
// live site, but does mean nobody has clicked these. Worth one click each
// after a deploy. SearchMonster and Official Directories stay empty.
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
         'url'  => ''],

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
