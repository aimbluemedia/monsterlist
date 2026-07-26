<?php
// ---------------------------------------------------------------------------
// Front controller. .htaccess rewrites every non-file request here.
// URL map:
//   /                              home
//   /browse                        interactive explorer
//   /search?q=                     full-text search
//   /category/{cat}                category landing page
//   /pricing /about /contact /terms /privacy /add-listing
//   /signup /login /logout /forgot /reset
//   /account[...] /members[...]    member area (same thing, two names)
//   /superadmin[...]                    admin + superadmin
//   /stripe/checkout|webhook|portal
//   /sitemap.xml /sitemap-{n}.xml  dynamic sitemaps
//   /out/{id}                      tracked click-through to a business website
//   /{country}[/{region}][/{city}][/{business}]   geo pages (catch-all, last)
// ---------------------------------------------------------------------------
// Locate the app folder — works whether app/ sits above the web root
// (recommended) or inside it (e.g. everything uploaded into public_html).
$bootstrap = null;
foreach ([dirname(__DIR__) . '/app/bootstrap.php', __DIR__ . '/app/bootstrap.php'] as $candidate) {
    if (is_file($candidate)) { $bootstrap = $candidate; break; }
}
if ($bootstrap === null) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>App folder not found</h1><p>Could not find <code>app/bootstrap.php</code>. '
       . 'Upload the <code>app</code> folder either next to the web root or inside it, '
       . 'or run <code>/install-check.php</code> for a full diagnosis.</p>' . str_repeat(' ', 600);
    exit;
}
define('WEB_ROOT', __DIR__);
require $bootstrap;

$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri  = rawurldecode($uri);
$path = rtrim($uri, '/') ?: '/';

// Normalize: no trailing slashes, lowercase geo URLs get canonical redirects later.
if ($uri !== '/' && substr($uri, -1) === '/') {
    redirect($path === '' ? '/' : $path);
}

$segments = $path === '/' ? [] : explode('/', ltrim($path, '/'));
$first    = $segments[0] ?? '';

$controllers = APP_ROOT . '/controllers';

switch (true) {
    case $path === '/':
        require $controllers . '/home.php';
        break;

    case $path === '/browse':
        require $controllers . '/browse.php';
        break;

    case $path === '/search':
        require $controllers . '/search.php';
        break;

    case $first === 'category' && count($segments) >= 2 && count($segments) <= 5:
        $catSlug = $segments[1];
        require $controllers . '/category.php';
        break;

    case $path === '/llms.txt':
        require $controllers . '/llms.php';
        break;

    case preg_match('#^/([a-f0-9]{32})\.txt$#', $path, $m) === 1 && $m[1] === setting('indexnow_key'):
        header('Content-Type: text/plain');
        exit(setting('indexnow_key'));

    case $first === 'claim' && count($segments) === 2:
        require $controllers . '/claim.php';
        break;

    case in_array($path, ['/pricing', '/about', '/contact', '/terms', '/privacy', '/add-listing'], true):
        require $controllers . '/pages.php';
        break;

    case in_array($path, ['/signup', '/login', '/logout', '/forgot', '/reset'], true):
        require $controllers . '/auth.php';
        break;

    case $first === 'account':
        require $controllers . '/account.php';
        break;

    // /members is a friendlier alias for the same member area.
    case $first === 'members':
        $segments[0] = 'account';
        $path = '/' . implode('/', $segments);
        require $controllers . '/account.php';
        break;

    case $first === 'superadmin':
        require $controllers . '/admin.php';
        break;

    case $first === 'stripe':
        require $controllers . '/payments.php';
        break;

    case $path === '/sitemap.xml' || preg_match('#^/sitemap-[a-z0-9-]+\.xml$#', $path):
        require $controllers . '/sitemap.php';
        break;

    case $first === 'out' && count($segments) === 2:
        require $controllers . '/out.php';
        break;

    case count($segments) >= 1 && count($segments) <= 4 && preg_match('/^[a-z]{2}$/', $first):
        require $controllers . '/geo.php';
        break;

    default:
        not_found();
}
