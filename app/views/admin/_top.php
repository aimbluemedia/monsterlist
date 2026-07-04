<?php
$saPath = parse_url($_SERVER['REQUEST_URI'] ?? '/superadmin', PHP_URL_PATH);
$saNav = [
    ['/superadmin',            'Dashboard',  '📊', true],
    ['/superadmin/listings',   'Listings',   '📋', true],
    ['/superadmin/members',    'Members',    '👥', true],
    ['/superadmin/claims',     'Claims',     '🏷️', true],
    ['/superadmin/reviews',    'Reviews',    '⭐', true],
    ['/superadmin/categories', 'Categories', '🗂️', true],
    ['/superadmin/admins',     'Admins',     '🛡️', is_superadmin()],
    ['/superadmin/settings',   'Settings',   '⚙️', is_superadmin()],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($meta['title'] ?? 'Admin') ?></title>
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="sa-body">
<div class="sa-shell">
  <aside class="sa-side">
    <a class="sa-brand" href="/superadmin">
      <span class="logo-mark" style="background:#2563eb">M</span>
      <span><?= e(setting('site_name')) ?><small>Control panel</small></span>
    </a>
    <nav class="sa-nav">
      <?php foreach ($saNav as [$href, $label, $icon, $show]): if (!$show) continue; ?>
        <?php $active = $href === '/superadmin' ? $saPath === '/superadmin' : str_starts_with($saPath, $href); ?>
        <a class="<?= $active ? 'active' : '' ?>" href="<?= e($href) ?>"><span class="ico"><?= $icon ?></span><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="sa-user">
      <div class="sa-user-name"><?= e($u['name']) ?><small><?= e($u['role']) ?></small></div>
      <div class="sa-user-actions">
        <a href="/">View site</a>
        <a href="/logout">Log out</a>
      </div>
    </div>
  </aside>
  <main class="sa-main">
    <?php foreach (flash_pull() as $f): ?>
      <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>
