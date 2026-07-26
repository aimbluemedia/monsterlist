<?php
// Authentication + role gates. Roles: member < admin < superadmin.

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $id = $_SESSION['uid'] ?? null;
        $user = $id ? row('SELECT * FROM users WHERE id = ? AND status = "active"', [$id]) : null;
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u && in_array($u['role'], ['admin', 'superadmin'], true);
}

function is_superadmin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'superadmin';
}

/**
 * True when $userId is a superadmin and the last one left — the guard that
 * stops the site being locked out of its own control panel.
 */
function last_superadmin(int $userId): bool
{
    $target = row('SELECT role FROM users WHERE id = ?', [$userId]);
    if (!$target || $target['role'] !== 'superadmin') return false;
    return (int)scalar('SELECT COUNT(*) FROM users WHERE role = "superadmin"') <= 1;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/account';
        redirect('/login');
    }
    return $u;
}

function require_admin(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/superadmin';
        redirect('/superadmin/login');
    }
    if (!in_array($u['role'], ['admin', 'superadmin'], true)) {
        http_response_code(403);
        exit('Forbidden');
    }
    return $u;
}

function require_superadmin(): array
{
    $u = require_login();
    if ($u['role'] !== 'superadmin') {
        http_response_code(403);
        exit('Forbidden');
    }
    return $u;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    q('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Simple brute-force guard: max 8 failed attempts per IP per 15 minutes. */
function login_throttled(): bool
{
    $n = (int)scalar(
        'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)',
        [client_ip()]
    );
    return $n >= 8;
}

function record_failed_login(?string $email): void
{
    q('INSERT INTO login_attempts (ip, email) VALUES (?, ?)', [client_ip(), $email]);
}
