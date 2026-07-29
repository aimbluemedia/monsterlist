<?php
/** @var array $meta  ['title','description','canonical','jsonld'=>[],'og'=>[],'robots'] */
$meta          = $meta ?? [];
$pageTitle     = $meta['title'] ?? setting('site_name') . ' — ' . setting('site_tagline');
$pageDesc      = $meta['description'] ?? setting('site_tagline');
$canonical     = $meta['canonical'] ?? null;
$me            = current_user();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<?php if (!empty($meta['robots'])): ?><meta name="robots" content="<?= e($meta['robots']) ?>">
<?php endif; ?>
<?php if ($canonical): ?><link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
<meta property="og:site_name" content="<?= e(setting('site_name')) ?>">
<meta property="og:type" content="<?= e($meta['og']['type'] ?? 'website') ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<?php if ($canonical): ?><meta property="og:url" content="<?= e($canonical) ?>">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<?php render_jsonld($meta['jsonld'] ?? []); ?>
</head>
<body>
<header class="site-header">
  <div class="wrap nav-row">
    <a class="logo" href="/"><span class="logo-mark">M</span> <?= e(setting('site_name')) ?></a>
    <nav class="main-nav">
      <a href="/browse">Browse</a>
      <a href="/search">Search</a>
      <a href="/pricing">Pricing</a>
    </nav>
    <div class="nav-actions">
      <?php if ($me): ?>
        <?php if (in_array($me['role'], ['admin','superadmin'], true)): ?>
          <a class="btn btn-ghost" href="/superadmin">Admin</a>
        <?php endif; ?>
        <a class="btn btn-ghost" href="/account">My account</a>
        <a class="btn btn-primary" href="/account/listings/new">Add Your Business FREE</a>
      <?php else: ?>
        <a class="btn btn-ghost" href="/login">Log in</a>
        <a class="btn btn-primary" href="/add-listing">Add Your Business FREE</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<?php foreach (flash_pull() as $f): ?>
  <div class="wrap"><div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div></div>
<?php endforeach; ?>
<main>
