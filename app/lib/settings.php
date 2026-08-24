<?php
// Site settings stored in the `settings` table, cached per request.

/**
 * The per-request cache, reachable by reference so a save can correct it.
 *
 * Without this, setting_save() wrote to the database and left the cache holding
 * the old value, so anything that saved a setting and then read it back in the
 * same request got the stale one. Every caller today happens to redirect
 * straight after saving, which hid it — but "correct only because nobody reads
 * it yet" is not a property worth relying on.
 */
function &settings_cache(): ?array
{
    static $cache = null;
    return $cache;
}

function settings_all(): array
{
    $cache = &settings_cache();
    if ($cache === null) {
        $cache = [];
        foreach (rows('SELECT name, value FROM settings') as $r) {
            $cache[$r['name']] = $r['value'];
        }
    }
    return $cache;
}

function setting(string $name, string $default = ''): string
{
    return settings_all()[$name] ?? $default;
}

function setting_save(string $name, string $value): void
{
    q('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$name, $value]);
    $cache = &settings_cache();
    if (is_array($cache)) $cache[$name] = $value;
}
