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
//   /account[...]                  member area
//   /admin[...]                    admin + superadmin
//   /stripe/checkout|webhook|portal
//   /sitemap.xml /sitemap-{n}.xml  dynamic sitemaps
//   /out/{id}                      tracked click-through to a business website
//   /{country}[/{region}][/{city}][/{business}]   geo pages (catch-all, last)
// ---------------------------------------------------------------------------
require dirname(__DIR__) . '/app/bootstrap.php';

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

    case $first === 'admin':
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
