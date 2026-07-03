<?php
// ---------------------------------------------------------------------------
// Bootstrap: config, error mode, session, core libraries.
// Every entry point (public/index.php) requires this file first.
// ---------------------------------------------------------------------------
if (PHP_VERSION_ID < 70400) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>PHP version too old</h1><p>MonsterList needs PHP 7.4 or newer (8.1+ recommended); this server is running PHP '
       . PHP_VERSION . '.</p><p>Fix: in cPanel open <strong>MultiPHP Manager</strong> (or "Select PHP Version"), '
       . 'select this domain and set PHP 8.1 or newer, then reload this page.</p>'
       . str_repeat(' ', 600); // pad past 512 bytes so browsers show this message instead of a generic error page
    exit;
}

define('APP_ROOT', __DIR__);
define('BASE_ROOT', dirname(__DIR__));

$configFile = APP_ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>Configuration file missing</h1>'
       . '<p>MonsterList is uploaded but not configured yet: <code>app/config.php</code> does not exist.</p>'
       . '<p>Fix: in your file manager, copy <code>app/config.example.php</code> to <code>app/config.php</code>, '
       . 'then edit it with your site URL and MySQL database credentials (Step 4 in INSTALL.txt).</p>'
       . '<p>You can run a full server self-test at <code>/install-check.php</code>.</p>'
       . str_repeat(' ', 600);
    exit;
}
$GLOBALS['config'] = require $configFile;

if (!empty($GLOBALS['config']['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}

date_default_timezone_set('UTC');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('mlsession');
session_start();

require APP_ROOT . '/lib/db.php';
require APP_ROOT . '/lib/helpers.php';
require APP_ROOT . '/lib/csrf.php';
require APP_ROOT . '/lib/auth.php';
require APP_ROOT . '/lib/seo.php';
require APP_ROOT . '/lib/geo.php';
require APP_ROOT . '/lib/listings.php';
require APP_ROOT . '/lib/plans.php';
require APP_ROOT . '/lib/settings.php';
require APP_ROOT . '/lib/stripe.php';
require APP_ROOT . '/lib/mailer.php';
require APP_ROOT . '/lib/uploads.php';
require APP_ROOT . '/lib/notify.php';
