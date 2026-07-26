<?php
// ---------------------------------------------------------------------------
// Shared bootstrap for the real-folder entry points (/superadmin, /members,
// /account, /login, ...). Those folders exist so Apache can serve each section
// directly, without depending on mod_rewrite to route virtual paths through
// the front controller.
//
// The caller sets, before requiring this file:
//   $ML_WEB_ROOT   absolute path of the web root (dirname of the folder)
//   $ML_SECTION    URL segment this folder answers to, e.g. 'superadmin'
//   $ML_ALIAS      optional: segment the controller expects instead
//   $ML_SUBPATHS   optional: false to ignore deeper segments (flat routes)
//
// Afterwards $path and $segments are set up exactly as index.php would leave
// them, and the caller requires its controller.
// ---------------------------------------------------------------------------
$bootstrap = null;
foreach ([dirname($ML_WEB_ROOT) . '/app/bootstrap.php', $ML_WEB_ROOT . '/app/bootstrap.php'] as $candidate) {
    if (is_file($candidate)) { $bootstrap = $candidate; break; }
}
if ($bootstrap === null) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>App folder not found</h1><p>Could not find <code>app/bootstrap.php</code>. '
       . 'Run <code>/install-check.php</code> for a full diagnosis.</p>' . str_repeat(' ', 600);
    exit;
}

if (!defined('WEB_ROOT')) define('WEB_ROOT', $ML_WEB_ROOT);
require $bootstrap;

$uri  = parse_url($_SERVER['REQUEST_URI'] ?? "/$ML_SECTION", PHP_URL_PATH) ?: "/$ML_SECTION";
$uri  = rawurldecode($uri);
$path = rtrim($uri, '/') ?: "/$ML_SECTION";

// Strip a literal /index.php the server may expose when there is no rewrite.
$path = preg_replace('#/index\.php$#', '', $path) ?: "/$ML_SECTION";

$segments = explode('/', ltrim($path, '/'));
if (($segments[0] ?? '') !== $ML_SECTION) array_unshift($segments, $ML_SECTION);

if (($ML_SUBPATHS ?? true) === false) {
    $segments = [$ML_SECTION];
} elseif (count($segments) === 1 && isset($_GET['p']) && $_GET['p'] !== '') {
    // No-rewrite fallback: /members/?p=listings/new === /members/listings/new
    foreach (explode('/', trim((string)$_GET['p'], '/')) as $extra) {
        if (preg_match('/^[a-z0-9_-]+$/i', $extra)) $segments[] = $extra;
    }
}

if (!empty($ML_ALIAS)) $segments[0] = $ML_ALIAS;

$path  = '/' . implode('/', $segments);
$first = $segments[0];
