<?php
// /claim/{businessId} — logged-in members request ownership of an unclaimed listing.

$u   = require_login();
$biz = row(
    'SELECT b.*, ci.name AS city_name FROM businesses b LEFT JOIN cities ci ON ci.id = b.city_id
     WHERE b.id = ? AND b.status = "live"', [(int)$segments[1]]);
if (!$biz) not_found();

if (!empty($biz['owner_id'])) {
    flash_set('info', 'This listing has already been claimed.');
    redirect(business_url_by_id((int)$biz['id']) ?? '/');
}

$existing = row('SELECT * FROM claims WHERE business_id = ? AND user_id = ?', [$biz['id'], $u['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
    csrf_check();
    $msg = mb_substr(post('message'), 0, 1000);
    if (mb_strlen(trim($msg)) < 10) {
        flash_set('error', 'Please tell us a bit more — how are you connected to this business?');
    } else {
        q('INSERT INTO claims (business_id, user_id, message) VALUES (?,?,?)', [$biz['id'], $u['id'], $msg]);
        flash_set('success', 'Claim submitted! Our team will verify it and email you, usually within 24 hours.');
        redirect('/account');
    }
}

$meta = [
    'title'  => 'Claim "' . $biz['name'] . '" — ' . setting('site_name'),
    'robots' => 'noindex',
];
view('claim', compact('meta', 'u', 'biz', 'existing'));
