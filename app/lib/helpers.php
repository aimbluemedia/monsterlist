<?php
// Shared helpers: escaping, slugs, redirects, views, pagination, flash messages.

// Polyfills so the app runs on PHP 7.4 (str_* helpers are PHP 8.0+)
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

/** HTML-escape. */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** "New York City" -> "new-york-city" (matches the JS slugger the URLs were seeded with). */
function slugify(string $s): string
{
    $s = strtolower(trim($s));
    // transliterate accents when intl/iconv is available
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) $s = $t;
    }
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function site_url(string $path = ''): string
{
    return rtrim($GLOBALS['config']['site_url'], '/') . $path;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function not_found(): void
{
    http_response_code(404);
    $meta = ['title' => 'Page not found — ' . setting('site_name'), 'description' => '', 'robots' => 'noindex'];
    view('pages/404', compact('meta'));
    exit;
}

/** Render a view inside the main layout. $data is extracted into scope. */
function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP_ROOT . '/views/layout/header.php';
    require APP_ROOT . '/views/' . $template . '.php';
    require APP_ROOT . '/views/layout/footer.php';
}

/** Render a bare view (no site chrome) — used by admin layout & standalone pages. */
function view_raw(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP_ROOT . '/views/' . $template . '.php';
}

/** One-shot flash messages. */
function flash_set(string $type, string $msg): void
{
    // Queue each distinct message once. A double-clicked submit button fires
    // two POSTs, and without this the identical message is shown twice on the
    // page they both redirect to.
    foreach ($_SESSION['flash'] ?? [] as $existing) {
        if ($existing['type'] === $type && $existing['msg'] === $msg) return;
    }
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_pull(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Clamp + parse ?page= */
function page_param(): int
{
    return max(1, (int)($_GET['page'] ?? 1));
}

function post(string $key, ?string $default = ''): ?string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/** Star string for ratings, e.g. 4.5 -> "★★★★½" handled in views; here numeric fmt. */
function fmt_rating($r): string
{
    return number_format((float)$r, 1);
}

/**
 * Cut text to at most $max words, appending an ellipsis when anything was
 * dropped. Splits on any run of whitespace so newlines in member-entered
 * descriptions count as word breaks rather than joining two words together.
 */
function words(string $text, int $max, string $ellipsis = '…'): string
{
    $text  = trim(preg_replace('/\s+/u', ' ', $text));
    if ($text === '') return '';
    $parts = explode(' ', $text);
    if (count($parts) <= $max) return $text;
    return rtrim(implode(' ', array_slice($parts, 0, $max)), " ,.;:—-") . $ellipsis;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Basic URL validation for member-supplied links. */
function clean_url(string $url): ?string
{
    $url = trim($url);
    if ($url === '') return null;
    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
}
