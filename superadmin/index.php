<?php
// ---------------------------------------------------------------------------
// Real-folder entry point for the staff area.
//
// The site normally routes every URL through the front controller (/index.php)
// via .htaccess mod_rewrite. This file is a belt-and-braces entrance so that
// /superadmin/ resolves even on a host where mod_rewrite is unavailable or
// disabled — Apache serves this file directly as the folder's DirectoryIndex.
//
// It performs the same bootstrap as index.php, then hands off to the staff
// controller. Deeper paths (/superadmin/listings, ...) still arrive here when
// the local .htaccess rewrite is active; without it they fall back to
// ?p= (e.g. /superadmin/?p=listings), which the controller understands too.
// ---------------------------------------------------------------------------
$root = dirname(__DIR__);

$bootstrap = null;
foreach ([dirname($root) . '/app/bootstrap.php', $root . '/app/bootstrap.php'] as $candidate) {
    if (is_file($candidate)) { $bootstrap = $candidate; break; }
}
if ($bootstrap === null) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>App folder not found</h1><p>Could not find <code>app/bootstrap.php</code>. '
       . 'Run <code>/install-check.php</code> for a full diagnosis.</p>' . str_repeat(' ', 600);
    exit;
}

define('WEB_ROOT', $root);
require $bootstrap;

$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/superadmin', PHP_URL_PATH) ?: '/superadmin';
$uri  = rawurldecode($uri);
$path = rtrim($uri, '/') ?: '/superadmin';

// Strip a literal /index.php the server may expose when there is no rewrite.
$path = preg_replace('#/index\.php$#', '', $path) ?: '/superadmin';

$segments = explode('/', ltrim($path, '/'));
if (($segments[0] ?? '') !== 'superadmin') array_unshift($segments, 'superadmin');

// No-rewrite fallback: /superadmin/?p=listings/edit behaves like /superadmin/listings/edit
if (count($segments) === 1 && isset($_GET['p']) && $_GET['p'] !== '') {
    foreach (explode('/', trim((string)$_GET['p'], '/')) as $extra) {
        if (preg_match('/^[a-z0-9_-]+$/i', $extra)) $segments[] = $extra;
    }
}

// Bare /superadmin/ from a signed-out visitor: serve the login form here rather
// than redirecting to /superadmin/login, which needs mod_rewrite to resolve.
if (count($segments) === 1 && !is_admin()) $segments[] = 'login';

$path = '/' . implode('/', $segments);

require APP_ROOT . '/controllers/admin.php';
