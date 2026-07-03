<?php
// ---------------------------------------------------------------------------
// Bootstrap: config, error mode, session, core libraries.
// Every entry point (public/index.php) requires this file first.
// ---------------------------------------------------------------------------
define('APP_ROOT', __DIR__);
define('BASE_ROOT', dirname(__DIR__));

$configFile = APP_ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Missing app/config.php — copy app/config.example.php and edit it.');
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
