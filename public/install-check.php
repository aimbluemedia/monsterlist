<?php
/**
 * MonsterList server self-test. Visit /install-check.php in your browser.
 * Written for maximum compatibility (runs even on PHP 5) so it can diagnose
 * servers where the app itself cannot start.
 *
 * ⚠️ DELETE THIS FILE once your site is working.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=UTF-8');

function ml_row($ok, $label, $detail) {
    $icon  = $ok === true ? '✅' : ($ok === null ? '⚠️' : '❌');
    $color = $ok === true ? '#0ca678' : ($ok === null ? '#d97706' : '#dc2626');
    echo '<tr><td style="font-size:20px;padding:8px 12px">' . $icon . '</td>'
       . '<td style="padding:8px 12px;font-weight:bold">' . htmlspecialchars($label) . '</td>'
       . '<td style="padding:8px 12px;color:' . $color . '">' . $detail . '</td></tr>';
}

echo '<!DOCTYPE html><html><head><title>MonsterList install check</title></head>'
   . '<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7f9;color:#1a1d24;padding:30px">'
   . '<div style="max-width:860px;margin:0 auto;background:#fff;border:1px solid #e7e9ee;border-radius:12px;padding:30px">'
   . '<h1>MonsterList install check</h1>'
   . '<table style="border-collapse:collapse;width:100%">';

// 1. PHP version
$phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
ml_row($phpOk, 'PHP version', 'Running PHP ' . PHP_VERSION . ($phpOk
    ? (version_compare(PHP_VERSION, '8.1.0', '>=') ? ' — great' : ' — works, but PHP 8.1+ is recommended (cPanel → MultiPHP Manager)')
    : ' — TOO OLD. Set PHP 8.1+ in cPanel → MultiPHP Manager / Select PHP Version'));

// 2. Extensions
$exts = array('pdo_mysql' => 'MySQL driver', 'gd' => 'image resizing', 'curl' => 'Stripe & IndexNow',
              'fileinfo' => 'upload validation', 'mbstring' => 'text handling', 'json' => 'data handling');
foreach ($exts as $ext => $why) {
    $has = extension_loaded($ext);
    ml_row($has, 'PHP extension: ' . $ext,
        $has ? 'Loaded (' . $why . ')' : 'MISSING — needed for ' . $why . '. Enable it in cPanel → Select PHP Version → Extensions');
}

// 3. Locate the app folder
$appDir = null;
$candidates = array(dirname(__DIR__) . '/app', __DIR__ . '/app', dirname(dirname(__DIR__)) . '/app');
foreach ($candidates as $cand) {
    if (is_file($cand . '/bootstrap.php')) { $appDir = $cand; break; }
}
ml_row($appDir !== null, 'App folder (app/bootstrap.php)',
    $appDir !== null ? 'Found at ' . htmlspecialchars($appDir)
    : 'NOT FOUND. The app/ folder must sit one level above this web root (or update the require path in index.php). Looked in:<br>'
      . htmlspecialchars(implode('<br>', $candidates)));

// 4. Config file
$config = null;
if ($appDir !== null) {
    $hasConfig = is_file($appDir . '/config.php');
    ml_row($hasConfig, 'Configuration (app/config.php)',
        $hasConfig ? 'Found' : 'MISSING — copy app/config.example.php to app/config.php and edit it (INSTALL.txt step 4)');
    if ($hasConfig) {
        $config = include $appDir . '/config.php';
        if (!is_array($config)) { $config = null; ml_row(false, 'Config contents', 'config.php did not return a settings array — re-copy it from config.example.php'); }
    }
}

// 5. Database connection
if ($config !== null && extension_loaded('pdo_mysql')) {
    $c = isset($config['db']) ? $config['db'] : array();
    try {
        $pdo = new PDO(
            'mysql:host=' . $c['host'] . ';dbname=' . $c['name'] . ';charset=' . $c['charset'],
            $c['user'], $c['pass'],
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        ml_row(true, 'MySQL connection', 'Connected to database <code>' . htmlspecialchars($c['name']) . '</code>');
        $need = array('countries', 'cities', 'categories', 'users', 'businesses', 'settings');
        $missing = array();
        foreach ($need as $t) {
            $st = $pdo->query("SHOW TABLES LIKE '" . $t . "'");
            if ($st === false || $st->rowCount() === 0) { $missing[] = $t; }
        }
        ml_row(count($missing) === 0, 'Database tables',
            count($missing) === 0 ? 'All core tables present'
            : 'Missing: ' . implode(', ', $missing) . ' — import database/schema.sql then seed.sql in phpMyAdmin');
        if (count($missing) === 0) {
            $n = $pdo->query('SELECT COUNT(*) FROM countries')->fetchColumn();
            ml_row($n > 0, 'Seed data', $n > 0 ? $n . ' countries loaded' : 'countries table is empty — import database/seed.sql');
            $a = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin','superadmin')")->fetchColumn();
            ml_row($a > 0, 'Admin account', $a > 0 ? 'Admin account exists — log in at /login'
                : 'No admin yet — import database/create-superadmin.sql (edit the email first)');
        }
    } catch (Exception $e) {
        ml_row(false, 'MySQL connection', 'FAILED: ' . htmlspecialchars($e->getMessage())
            . '<br>Check host/name/user/pass in app/config.php, and that the DB user was ADDED to the database with all privileges in cPanel.');
    }
} elseif ($config !== null) {
    ml_row(false, 'MySQL connection', 'Skipped — pdo_mysql extension is missing (see above)');
}

// 6. Uploads folder
$upl = __DIR__ . '/uploads';
if (!is_dir($upl)) {
    ml_row(false, 'uploads/ folder', 'Missing — create an <code>uploads</code> folder inside the web root');
} else {
    ml_row(is_writable($upl), 'uploads/ folder', is_writable($upl) ? 'Writable' : 'Not writable — set permissions to 755 (or 775 if your host requires)');
}

// 7. Rewrites
$rw = null;
if (function_exists('apache_get_modules')) { $rw = in_array('mod_rewrite', apache_get_modules()); }
ml_row($rw === null ? null : $rw, 'Pretty URLs (mod_rewrite + .htaccess)',
    ($rw === true ? 'mod_rewrite is loaded. ' : ($rw === false ? 'mod_rewrite NOT loaded — ask your host. ' : 'Could not auto-detect (normal on PHP-FPM). '))
    . 'Test: after everything above is green, open <a href="/browse">/browse</a> — if it 404s, the hidden .htaccess file didn\'t get uploaded.');

echo '</table>'
   . '<h3 style="margin-top:24px">Next steps</h3>'
   . '<p>Fix anything marked ❌ above (top to bottom), then reload this page. '
   . 'When every row is green, your site is live: <a href="/">open the homepage</a>.</p>'
   . '<p style="background:#fdeaea;color:#dc2626;padding:12px;border-radius:8px;font-weight:bold">'
   . 'Security: DELETE this file (install-check.php) once the site works.</p>'
   . '</div></body></html>';
