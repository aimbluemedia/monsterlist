<?php
// /signup /login /logout /forgot /reset

$site = setting('site_name');

/**
 * Is Toutify in charge of signing people in?
 *
 * BOTH halves matter. 'live' is the switch, but toutify_ready() is the seatbelt:
 * it also checks that there is a client secret and that the database has the
 * columns. Without that, a half-finished setup would send every visitor to a
 * route that answers 404 and nobody — staff included — could get in. When
 * anything is missing the form below is still here and still works.
 *
 * Setting mode back to 'test' or 'off' restores today's login, sign-up and
 * password reset exactly as they are.
 */
function auth_toutify_runs_login(): bool
{
    return toutify_mode() === 'live' && toutify_ready();
}

switch ($path) {
    case '/signup':
        if (is_logged_in()) redirect('/members');
        // prompt=create, so Toutify opens on its "create an account" step.
        if (auth_toutify_runs_login()) redirect('/auth/toutify/login?signup=1');
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            // The account is identified by its business domain, so the domain
            // can be checked before an account exists at all.
            $domain = normalize_domain(post('website'));
            // Normalising gets a pasted URL into the shape of a host; it does
            // not decide whether the result is one. "-bad.com" and "a..b.com"
            // survive the first and fail the second.
            if ($domain !== null && !domain_is_valid($domain)) $domain = null;
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
                // name until the member sets one in account settings. `name`
                // is the narrower column, so it is cut to fit: a long domain
                // otherwise failed the insert and the visitor got a 500 on the
                // sign-up form with their account not created.
                q('INSERT INTO users (email, password_hash, name, website) VALUES (?,?,?,?)',
                  [$email, password_hash($pass, PASSWORD_DEFAULT), mb_substr($domain, 0, 140), $domain]);
                $user = row('SELECT * FROM users WHERE email = ?', [$email]);
                login_user($user);
                // Best-effort: the account exists and the member is signed in,
                // so a notification problem must not fail the request.
                try { notify_welcome($email, $domain); }
                catch (Throwable $e) { error_log('MonsterList: welcome email failed — ' . $e->getMessage()); }
                flash_set('success', 'Welcome to ' . $site . '! Create your first listing below.');
                redirect('/members/listings/new');
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
        if (is_logged_in()) redirect(is_admin() ? '/superadmin' : '/members');
        if (auth_toutify_runs_login()) {
            // Straight to Toutify's own page. An email typed into the form
            // before the switch — or posted by a password manager — is carried
            // over as login_hint so it does not have to be typed twice.
            $hint = trim((string)($_POST['email'] ?? $_GET['email'] ?? ''));
            redirect('/auth/toutify/login' . ($hint !== '' ? '?login_hint=' . urlencode($hint) : ''));
        }
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
                        // Always the dashboard. Signing in used to continue to
                        // whatever members-only page had been asked for while
                        // logged out — but that address is remembered for the
                        // whole browsing session, so someone who bounced off
                        // /members/billing in the morning and signed in from
                        // the homepage in the afternoon landed on billing with
                        // no idea why. One predictable destination is worth
                        // more than the occasional saved click.
                        unset($_SESSION['after_login']);
                        redirect('/members');
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
        // Signed in through Toutify? Then logging out of this site alone would
        // leave them signed in at toutify.com, and the next click on "Log in"
        // would walk straight back in without asking for anything.
        if (!empty($_SESSION['tf_sub']) && toutify_ready()) redirect('/auth/toutify/logout');
        logout_user();
        redirect('/');

    case '/forgot':
        // Passwords live on Toutify now, so this page has nothing to reset.
        if (auth_toutify_runs_login()) redirect(toutify_issuer() . '/members/forgot.php');
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
        // A reset link emailed before the switch. The token is no longer worth
        // anything, so send them somewhere that can actually sign them in
        // rather than showing a form that cannot.
        if (auth_toutify_runs_login()) redirect('/auth/toutify/login');
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
