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
<body>
<header class="site-header">
  <div class="wrap nav-row">
    <a class="logo" href="/superadmin"><span class="logo-mark" style="background:#1a1d24">A</span> <?= e(setting('site_name')) ?> Admin</a>
    <nav class="main-nav">
      <a href="/superadmin">Dashboard</a>
      <a href="/superadmin/listings">Listings</a>
      <a href="/superadmin/members">Members</a>
      <a href="/superadmin/claims">Claims</a>
      <a href="/superadmin/reviews">Reviews</a>
      <a href="/superadmin/categories">Categories</a>
      <?php if (is_superadmin()): ?>
        <a href="/superadmin/admins">Admins</a>
        <a href="/superadmin/settings">Settings</a>
      <?php endif; ?>
    </nav>
    <div class="nav-actions">
      <span class="mute" style="font-size:.85rem"><?= e($u['name']) ?> · <?= e($u['role']) ?></span>
      <a class="btn btn-ghost btn-sm" href="/">View site</a>
      <a class="btn btn-ghost btn-sm" href="/logout">Log out</a>
    </div>
  </div>
</header>
<?php foreach (flash_pull() as $f): ?>
  <div class="wrap"><div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div></div>
<?php endforeach; ?>
<main class="wrap section">
