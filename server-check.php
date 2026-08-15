<?php
// ---------------------------------------------------------------------------
// Standalone server diagnostic. No database, no app bootstrap — this file runs
// even when everything else is broken.
//
//   1. Visit  /server-check.php          → should always load
//   2. Visit  /server-check-rewrite      → loads ONLY if mod_rewrite works
//
// Delete this file once the site is confirmed healthy.
// ---------------------------------------------------------------------------
header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex');

$uri        = $_SERVER['REQUEST_URI'] ?? '';
$viaRewrite = strpos($uri, 'server-check.php') === false;

$mods = function_exists('apache_get_modules') ? apache_get_modules() : null;
$rewriteModule = $mods === null ? 'unknown (not running under mod_php)' :
    (in_array('mod_rewrite', $mods, true) ? 'loaded' : 'NOT loaded');

function row_out(string $k, string $v): void {
    echo '<tr><th style="text-align:left;padding:6px 14px 6px 0;white-space:nowrap">'
       . htmlspecialchars($k) . '</th><td style="padding:6px 0"><code>'
       . htmlspecialchars($v) . '</code></td></tr>';
}
?>
<!doctype html>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Server check</title>
<style>
 body{font:15px/1.55 system-ui,sans-serif;max-width:760px;margin:40px auto;padding:0 18px;color:#111}
 h1{font-size:1.4rem;margin:0 0 4px} .ok{color:#0a7d32;font-weight:700} .bad{color:#b3261e;font-weight:700}
 .box{border:1px solid #ddd;border-radius:10px;padding:16px 18px;margin:18px 0}
 code{background:#f4f4f5;padding:1px 5px;border-radius:4px;font-size:.9em}
</style>
<h1>Server check</h1>
<p>Plain PHP diagnostic — no database, no app code.</p>

<div class="box">
  <?php if ($viaRewrite): ?>
    <p class="ok">mod_rewrite is WORKING.</p>
    <p>This page was reached at <code><?= htmlspecialchars($uri) ?></code>, which is not a real
       file — so Apache rewrote it to the front controller. Clean URLs like
       <code>/superadmin</code> and <code>/browse</code> should work.</p>
  <?php else: ?>
    <p>Direct hit. Now visit <a href="/server-check-rewrite"><code>/server-check-rewrite</code></a>
       — if that loads this same page, rewriting works. If it 403s or 404s, mod_rewrite is the problem,
       and clean URLs such as <code>/superadmin</code> will not resolve.</p>
  <?php endif; ?>
</div>

<table>
<?php
row_out('PHP version', PHP_VERSION);
row_out('mod_rewrite', $rewriteModule);
row_out('Server software', $_SERVER['SERVER_SOFTWARE'] ?? 'unknown');
row_out('REQUEST_URI', $uri);
row_out('SCRIPT_NAME', $_SERVER['SCRIPT_NAME'] ?? '');
row_out('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT'] ?? '');
row_out('This file', __FILE__);
row_out('index.php present', is_file(__DIR__ . '/index.php') ? 'yes' : 'NO');
row_out('.htaccess present', is_file(__DIR__ . '/.htaccess') ? 'yes' : 'NO');
row_out('app/bootstrap.php present', is_file(__DIR__ . '/app/bootstrap.php') ? 'yes' : 'NO');
row_out('app/config.php present', is_file(__DIR__ . '/app/config.php') ? 'yes' : 'NO');
row_out('superadmin/index.php present', is_file(__DIR__ . '/superadmin/index.php') ? 'yes' : 'NO');
row_out('account/index.php present', is_file(__DIR__ . '/account/index.php') ? 'yes' : 'NO');
?>
</table>

<?php
// A file left behind by a partial upload fatals on require, which shows the
// visitor a blank 500. This check runs without loading the app, so it still
// answers when every other page is dead.
$libs = ['db','helpers','csrf','auth','seo','geo','blocklist','listings','promotions',
         'plans','settings','stripe','mailer','uploads','notify','ai','wizard','tokens'];
$missing = [];
foreach ($libs as $l) if (!is_file(__DIR__ . '/app/lib/' . $l . '.php')) $missing[] = 'app/lib/' . $l . '.php';
?>
<div class="box">
  <strong>Application files</strong>
  <?php if (!$missing): ?>
    <p class="ok">All <?= count($libs) ?> library files are present.</p>
  <?php else: ?>
    <p class="bad"><?= count($missing) ?> file(s) missing — this alone causes a blank 500 on every page.</p>
    <ul><?php foreach ($missing as $m): ?><li><code><?= htmlspecialchars($m) ?></code></li><?php endforeach; ?></ul>
    <p>Upload them from the release zip, keeping the folder structure. A partial FTP transfer is
       the usual cause, so re-uploading the whole <code>app</code> folder is the safest repair.</p>
  <?php endif; ?>
</div>

<div class="box">
  <strong>Next stops</strong>
  <ul>
    <li><a href="/superadmin/">/superadmin/</a> — staff login (works with or without rewrite)</li>
    <li><a href="/account/">/account/</a> — member area</li>
    <li><a href="/install-check.php">/install-check.php</a> — full app + database diagnosis</li>
  </ul>
</div>
