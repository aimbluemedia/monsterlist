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

/**
 * A page of numbered links.
 *
 * The staff tables all carried a lone "Next →" that appeared only when a page
 * happened to be full — no page numbers, no way back, and no idea how much
 * there was. Worse, a last page of exactly 30 rows still offered Next, and a
 * last page of 29 offered nothing even when you had come from page 4.
 *
 * $url is given the page number and returns the href for it, so each caller
 * keeps its own filters in the link.
 *
 * Returns the window of numbers to draw: first and last are always in it, the
 * current page sits in the middle of a run, and the gaps are marked so a
 * thousand pages do not print a thousand links.
 */
function pager_pages(int $page, int $pages, int $window = 2): array
{
    if ($pages < 1) return [];
    $keep = [1, $pages];
    for ($i = $page - $window; $i <= $page + $window; $i++) {
        if ($i >= 1 && $i <= $pages) $keep[] = $i;
    }
    $keep = array_values(array_unique($keep));
    sort($keep);

    $out  = [];
    $last = 0;
    foreach ($keep as $n) {
        if ($last && $n > $last + 1) $out[] = null;   // a gap, drawn as an ellipsis
        $out[] = $n;
        $last  = $n;
    }
    return $out;
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

/**
 * Trim text to a character budget without cutting a word in half.
 *
 * Meta descriptions are measured in characters — search engines cut around 160
 * — but readers see words, and "…relaxation methods to create p" is what a hard
 * mb_substr() produces. Back off to the last space instead, drop any dangling
 * punctuation, and mark the cut.
 *
 * A whole sentence that already fits is returned untouched, ellipsis and all
 * absent, so short descriptions read as themselves rather than as excerpts.
 */
function meta_excerpt(string $text, int $max = 160, string $ellipsis = '…'): string
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
    if ($text === '' || mb_strlen($text) <= $max) return $text;

    // Reserve a character for the ellipsis so the result still fits the budget.
    $cut   = mb_substr($text, 0, $max - mb_strlen($ellipsis));
    $space = mb_strrpos($cut, ' ');
    // No space at all (one very long word) — keep the hard cut rather than
    // returning nothing.
    if ($space !== false && $space > 0) $cut = mb_substr($cut, 0, $space);
    $cut = rtrim($cut, " ,.;:!?—–-");

    // Ending on "…techniques. The…" reads as a mistake. If the last word is one
    // that only exists to introduce the next one, drop it too.
    $orphans = ['a', 'an', 'the', 'and', 'or', 'but', 'of', 'to', 'in', 'on', 'at',
                'for', 'with', 'from', 'by', 'as', 'is', 'are', 'that', 'this', 'our'];
    $lastSpace = mb_strrpos($cut, ' ');
    if ($lastSpace !== false) {
        $lastWord = mb_strtolower(mb_substr($cut, $lastSpace + 1));
        if (in_array($lastWord, $orphans, true)) {
            $cut = rtrim(mb_substr($cut, 0, $lastSpace), " ,.;:!?—–-");
        }
    }

    return $cut . $ellipsis;
}

/**
 * Cap text at a length, but finish the sentence rather than stopping mid-word.
 *
 * A plain mb_substr() at 300 is why a listing's About text could end
 * "…relaxation methods to create p". Going a little over the limit is better
 * than that: this finds the first sentence ending at or after $soft and cuts
 * just past it, so what is stored always reads as finished prose.
 *
 * $hard is the point where we stop waiting for a full stop — text with no
 * sentence punctuation would otherwise never be capped at all. There, it falls
 * back to a whole-word cut.
 */
function sentence_cap(string $text, int $soft, int $hard = 0): string
{
    $text = trim($text);
    $hard = $hard ?: $soft * 2;
    if ($text === '' || mb_strlen($text) <= $soft) return $text;

    // Byte offset of the soft limit, since preg offsets are in bytes even with /u.
    $from = strlen(mb_substr($text, 0, $soft));

    // A full stop, question or exclamation mark — with any closing quote or
    // bracket — that is followed by whitespace or the end of the text. The
    // lookahead is what stops "ibzzz.com" and "3.5" from counting as sentences.
    if (preg_match('/[.!?]["\'\)\]\x{201D}\x{2019}]*(?=\s|$)/u', $text, $m, PREG_OFFSET_CAPTURE, $from)) {
        $end = $m[0][1] + strlen($m[0][0]);
        $out = rtrim(substr($text, 0, $end));
        if (mb_strlen($out) <= $hard) return $out;
    }

    // No sentence ending within reach: trim to whole words at the ceiling.
    return meta_excerpt($text, $hard);
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Basic URL validation for member-supplied links. */
/**
 * An asset URL that changes when the release does.
 *
 * Without this every release shipped new CSS at the same address, so a browser
 * that had the old one kept using it — and the symptom is the worst kind:
 * current HTML styled by stale rules, which does not look like a caching
 * problem, it looks like the layout is broken. Appending the build number
 * means a new release is a new URL and the question never comes up.
 */
function asset(string $path): string
{
    return $path . '?v=' . (defined('ML_BUILD') ? ML_BUILD : '1');
}

/**
 * A website address as a person would say it: no scheme, no www, no trailing
 * slash. "https://www.bluepeake.com/" becomes "bluepeake.com".
 *
 * Returns '' for anything that is not a usable address, so a caller can fall
 * back to a name rather than printing an empty gap.
 */
function display_host(?string $url): string
{
    $url = trim((string)$url);
    if ($url === '') return '';
    $host = preg_replace('#^(?:[a-z][a-z0-9+.-]*://)?(?:www\.)?#i', '', rtrim($url, '/'));
    return (string)$host;
}

function clean_url(string $url): ?string
{
    $url = trim($url);
    if ($url === '') return null;
    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
}
