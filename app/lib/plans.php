<?php
// Membership plans: what each tier allows. Prices come from settings so the
// superadmin can change them without a deploy.

function plans(): array
{
    return [
        'free' => [
            'label'        => 'Free',
            'price'        => 0,
            // One listing on Free; paid plans carry as many as you like. 0
            // means unlimited — read it through plan_listing_limit() and the
            // helpers beside it rather than comparing to it directly.
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
            'max_listings' => 0,   // unlimited
            'enhanced'     => true,
            'featured'     => false,
            'analytics'    => true,
            'profile'      => true,
            'concierge'    => false,
            'blurb'        => 'Unlimited listings, each a full storefront: a 1,500-word profile, phone and public email, logo, photo gallery, video, verified badge and analytics — plus the tokens to promote it every month.',
        ],
        'featured' => [
            'label'        => 'Featured',
            'price'        => (float)setting('price_featured_monthly', '49'),
            'max_listings' => 0,   // unlimited
            'enhanced'     => true,
            'featured'     => true,
            'analytics'    => true,
            'profile'      => true,
            'concierge'    => true,
            'blurb'        => 'Unlimited listings with everything in Pro, plus priority placement — and we do the work: one article a month, written for you and posted out across our own channels and yours.',
        ],
    ];
}

function plan_for(array $user): array
{
    $p = plans();
    return $p[$user['plan']] ?? $p['free'];
}

/**
 * What a plan is called out loud.
 *
 * "Featured" is the key everywhere in the data and always will be — renaming a
 * column across a live database to change a word on a screen is a bad trade.
 * "Premium" is the word we use to people. One function, because the same
 * ternary written inline in six views is five chances to disagree with itself.
 */
function plan_public_label(string $plan): string
{
    if ($plan === 'featured') return 'Premium';
    $all = plans();
    return $all[$plan]['label'] ?? ucfirst($plan);
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
    if (plan_listing_limit($b) === 0 && plan_listing_limit($a) > 0) {
        $gains[] = 'As many listings as you like, instead of ' . plan_listings_label($a);
    } elseif (plan_listing_limit($b) > plan_listing_limit($a) && plan_listing_limit($a) > 0) {
        $gains[] = plan_listings_label($b) . ' instead of ' . plan_listings_label($a);
    }
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

/** How many listings a plan allows. 0 means no limit. */
function plan_listing_limit(array $plan): int
{
    return max(0, (int)($plan['max_listings'] ?? 1));
}

/** "1 listing" / "Unlimited listings" — the phrase, so no view counts to zero. */
function plan_listings_label(array $plan): string
{
    $n = plan_listing_limit($plan);
    return $n === 0 ? 'Unlimited listings' : $n . ' listing' . ($n === 1 ? '' : 's');
}

/** Room for another? Unlimited plans always have room. */
function plan_has_room(array $plan, int $current): bool
{
    $n = plan_listing_limit($plan);
    return $n === 0 || $current < $n;
}

/** Does any plan allow more listings than this one? */
function plan_more_listings_exist(array $plan): bool
{
    if (plan_listing_limit($plan) === 0) return false;   // already unlimited
    foreach (plans() as $p) {
        if (plan_listing_limit($p) === 0 || plan_listing_limit($p) > plan_listing_limit($plan)) return true;
    }
    return false;
}

function user_can_add_listing(array $user): bool
{
    return plan_has_room(plan_for($user), user_listing_count((int)$user['id']));
}

/** Keep businesses.tier in sync with the owner's plan (called on plan change). */
function sync_business_tiers(int $userId, string $plan): void
{
    q('UPDATE businesses SET tier = ? WHERE owner_id = ?', [$plan, $userId]);
}

/**
 * The tier a listing should be carrying: its owner's plan, or free when it has
 * no owner.
 *
 * businesses.tier is a copy of users.plan, not a setting of its own. The plan
 * sits on the account and covers every listing it owns, and three separate
 * paths already push it down here — a Stripe payment, a cancellation, and a
 * staff plan change all call sync_business_tiers(). Anything that sets a tier
 * without going through the plan is writing a value the next of those will
 * overwrite, while the listing wears a paid badge and takes paid placement in
 * the meantime. So the tier is derived rather than typed.
 */
function tier_for_owner(?int $ownerId): string
{
    if (!$ownerId) return 'free';
    $plan = (string)scalar('SELECT plan FROM users WHERE id = ?', [(int)$ownerId]);
    return in_array($plan, plan_ladder(), true) ? $plan : 'free';
}
