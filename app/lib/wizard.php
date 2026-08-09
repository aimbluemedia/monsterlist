<?php
// ---------------------------------------------------------------------------
// Listing setup wizard: services → social → reviews.
//
// Runs straight after a listing is created, and is reachable again later from
// "My listings". Every step is skippable — a listing is complete without any
// of it, and a member who wants to get on with their day should be able to.
// ---------------------------------------------------------------------------

/** How many services a listing may show. */
const WIZ_MAX_SERVICES = 7;
/** How many AI suggestions to offer to choose from. */
const WIZ_SUGGESTIONS  = 10;

/** The wizard steps, in order: [slug, label]. */
function wizard_steps(): array
{
    return [
        ['services', 'Services'],
        ['social',   'Social'],
        ['reviews',  'Reviews'],
    ];
}

/**
 * Every social / content link a listing can carry, in display order.
 *
 * One list, used by the member form, the staff form and the AI post-processor.
 * It used to be spelled out in four files, and adding Reddit meant editing all
 * four — which is exactly how one of them got missed.
 */
function social_nets(): array
{
    return [
        'facebook'  => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok'    => 'TikTok',
        'youtube'   => 'YouTube',
        'pinterest' => 'Pinterest',
        'linkedin'  => 'LinkedIn',
        'x'         => 'X',
        'reddit'    => 'Reddit',
        'threads'   => 'Threads',
        'blog'      => 'Blog',
    ];
}

/** Networks on the social step, in the order the member asked for them. */
function wizard_socials(): array
{
    return [
        'facebook'  => ['Facebook',  'https://facebook.com/yourpage'],
        'instagram' => ['Instagram', 'https://instagram.com/yourhandle'],
        'youtube'   => ['YouTube',   'https://youtube.com/@yourchannel'],
        'tiktok'    => ['TikTok',    'https://tiktok.com/@yourhandle'],
        'reddit'    => ['Reddit',    'https://reddit.com/r/yourcommunity'],
        'pinterest' => ['Pinterest', 'https://pinterest.com/yourprofile'],
        'linkedin'  => ['LinkedIn',  'https://linkedin.com/company/yourcompany'],
    ];
}

/** Review sites on the reviews step. */
function wizard_reviews(): array
{
    return [
        'trustpilot'  => ['Trustpilot',              'https://trustpilot.com/review/yoursite.com'],
        'google'      => ['Google',                  'https://g.page/your-business'],
        'yelp'        => ['Yelp',                    'https://yelp.com/biz/your-business'],
        'tripadvisor' => ['Tripadvisor',             'https://tripadvisor.com/your-listing'],
        'facebook'    => ['Facebook Recommendations','https://facebook.com/yourpage/reviews'],
        'applemaps'   => ['Apple Maps',              'https://maps.apple.com/place?q=your-business'],
        'g2'          => ['G2',                      'https://g2.com/products/your-product/reviews'],
        'capterra'    => ['Capterra',                'https://capterra.com/p/your-product'],
        'trustradius' => ['TrustRadius',             'https://trustradius.com/products/your-product/reviews'],
    ];
}

/** Where a step should send the member next; null means the wizard is done. */
function wizard_next(string $step): ?string
{
    $slugs = array_column(wizard_steps(), 0);
    $i     = array_search($step, $slugs, true);
    return ($i === false || !isset($slugs[$i + 1])) ? null : $slugs[$i + 1];
}

function wizard_url(string $step, int $bizId): string
{
    return '/account/listings/' . $step . '?id=' . $bizId;
}

/**
 * Service names the AI suggested for this listing, stashed when the autofill
 * ran. Read once and cleared, so a later listing never inherits them.
 */
function wizard_take_suggestions(int $bizId): array
{
    $all = $_SESSION['ai_services'] ?? [];
    if (!is_array($all) || !$all) return [];
    unset($_SESSION['ai_services']);
    return array_slice($all, 0, WIZ_SUGGESTIONS);
}

/** Replace a listing's services with the given labels (max WIZ_MAX_SERVICES). */
function wizard_save_services(int $bizId, array $labels): int
{
    $clean = [];
    foreach ($labels as $label) {
        $label = trim(preg_replace('/\s+/u', ' ', (string)$label));
        if ($label === '') continue;
        $label = mb_substr($label, 0, 80);
        // Case-insensitive de-dupe: "Web design" and "web design" are one service.
        if (in_array(mb_strtolower($label), array_map('mb_strtolower', $clean), true)) continue;
        $clean[] = $label;
        if (count($clean) >= WIZ_MAX_SERVICES) break;
    }
    q('DELETE FROM services WHERE business_id = ?', [$bizId]);
    foreach ($clean as $label) {
        q('INSERT INTO services (business_id, name) VALUES (?,?)', [$bizId, $label]);
    }
    return count($clean);
}

/**
 * Merge posted links into a listing's social or review_links JSON column.
 * A field left empty clears that network rather than keeping a stale link.
 */
function wizard_save_links(int $bizId, string $column, array $allowed, array $posted): int
{
    if (!in_array($column, ['social', 'review_links'], true)) return 0; // column name is interpolated
    $out = [];
    foreach ($allowed as $key => $_) {
        $url = clean_url((string)($posted[$key] ?? ''));
        if ($url) $out[$key] = mb_substr($url, 0, 255);
    }
    q("UPDATE businesses SET $column = ? WHERE id = ?", [$out ? json_encode($out) : null, $bizId]);
    return count($out);
}

/** Decode a JSON link column into an array, tolerating null/garbage. */
function wizard_links(?string $json): array
{
    $a = json_decode((string)$json, true);
    return is_array($a) ? $a : [];
}
