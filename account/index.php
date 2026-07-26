<?php
// ---------------------------------------------------------------------------
// Real-folder entry point for the member area (see superadmin/index.php for
// the full rationale). Guarantees /account/ resolves even without mod_rewrite.
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

$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/account', PHP_URL_PATH) ?: '/account';
$uri  = rawurldecode($uri);
$path = rtrim($uri, '/') ?: '/account';
$path = preg_replace('#/index\.php$#', '', $path) ?: '/account';

$segments = explode('/', ltrim($path, '/'));
if (($segments[0] ?? '') !== 'account') array_unshift($segments, 'account');

// No-rewrite fallback: /account/?p=listings/new
if (count($segments) === 1 && isset($_GET['p']) && $_GET['p'] !== '') {
    foreach (explode('/', trim((string)$_GET['p'], '/')) as $extra) {
        if (preg_match('/^[a-z0-9_-]+$/i', $extra)) $segments[] = $extra;
    }
}

$path = '/' . implode('/', $segments);

require APP_ROOT . '/controllers/account.php';
