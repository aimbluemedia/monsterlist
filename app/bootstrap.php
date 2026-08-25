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

// Build number of this upload. Shown in the control panel under Diagnostics so
// "is my change live?" is a question the site answers itself, rather than one
// you have to work out from what a page looks like. Bumped with each release.
define('ML_BUILD', 'v112');

define('APP_ROOT', __DIR__);
define('BASE_ROOT', dirname(__DIR__));
if (!defined('WEB_ROOT')) define('WEB_ROOT', BASE_ROOT); // CLI scripts: webroot = repo root

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

// A release that adds a table or column 500s blankly until its upgrade SQL is
// imported, and the raw MySQL error never reaches the person who can fix it.
// Recognise those two errors and say which file to run instead.
set_exception_handler(function (Throwable $e) {
    $msg = $e->getMessage();
    $isSchema = stripos($msg, 'Base table or view not found') !== false
             || stripos($msg, 'Unknown column') !== false
             || stripos($msg, "doesn't exist") !== false;
    http_response_code(500);
    if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
    if ($isSchema) {
        echo '<h1>Database upgrade needed</h1>'
           . '<p>The uploaded code expects a table or column this database does not have yet:</p>'
           . '<p><code>' . htmlspecialchars($msg) . '</code></p>'
           . '<p>Fix: in hPanel open <strong>phpMyAdmin</strong>, pick this site’s database, open the '
           . '<strong>SQL</strong> tab, and run <code>database/upgrade-all.sql</code> from the release '
           . 'zip. It skips whatever the database already has, so it is safe whether none, some or all '
           . 'of the upgrades have been applied, and it never drops anything.</p>';
    } else {
        echo '<h1>Something went wrong</h1><p>The page could not be generated.</p>'
           . '<p><code>' . htmlspecialchars(get_class($e)) . '</code> in <code>'
           . htmlspecialchars(basename($e->getFile())) . ':' . (int)$e->getLine() . '</code></p>';
        // A PDOException that reaches here is always a statement error: the
        // only PDO call that can quote the DSN (and therefore the password) is
        // the connection in db(), which handles its own failure and exits. So
        // this message is safe to show, and it is the one worth showing.
        if ($e instanceof PDOException) {
            echo '<p><code>' . htmlspecialchars($msg) . '</code></p>';
        }
        if (!empty($GLOBALS['config']['debug'])) {
            echo '<p><code>' . htmlspecialchars($msg) . '</code></p><pre>'
               . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            echo '<p>Set <code>\'debug\' => true</code> in <code>app/config.php</code> to see the message '
               . 'and trace, or check the error log in hPanel.</p>';
        }
    }
    echo str_repeat(' ', 600); // pad past 512 bytes so browsers show this, not their own error page
    error_log('MonsterList: ' . $msg);
});

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

// Libraries. Checked before loading: an incomplete upload — a new file left
// behind by a partial FTP transfer — otherwise fatals on require and the
// visitor gets a blank 500 with nothing to go on.
$mlLibs = ['db', 'helpers', 'csrf', 'auth', 'seo', 'geo', 'blocklist', 'listings',
           'promotions', 'plans', 'settings', 'stripe', 'mailer', 'uploads', 'notify', 'ai', 'wizard', 'tokens', 'intake', 'cycles'];
$mlMissing = [];
foreach ($mlLibs as $mlLib) {
    if (!is_file(APP_ROOT . '/lib/' . $mlLib . '.php')) $mlMissing[] = 'app/lib/' . $mlLib . '.php';
}
if ($mlMissing) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>Upload incomplete</h1>'
       . '<p>MonsterList cannot start because ' . count($mlMissing)
       . ' required file(s) are missing from the server:</p><ul><li><code>'
       . implode('</code></li><li><code>', array_map('htmlspecialchars', $mlMissing))
       . '</code></li></ul>'
       . '<p>Fix: upload the missing file(s) from the release zip, keeping the same folder '
       . 'structure. This usually means the last upload was partial — re-uploading the whole '
       . '<code>app</code> folder is the safest repair.</p>'
       . '<p>More checks at <code>/server-check.php</code>.</p>'
       . str_repeat(' ', 600); // pad past 512 bytes so browsers show this, not their own error page
    exit;
}
foreach ($mlLibs as $mlLib) require APP_ROOT . '/lib/' . $mlLib . '.php';
