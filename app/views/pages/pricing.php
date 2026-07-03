<div class="wrap">
  <section class="section" style="text-align:center">
    <h1>Simple pricing. Cancel anytime.</h1>
    <p class="mute">Start free. Upgrade when you want a bigger presence.</p>
  </section>

  <div class="plans">
    <?php foreach ($planList as $key => $p): ?>
      <div class="card plan <?= $key === 'pro' ? 'popular' : '' ?>">
        <h3><?= e($p['label']) ?><?= $key === 'pro' ? ' · Most popular' : '' ?></h3>
        <div class="price">$<?= number_format($p['price'], 0) ?><small>/month</small></div>
        <p class="mute"><?= e($p['blurb']) ?></p>
        <ul>
          <li><?= (int)$p['max_listings'] ?> listing<?= $p['max_listings'] > 1 ? 's' : '' ?></li>
          <li>Appears in search &amp; city pages</li>
          <?php if ($p['enhanced']): ?>
            <li>Full storefront: photos, video, social links</li>
            <li>Verified badge</li>
          <?php endif; ?>
          <?php if ($p['analytics']): ?><li>Views &amp; clicks analytics</li><?php endif; ?>
          <?php if ($p['featured']): ?><li>Priority placement — homepage + top of city &amp; category pages</li><?php endif; ?>
        </ul>
        <?php if ($key === 'free'): ?>
          <a class="btn btn-ghost btn-block" href="/signup">Start free</a>
        <?php else: ?>
          <form method="post" action="/stripe/checkout"><?= csrf_field() ?>
            <input type="hidden" name="plan" value="<?= e($key) ?>">
            <button class="btn btn-primary btn-block">Get <?= e($p['label']) ?></button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <section class="section" style="max-width:720px;margin:0 auto">
    <h2>Frequently asked questions</h2>
    <?php foreach ($faq as $qa): ?>
      <div class="card card-pad" style="margin-bottom:10px">
        <strong><?= e($qa[0]) ?></strong>
        <p class="mute" style="margin:6px 0 0"><?= e($qa[1]) ?></p>
      </div>
    <?php endforeach; ?>
  </section>
</div>
