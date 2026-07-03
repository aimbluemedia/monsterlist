<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <h1>Plan &amp; billing</h1>
    <div class="card card-pad" style="margin-bottom:14px">
      <h3>Current plan: <?= e($plan['label']) ?></h3>
      <p class="mute"><?= e($plan['blurb']) ?></p>
      <?php if ($subRow): ?>
        <div class="info-row"><span class="mute">Status</span><span><?= e(ucfirst($subRow['status'])) ?></span></div>
        <?php if ($subRow['current_period_end']): ?>
          <div class="info-row"><span class="mute">Renews</span><span><?= e(date('M j, Y', strtotime($subRow['current_period_end']))) ?></span></div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php if ($u['plan'] === 'free'): ?>
      <div class="card card-pad">
        <h3>Upgrade</h3>
        <p class="mute">Unlock a full storefront, analytics and more visibility.</p>
        <form method="post" action="/stripe/checkout" style="display:inline"><?= csrf_field() ?>
          <input type="hidden" name="plan" value="pro">
          <button class="btn btn-primary">Go Pro</button>
        </form>
        <form method="post" action="/stripe/checkout" style="display:inline"><?= csrf_field() ?>
          <input type="hidden" name="plan" value="featured">
          <button class="btn btn-ghost">Go Featured</button>
        </form>
        <p class="form-note"><a href="/pricing" style="color:var(--accent)">Compare plans →</a></p>
      </div>
    <?php else: ?>
      <div class="card card-pad">
        <h3>Manage subscription</h3>
        <p class="mute">Update your card, switch plans or cancel — handled securely by Stripe.</p>
        <form method="post" action="/stripe/portal"><?= csrf_field() ?>
          <button class="btn btn-ghost">Open billing portal</button>
        </form>
        <?php if ($u['plan'] === 'pro'): ?>
          <form method="post" action="/stripe/checkout" style="margin-top:10px"><?= csrf_field() ?>
            <input type="hidden" name="plan" value="featured">
            <button class="btn btn-primary">Upgrade to Featured</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
