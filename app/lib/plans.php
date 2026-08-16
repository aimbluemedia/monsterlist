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
