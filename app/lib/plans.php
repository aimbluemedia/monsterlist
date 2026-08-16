<?php
// Membership plans: what each tier allows. Prices come from settings so the
// superadmin can change them without a deploy.

function plans(): array
{
    return [
        'free' => [
            'label'        => 'Free',
            'price'        => 0,
            // Every plan carries one listing. The paid tiers sell what that one
            // listing can DO — a full storefront, more promotion, and the
            // article service — not how many of them you may own.
            'max_listings' => 1,
            // 'enhanced' is the paid-features gate: phone, public email, logo,
            // photo gallery, video, social links and the long description.
            'enhanced'     => false,
            'featured'     => false,  // priority placement
            'analytics'    => false,
            'profile'      => false,  // the long-form Profile section
            'concierge'    => false,  // we write and post it for them
            'blurb'        => 'Get found. One listing with your description, category, location and a link to your website.',
        ],
        'pro' => [
            'label'        => 'Pro',
            'price'        => (float)setting('price_pro_monthly', '19'),
            'max_listings' => 1,
            'enhanced'     => true,
            'featured'     => false,
            'analytics'    => true,
            'profile'      => true,
            'concierge'    => false,
            'blurb'        => 'A full storefront: a 1,500-word profile, phone and public email, logo, photo gallery, video, verified badge and analytics — plus the tokens to promote it every month.',
        ],
        'featured' => [
            'label'        => 'Featured',
            'price'        => (float)setting('price_featured_monthly', '49'),
            'max_listings' => 1,
            'enhanced'     => true,
            'featured'     => true,
            'analytics'    => true,
            'profile'      => true,
            'concierge'    => true,
            'blurb'        => 'Everything in Pro, plus priority placement — and we do the work: one article a month, written for you and posted out across our own channels and yours.',
        ],
    ];
}

function plan_for(array $user): array
{
    $p = plans();
    return $p[$user['plan']] ?? $p['free'];
}

/** The three plan keys, cheapest first. The one place the order is written down. */
function plan_ladder(): array
{
    return ['free', 'pro', 'featured'];
}

/** Where a plan sits on the ladder. Anything unrecognised counts as free. */
function plan_rank(string $plan): int
{
    $i = array_search($plan, plan_ladder(), true);
    return $i === false ? 0 : (int)$i;
}

/**
 * The plans a member on $plan can move up to, in ladder order.
 *
 * Empty for Featured, and the upgrade page shows nothing rather than a card
 * they cannot buy: offering someone an upgrade to what they already pay for is
 * how a page loses their trust in everything else it says.
 */
function plans_above(string $plan): array
{
    $all  = plans();
    $out  = [];
    foreach (array_slice(plan_ladder(), plan_rank($plan) + 1) as $key) {
        if (isset($all[$key])) $out[$key] = $all[$key];
    }
    return $out;
}

/** Days a plan's promotions stay boosted at the top of the feed. */
function plan_feed_boost(string $plan): int
{
    if ($plan === 'free') return 0;
    return (int)setting('feed_boost_' . $plan, $plan === 'pro' ? '7' : '14');
}

/**
 * What moving from one plan to another actually adds, in plain sentences.
 *
 * Built by comparing the two plans rather than written out per pair, so it can
 * only ever promise what plans() and token_rules() really grant — and a price
 * or token figure changed in Settings changes this copy with it.
 */
function plan_gains(string $from, string $to): array
{
    $all = plans();
    if (!isset($all[$from], $all[$to])) return [];
    $a  = $all[$from];
    $b  = $all[$to];
    $ta = token_rules($from);
    $tb = token_rules($to);

    $gains = [];
    if (empty($a['enhanced']) && !empty($b['enhanced'])) {
        $gains[] = 'Your phone number and a public email address on the listing';
        $gains[] = 'Your logo, a photo gallery of up to 6 images, and a video';
        $gains[] = 'The verified badge';
    }
    if (empty($a['profile']) && !empty($b['profile'])) {
        $gains[] = 'A ' . number_format(PROFILE_MAX_WORDS) . '-word Profile section on the storefront';
    }
    if (empty($a['analytics']) && !empty($b['analytics'])) {
        $gains[] = 'Views and website-click analytics for this listing';
    }
    if (empty($a['featured']) && !empty($b['featured'])) {
        $gains[] = 'Priority placement on the homepage, city and category pages';
    }
    if (empty($a['concierge']) && !empty($b['concierge'])) {
        $gains[] = 'One article a month, written for you and posted to our '
                 . implode(', ', article_channels());
        $gains[] = 'That article promoted out to each of your own channels';
    }
    if ($tb['grant'] > $ta['grant']) {
        $gains[] = number_format($tb['grant']) . ' tokens a month instead of '
                 . number_format($ta['grant']);
    }
    if ($tb['promos_max'] > $ta['promos_max']) {
        $gains[] = 'Up to ' . (int)$tb['promos_max'] . ' promotions a month instead of '
                 . (int)$ta['promos_max'];
    }
    if ($tb['earn_view'] > $ta['earn_view']) {
        $gains[] = $tb['earn_view'] . ' tokens for every promotion you open instead of '
                 . $ta['earn_view'];
    }
    if (plan_feed_boost($to) > plan_feed_boost($from)) {
        $gains[] = plan_feed_boost($to) . ' extra days at the top of the promotions feed';
    }
    return $gains;
}

function user_listing_count(int $userId): int
{
    return (int)scalar('SELECT COUNT(*) FROM businesses WHERE owner_id = ? AND status != "rejected"', [$userId]);
}

function user_can_add_listing(array $user): bool
{
    return user_listing_count((int)$user['id']) < plan_for($user)['max_listings'];
}

/** Keep businesses.tier in sync with the owner's plan (called on plan change). */
function sync_business_tiers(int $userId, string $plan): void
{
    q('UPDATE businesses SET tier = ? WHERE owner_id = ?', [$plan, $userId]);
}
