<?php
// /signup /login /logout /forgot /reset

$site = setting('site_name');

switch ($path) {
    case '/signup':
        if (is_logged_in()) redirect('/account');
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            // The account is identified by its business domain, so the domain
            // can be checked before an account exists at all.
            $domain = normalize_domain(post('website'));
            $email  = filter_var(post('email'), FILTER_VALIDATE_EMAIL);
            $pass   = (string)($_POST['password'] ?? '');
            if ($domain === null)        $errors[] = 'Please enter your website, like yourbusiness.com.';
            if (!$email)                 $errors[] = 'Please enter a valid email address.';
            if (strlen($pass) < 8)       $errors[] = 'Password must be at least 8 characters.';
            if ($email && row('SELECT id FROM users WHERE email = ?', [$email])) {
                $errors[] = 'An account with that email already exists. Try logging in.';
            } elseif ($email && is_blocked_email($email)) {
                // Deliberately vague: spelling out "you are blocked" just tells
                // someone to come back with a fresh address.
                $errors[] = 'We can’t create an account for that email address. If you think this is a mistake, please contact us.';
            }
            if ($domain !== null) {
                if (is_blocked_domain($domain)) {
                    $errors[] = 'That website can’t be registered on this directory. If you think this is a mistake, please contact us.';
                } elseif ($acct = account_with_domain($domain)) {
                    $errors[] = 'There is already an account for ' . $acct['website']
                        . '. Log in instead, or use the password reset if you’ve lost access.';
                } elseif ($dupe = listing_with_domain($domain)) {
                    $errors[] = domain_taken_message($dupe, $domain);
                }
            }
            if (!$errors) {
                // No name field any more — the domain doubles as the display
                // name until the member sets one in account settings.
                q('INSERT INTO users (email, password_hash, name, website) VALUES (?,?,?,?)',
                  [$email, password_hash($pass, PASSWORD_DEFAULT), $domain, $domain]);
                $user = row('SELECT * FROM users WHERE email = ?', [$email]);
                login_user($user);
                // Best-effort: the account exists and the member is signed in,
                // so a notification problem must not fail the request.
                try { notify_welcome($email, $domain); }
                catch (Throwable $e) { error_log('MonsterList: welcome email failed — ' . $e->getMessage()); }
                flash_set('success', 'Welcome to ' . $site . '! Create your first listing below.');
                redirect('/account/listings/new');
            }
        }
        $meta = [
            'title'       => "Sign up free — $site",
            'description' => "Create a free $site account and list your business in minutes.",
            'canonical'   => site_url('/signup'),
        ];
        view('auth/signup', compact('meta', 'errors'));
        break;

    case '/login':
        if (is_logged_in()) redirect(is_admin() ? '/superadmin' : '/account');
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            if (login_throttled()) {
                $errors[] = 'Too many failed attempts. Please wait 15 minutes and try again.';
            } else {
                $email = post('email');
                $pass  = (string)($_POST['password'] ?? '');
                $user  = row('SELECT * FROM users WHERE email = ?', [$email]);
                if ($user && $user['status'] === 'active' && password_verify($pass, $user['password_hash'])) {
                    if (in_array($user['role'], ['admin', 'superadmin'], true)) {
                        // Staff accounts use the dedicated admin entrance.
                        $errors[] = 'This is the member login. Administrators sign in at /superadmin/login.';
                    } else {
                        login_user($user);
                        $to = $_SESSION['after_login'] ?? '/account';
                        unset($_SESSION['after_login']);
                        redirect($to);
                    }
                } else {
                    record_failed_login($email);
                    $errors[] = 'Invalid email or password.';
                }
            }
        }
        $meta = ['title' => "Log in — $site", 'description' => "Member and admin login for $site.", 'canonical' => site_url('/login'), 'robots' => 'noindex'];
        view('auth/login', compact('meta', 'errors'));
        break;

    case '/logout':
        logout_user();
        redirect('/');

    case '/forgot':
        $done = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $email = filter_var(post('email'), FILTER_VALIDATE_EMAIL);
            if ($email && row('SELECT id FROM users WHERE email = ?', [$email])) {
                $token = bin2hex(random_bytes(32));
                q('DELETE FROM password_resets WHERE email = ?', [$email]);
                q('INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?,?, NOW() + INTERVAL 1 HOUR)',
                  [$email, hash('sha256', $token)]);
                $link = site_url('/reset?token=' . $token . '&email=' . urlencode($email));
                send_mail($email, "$site password reset", "Reset your password (link valid 1 hour):\n\n$link\n\nIf you didn't request this, ignore this email.");
            }
            $done = true; // same response whether or not the account exists
        }
        $meta = ['title' => "Reset password — $site", 'robots' => 'noindex'];
        view('auth/forgot', compact('meta', 'done'));
        break;

    case '/reset':
        $errors = [];
        $email  = (string)($_GET['email'] ?? post('email'));
        $token  = (string)($_GET['token'] ?? post('token'));
        $valid  = $email && $token && row(
            'SELECT id FROM password_resets WHERE email = ? AND token_hash = ? AND expires_at > NOW()',
            [$email, hash('sha256', $token)]
        );
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $pass = (string)($_POST['password'] ?? '');
            if (!$valid)             $errors[] = 'This reset link is invalid or has expired.';
            elseif (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
            else {
                q('UPDATE users SET password_hash = ? WHERE email = ?', [password_hash($pass, PASSWORD_DEFAULT), $email]);
                q('DELETE FROM password_resets WHERE email = ?', [$email]);
                flash_set('success', 'Password updated — log in with your new password.');
                redirect('/login');
            }
        }
        $meta = ['title' => "Choose a new password — $site", 'robots' => 'noindex'];
        view('auth/reset', compact('meta', 'errors', 'valid', 'email', 'token'));
        break;

    default:
        not_found();
}
