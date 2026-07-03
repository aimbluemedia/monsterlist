<?php
// Shared helpers: escaping, slugs, redirects, views, pagination, flash messages.

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

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function not_found(): never
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
