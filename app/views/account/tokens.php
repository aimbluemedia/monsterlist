<?php $bal = (int)$u['token_balance']; ?>
<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <h1>Tokens</h1>

    <div class="card card-pad tk-hero">
      <b><?= number_format($bal) ?></b>
      <small>Token<?= $bal === 1 ? '' : 's' ?> available</small>
      <p class="mute" style="margin:14px 0 0">
        Your <?= e($plan['label']) ?> plan adds <strong><?= number_format(token_monthly_grant((string)$u['plan'])) ?></strong>
        tokens on the first visit of each month.
      </p>
    </div>

    <div class="grid grid-3" style="margin-bottom:18px">
      <div class="card card-pad">
        <h3>Spend them</h3>
        <p class="mute" style="margin:0">Putting a link in the member feed costs
          <strong><?= (int)$rules['cost_promo'] ?> tokens</strong>. Blog posts, products, services, reviews,
          Facebook, Instagram, TikTok, YouTube, Pinterest, Reddit — whatever you have already published.</p>
        <a class="btn btn-primary btn-sm" style="margin-top:12px" href="/account/promotions">Promote something</a>
      </div>
      <div class="card card-pad">
        <h3>Earn them</h3>
        <p class="mute" style="margin:0">Open another member's promotion and you earn
          <strong><?= (int)$rules['earn_view'] ?> tokens</strong> — once per promotion per day, up to
          <strong><?= (int)$rules['daily_earn_cap'] ?> a day</strong>. Give a genuine look; that is the deal.</p>
        <a class="btn btn-ghost btn-sm" style="margin-top:12px" href="/promotions">Open the feed</a>
      </div>
      <div class="card card-pad">
        <h3>Why tokens</h3>
        <p class="mute" style="margin:0">A feed nobody reads is worth nothing to post in. Paying attention to
          earn attention keeps the members posting and the members reading the same people.</p>
      </div>
    </div>

    <div class="card card-pad">
      <h3>History</h3>
      <?php if (!$history): ?>
        <p class="mute" style="margin:0">Nothing yet.</p>
      <?php else: ?>
        <div class="table-wrap table-narrow">
          <table class="table">
            <tr><th>When</th><th>What</th><th style="text-align:right">Tokens</th></tr>
            <?php foreach ($history as $e): ?>
              <tr>
                <td class="mute" style="white-space:nowrap"><?= e(date('M j, Y', strtotime($e['created_at']))) ?></td>
                <td><?= e(token_reason_label($e)) ?></td>
                <td style="text-align:right;font-weight:800;color:<?= (int)$e['delta'] > 0 ? 'var(--green)' : 'var(--mute)' ?>">
                  <?= (int)$e['delta'] > 0 ? '+' : '' ?><?= (int)$e['delta'] ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
