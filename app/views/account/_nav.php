<?php
$cur = $_SERVER['REQUEST_URI'];
$is  = fn($p) => str_starts_with($cur, $p) ? 'active' : '';

// The balance rides along with the nav so it is on every page of the member
// area, not just the Tokens page. It is the number that decides whether a
// member can do the thing they came here to do, so it should never be a click
// away. current_user() is memoised, so this costs nothing extra.
$navU    = current_user();
$navPlan = $navU ? (string)$navU['plan'] : 'free';
$navBal  = $navU ? (int)$navU['token_balance'] : 0;
$navMax  = $navU ? promos_monthly_max($navPlan) : 0;
$navUsed = $navU ? promos_used_this_month((int)$navU['id']) : 0;
?>
<div>
  <nav class="side-nav">
    <a class="<?= $cur === '/account' ? 'active' : '' ?>" href="/account">Dashboard</a>
    <a class="<?= $is('/account/listings') ?>" href="/account/listings">My listings</a>
    <a class="<?= $is('/account/promotions') ?>" href="/account/promotions">Promotion engine</a>
    <a class="<?= $is('/account/tokens') ?>" href="/account/tokens">Tokens</a>
    <a class="<?= $is('/account/article') ?>" href="/account/article">Monthly article</a>
    <a class="<?= $is('/account/analytics') ?>" href="/account/analytics">Analytics</a>
    <a class="<?= $is('/account/billing') ?>" href="/account/billing">Plan &amp; billing</a>
    <a class="<?= $is('/account/settings') ?>" href="/account/settings">Settings</a>
    <a href="/logout">Log out</a>
  </nav>

  <?php if ($navU): ?>
    <a class="card card-pad tk-side" href="/account/tokens">
      <b><?= number_format($navBal) ?></b>
      <small>Token<?= $navBal === 1 ? '' : 's' ?></small>
      <span class="tk-side-note">
        <?= max(0, $navMax - $navUsed) ?> of <?= (int)$navMax ?> promotions left this month
      </span>
    </a>
  <?php endif; ?>
</div>
