<?php $cur = $_SERVER['REQUEST_URI']; $is = fn($p) => str_starts_with($cur, $p) ? 'active' : ''; ?>
<nav class="side-nav">
  <a class="<?= $cur === '/account' ? 'active' : '' ?>" href="/account">Dashboard</a>
  <a class="<?= $is('/account/listings') ?>" href="/account/listings">My listings</a>
  <a class="<?= $is('/account/promotions') ?>" href="/account/promotions">Promotion engine</a>
  <a class="<?= $is('/account/analytics') ?>" href="/account/analytics">Analytics</a>
  <a class="<?= $is('/account/billing') ?>" href="/account/billing">Plan &amp; billing</a>
  <a class="<?= $is('/account/settings') ?>" href="/account/settings">Settings</a>
  <a href="/logout">Log out</a>
</nav>
