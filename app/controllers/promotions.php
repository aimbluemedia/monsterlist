<?php
// Public promotion feed: /promotions[?channel=youtube][&page=2]
// Plus the click-through counter at /promotions/go/{id}.
$site = setting('site_name');

// /promotions/go/{id} — count the click, then send the visitor to the post.
if (($segments[1] ?? '') === 'go' && isset($segments[2])) {
    $promo = row('SELECT * FROM promotions WHERE id = ? AND status = "live"', [(int)$segments[2]]);
    if (!$promo) not_found();
    promotion_click((int)$promo['id']);

    // Attention paid is attention earned: opening another member's promotion
    // credits tokens, which are what it costs to run one of your own. Once per
    // promotion per day, never for your own, and under a daily ceiling —
    // token_earn_from_view() holds those rules.
    $earned = token_earn_from_view(current_user(), $promo);
    if ($earned > 0) {
        flash_set('success', "+$earned token" . ($earned === 1 ? '' : 's') . ' for taking a look. Thanks for supporting another member.');
    }

    header('Location: ' . $promo['url'], true, 302);
    header('X-Robots-Tag: noindex');
    exit;
}

$channel = (string)($_GET['channel'] ?? '');
if ($channel !== '' && !isset(promo_channels()[$channel])) $channel = '';

$page   = page_param();
$list   = promotions_live($channel, $page);
$total  = promotions_count($channel);
$pages  = max(1, (int)ceil($total / 24));
$counts = [];
foreach (array_keys(promo_channels()) as $ch) $counts[$ch] = promotions_count($ch);

$label = $channel !== '' ? promo_channel_label($channel) : '';
$meta  = [
    'title'       => ($label ? "$label promotions" : 'Live member promotions') . " — $site",
    'description' => "Fresh posts, products, videos and reviews published by $site members. "
                   . "Open one, give it a look, and help another small business get seen.",
    'canonical'   => site_url('/promotions' . ($channel ? '?channel=' . $channel : '')),
];

view('promotions', compact('meta', 'list', 'channel', 'counts', 'total', 'page', 'pages', 'site'));
