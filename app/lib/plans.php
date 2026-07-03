<?php
// Membership plans: what each tier allows. Prices come from settings so the
// superadmin can change them without a deploy.

function plans(): array
{
    return [
        'free' => [
            'label'        => 'Free',
            'price'        => 0,
            'max_listings' => 1,
            'enhanced'     => false,  // gallery, video, social links, long description
            'featured'     => false,  // priority placement
            'analytics'    => false,
            'blurb'        => 'Get found. One basic listing with contact info, category and location.',
        ],
        'pro' => [
            'label'        => 'Pro',
            'price'        => (float)setting('price_pro_monthly', '19'),
            'max_listings' => 5,
            'enhanced'     => true,
            'featured'     => false,
            'analytics'    => true,
            'blurb'        => 'A full storefront: photo gallery, video, social links, verified badge and analytics.',
        ],
        'featured' => [
            'label'        => 'Featured',
            'price'        => (float)setting('price_featured_monthly', '49'),
            'max_listings' => 15,
            'enhanced'     => true,
            'featured'     => true,
            'analytics'    => true,
            'blurb'        => 'Everything in Pro, plus priority placement on the homepage and at the top of city and category pages.',
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
