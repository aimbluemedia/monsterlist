<?php
// /search?q= — full-text search over live businesses.

$qstr = trim((string)($_GET['q'] ?? ''));
$page = page_param();
$list = $qstr !== '' ? search_businesses($qstr, $page) : [];

$site = setting('site_name');
$meta = [
    'title'       => $qstr !== '' ? "\"$qstr\" — search results | $site" : "Search businesses — $site",
    'description' => "Search thousands of trusted local businesses worldwide on $site.",
    'canonical'   => site_url('/search'),
    'robots'      => $qstr !== '' ? 'noindex, follow' : null,
];
$meta = array_filter($meta, fn($v) => $v !== null);

view('search', compact('meta', 'qstr', 'list', 'page'));
