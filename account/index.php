<?php
// ---------------------------------------------------------------------------
// The member area's old address.
//
// It moved to /members/. This folder stays so that bookmarks, and the links in
// emails sent before the move, land on the page rather than on a 404 — it
// answers with a permanent redirect and nothing else.
//
// Deliberately plain PHP: no bootstrap, no database, no config. A redirect
// that works on a half-finished upload, or while the database is down, is
// worth more than one that shares the application's own failure modes.
// ---------------------------------------------------------------------------

$uri  = (string)($_SERVER['REQUEST_URI'] ?? '/account');
$path = parse_url($uri, PHP_URL_PATH) ?: '/account';
$qs   = parse_url($uri, PHP_URL_QUERY);

// Swap the leading segment and keep everything under it: /account/listings/edit
// becomes /members/listings/edit. The literal /index.php a server without
// rewrites exposes is dropped on the way.
$path = preg_replace('#/index\.php$#', '', $path);
$rest = preg_replace('#^/account#', '', (string)$path);
$to   = '/members' . $rest . ($qs !== null && $qs !== '' ? '?' . $qs : '');

header('Location: ' . $to, true, 301);
exit;
