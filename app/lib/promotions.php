<?php
// ---------------------------------------------------------------------------
// The promotion engine. A promotion is a link a member already published
// somewhere else and wants the network to see. Staff approve it, then it runs
// in the public feed at /promotions.
// ---------------------------------------------------------------------------

/** Channel key => [label, sprite icon id]. Order drives the filter row. */
function promo_channels(): array
{
    return [
        'blog'      => ['Blog post', 'blog'],
        'product'   => ['Product',   'prod'],
        'service'   => ['Service',   'serv'],
        'review'    => ['Review',    'star'],
        'youtube'   => ['YouTube',   'yt'],
        'facebook'  => ['Facebook',  'fb'],
        'instagram' => ['Instagram', 'ig'],
        'tiktok'    => ['TikTok',    'tt'],
        'reddit'    => ['Reddit',    'rd'],
        'pinterest' => ['Pinterest', 'pt'],
        'other'     => ['Link',      'link'],
    ];
}

function promo_channel_label(string $key): string
{
    return promo_channels()[$key][0] ?? 'Link';
}

function promo_channel_icon(string $key): string
{
    return promo_channels()[$key][1] ?? 'link';
}

/** Best-guess channel from the URL, so members rarely have to pick. */
function promo_guess_channel(string $url): string
{
    $host = strtolower((string)parse_url($url, PHP_URL_HOST));
    $map = [
        'youtube.'   => 'youtube',   'youtu.be'   => 'youtube',
        'facebook.'  => 'facebook',  'fb.com'     => 'facebook',
        'instagram.' => 'instagram', 'tiktok.'    => 'tiktok',
        'reddit.'    => 'reddit',    'pinterest.' => 'pinterest',
    ];
    foreach ($map as $needle => $channel) {
        if (strpos($host, $needle) !== false) return $channel;
    }
    return 'other';
}

/** Live feed, newest first. $channel filters; '' means everything. */
function promotions_live(string $channel = '', int $page = 1, int $perPage = 24): array
{
    $offset = max(0, ($page - 1) * $perPage);
    $where  = 'p.status = "live"';
    $args   = [];
    if ($channel !== '' && isset(promo_channels()[$channel])) {
        $where .= ' AND p.channel = ?';
        $args[] = $channel;
    }
    // Paid promotions carry extra "freshness": a Featured post sorts as though
    // it were published feed_boost_featured days later than it was. That buys
    // real exposure — the thing a paid membership is actually selling — without
    // pinning paid posts above every free one for ever, which would leave free
    // members no reason to take part and no feed worth paying to be in.
    $boostPro = max(0, (int)setting('feed_boost_pro', '7'));
    $boostFea = max(0, (int)setting('feed_boost_featured', '14'));
    $rank = "DATE_ADD(p.created_at, INTERVAL CASE b.tier
                 WHEN 'featured' THEN $boostFea WHEN 'pro' THEN $boostPro ELSE 0 END DAY)";

    return rows(
        "SELECT p.*, b.name AS business_name, b.slug AS business_slug, b.logo_url,
                b.tier, b.verified, ci.name AS city_name, c.label AS category_label,
                co.code AS country_code, r.slug AS region_slug, ci.slug AS city_slug
         FROM promotions p
         JOIN businesses b   ON b.id = p.business_id
         LEFT JOIN cities ci ON ci.id = b.city_id
         LEFT JOIN regions r ON r.id = ci.region_id
         LEFT JOIN countries co ON co.code = ci.country_code
         LEFT JOIN categories c ON c.id = b.category_id
         WHERE $where
         ORDER BY $rank DESC, p.id DESC
         LIMIT $perPage OFFSET $offset", $args);
}

function promotions_count(string $channel = ''): int
{
    if ($channel !== '' && isset(promo_channels()[$channel])) {
        return (int)scalar('SELECT COUNT(*) FROM promotions WHERE status = "live" AND channel = ?', [$channel]);
    }
    return (int)scalar('SELECT COUNT(*) FROM promotions WHERE status = "live"');
}

/** Promotions a member has submitted, any status. */
function promotions_for_user(int $userId): array
{
    return rows(
        'SELECT p.*, b.name AS business_name
         FROM promotions p JOIN businesses b ON b.id = p.business_id
         WHERE p.user_id = ? ORDER BY p.created_at DESC', [$userId]);
}

function promotion_owned(int $id, int $userId): ?array
{
    return row('SELECT * FROM promotions WHERE id = ? AND user_id = ?', [$id, $userId]);
}

/** Count a click-through. Cheap and fire-and-forget. */
function promotion_click(int $id): void
{
    q('UPDATE promotions SET clicks = clicks + 1 WHERE id = ?', [$id]);
}
