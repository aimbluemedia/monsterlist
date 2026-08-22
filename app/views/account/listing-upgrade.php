<?php
// Upgrade, scoped to one listing. Everything on this page names the business
// being upgraded — the heading, each button, the confirmation underneath —
// because "upgrade your listing" from a member area that also holds tokens,
// billing and an account plan is otherwise a sentence with three possible
// meanings, and the member finds out which one it was after paying.
$bizName = (string)$biz['name'];
$where   = trim(($cityRow['name'] ?? '') . (!empty($cityRow['region_name']) ? ', ' . $cityRow['region_name'] : ''));
?>
<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <h1>Upgrade this listing</h1>

    <div class="card card-pad up-which">
      <span class="up-which-label">You are upgrading</span>
      <b class="up-which-name"><?= e($bizName) ?></b>
      <?php if ($where !== ''): ?><span class="up-which-meta"><?= e($where) ?></span><?php endif; ?>
      <span class="up-now">
        On the <b><?= e($plan['label']) ?></b> plan
        <?= $plan['price'] > 0 ? '· $' . number_format($plan['price'], 0) . ' a month' : '· free' ?>
      </span>
    </div>

    <?php if (!$offers): ?>
      <?php // Featured is the top of the ladder. There is nothing to sell here,
            // so this page sells nothing and says so. ?>
      <div class="card card-pad up-top">
        <h2>You are on the top plan</h2>
        <p class="mute">
          <b><?= e($bizName) ?></b> already has everything MonsterList offers — the full storefront,
          priority placement, the largest token allowance, and an article a month written and
          posted out for you.
        </p>
        <p>
          <a class="btn btn-primary" href="/account/listings/edit?id=<?= (int)$biz['id'] ?>">Back to this listing</a>
          <a class="btn btn-ghost" href="/account/billing">Billing</a>
        </p>
      </div>
    <?php else: ?>
      <p class="up-intro">
        Everything below applies to <b><?= e($bizName) ?></b> — and to every other listing on
        your account, now and later: Pro and Premium both carry as many as you like.
      </p>

      <div class="up-plans">
        <?php foreach ($offers as $key => $p): ?>
          <?php
          $gains  = plan_gains((string)$u['plan'], $key);
          $perDay = '$' . number_format($p['price'] / 30.4, 2) . ' a day';
          $tk     = token_rules($key);
          ?>
          <div class="card up-card<?= $key === 'featured' ? ' up-card-top' : '' ?>">
            <span class="up-ribbon"><?= $key === 'featured' ? 'We do the work' : 'Most popular' ?></span>
            <h2><?= e($p['label']) ?></h2>
            <div class="up-price">$<?= number_format($p['price'], 0) ?><small>/month</small></div>
            <p class="up-day"><?= e($perDay) ?></p>
            <p class="mute"><?= e($p['blurb']) ?></p>

            <p class="up-gain-head">What <?= e($bizName) ?> gains</p>
            <ul class="up-gains">
              <?php foreach ($gains as $g): ?><li><?= e($g) ?></li><?php endforeach; ?>
            </ul>

            <form method="post" action="/stripe/checkout"><?= csrf_field() ?>
              <input type="hidden" name="plan" value="<?= e($key) ?>">
              <?php // The listing travels with the payment, so Stripe returns
                    // the member to the business they just upgraded. ?>
              <input type="hidden" name="business_id" value="<?= (int)$biz['id'] ?>">
              <button class="btn btn-primary btn-block btn-xl">
                Upgrade <?= e($bizName) ?> to <?= e($p['label']) ?>
              </button>
            </form>
            <p class="up-note">
              <?= number_format((int)$tk['grant']) ?> tokens a month · Cancel anytime ·
              Applies to <?= e($bizName) ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>

      <p class="up-foot">
        Prices are per month and include tax where it applies. Nothing you have already
        written is lost on an upgrade — the phone, email and images you add unlock on the
        listing as soon as the payment clears.
        <a href="/pricing">See the full comparison →</a>
      </p>
    <?php endif; ?>
  </div>
</div>
