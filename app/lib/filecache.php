<?php
// ---------------------------------------------------------------------------
// A small file cache for answers fetched from other people's servers.
//
// WHY NOT THE settings TABLE, which is where the rest of the Toutify caching
// lives: settings_all() reads EVERY row into memory on the first setting()
// call of every request. One row per website address would put a couple of
// thousand JSON blobs behind every page on the site, including pages that
// have nothing to do with Toutify. A cache keyed per listing has to be read
// one key at a time, and that is what this does.
//
// Files rather than a table because this site is deployed by pulling from
// GitHub and pasting SQL into phpMyAdmin by hand: a cache that needs a
// migration before it works is a cache that spends a week not working.
//
// Everything here fails quietly. A cache that cannot write is a slow site,
// not a broken one, and nothing stored in it is worth an error page.
// ---------------------------------------------------------------------------

/** The writable directory, or null when there isn't one. Decided once. */
function cache_dir(): ?string
{
    static $dir = null, $looked = false;
    if ($looked) return $dir;
    $looked = true;

    // The repo directory first, so the cache is visible to whoever is
    // debugging; the system temp directory when the host will not have it.
    foreach ([BASE_ROOT . '/storage/cache', sys_get_temp_dir() . '/monsterlist-cache'] as $c) {
        if (!is_dir($c)) @mkdir($c, 0775, true);
        if (is_dir($c) && is_writable($c)) { $dir = $c; break; }
    }
    return $dir;
}

/** The file one key lives in. Hashed, so any key is a safe filename. */
function cache_path(string $key): ?string
{
    $d = cache_dir();
    return $d === null ? null : $d . '/' . sha1($key) . '.json';
}

/**
 * The cached value, or null when there is none or it has aged out.
 *
 * $ttl of 0 means "however old it is" — the stale read a caller falls back to
 * when the far end cannot be reached.
 */
function cache_get(string $key, int $ttl): ?array
{
    $p = cache_path($key);
    if ($p === null || !is_file($p)) return null;
    if ($ttl > 0 && filemtime($p) + $ttl <= time()) return null;
    $raw = @file_get_contents($p);
    if ($raw === false) return null;
    $v = json_decode($raw, true);
    return is_array($v) ? $v : null;
}

/** Store a value. Written to a temporary file first, so a reader never sees half of one. */
function cache_put(string $key, array $value): void
{
    $p = cache_path($key);
    if ($p === null) return;
    $tmp = $p . '.' . getmypid() . '.tmp';
    if (@file_put_contents($tmp, json_encode($value)) === false) return;
    if (!@rename($tmp, $p)) @unlink($tmp);
}

/**
 * Delete entries older than $olderThan seconds, at most $max of them.
 *
 * Called on a small fraction of writes rather than on a schedule, because
 * this host has no cron this code can rely on. The cap keeps that sweep from
 * turning one unlucky page view into a directory walk of thousands of files.
 */
function cache_sweep(int $olderThan = 86400, int $max = 200): void
{
    $d = cache_dir();
    if ($d === null) return;
    $n = 0;
    foreach (glob($d . '/*.json') ?: [] as $f) {
        if ($n >= $max) break;
        if (@filemtime($f) + $olderThan < time()) { @unlink($f); $n++; }
    }
}
