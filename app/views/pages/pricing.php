<div class="wrap">
  <section class="section" style="text-align:center">
    <h1>Simple pricing. Cancel anytime.</h1>
    <p class="mute">Start free. Upgrade when you want a bigger presence.</p>
  </section>

  <div class="plans">
    <?php foreach ($planList as $key => $p): ?>
      <?php // Free spans the full width on top; the two paid plans share the row
            // below it, so the choice reads as "start here" then "or upgrade to". ?>
      <div class="card plan <?= $key === 'pro' ? 'popular' : '' ?><?= $key === 'free' ? ' plan-wide' : '' ?>">
        <?php // The free card leads with the offer rather than the plan name —
              // "Free" is what the price already says. ?>
        <?php if ($key === 'free'): ?>
          <h3>Add Your Business FREE</h3>
          <p class="plan-sub">No Credit Card Needed</p>
        <?php else: ?>
          <h3><?= e($p['label']) ?><?= $key === 'pro' ? ' · Most popular' : '' ?></h3>
        <?php endif; ?>
        <div class="price">$<?= number_format($p['price'], 0) ?><small>/month</small></div>
        <p class="mute"><?= e($p['blurb']) ?></p>
        <ul>
          <li><?= (int)$p['max_listings'] ?> listing<?= $p['max_listings'] > 1 ? 's' : '' ?></li>
          <li>Appears in search &amp; city pages</li>
          <li>Link to your website</li>
          <li>Social profiles &amp; review-site links</li>
          <?php $tk = token_rules($key); ?>
          <li><?= number_format((int)$tk['grant']) ?> promotion tokens a month</li>
          <li>Run up to <?= (int)$tk['promos_max'] ?> promotions a month</li>
          <li>Earn <?= (int)$tk['earn_view'] ?> tokens per member promotion you open<?= (int)$tk['daily_earn_cap'] ? ', up to ' . (int)$tk['daily_earn_cap'] . ' a day' : '' ?></li>
          <?php if ($key !== 'free' && ($boost = (int)setting('feed_boost_' . $key, $key === 'pro' ? '7' : '14'))): ?>
            <li>Your promotions stay top of the feed <?= $boost ?> days longer</li>
          <?php endif; ?>
          <?php if ($p['enhanced']): ?>
            <li><?= number_format(PROFILE_MAX_WORDS) ?>-word Profile section</li>
            <li>Phone &amp; public email on your listing</li>
            <li>Your logo and a photo gallery</li>
            <li>Video</li>
            <li>Verified badge</li>
          <?php else: ?>
            <li class="no">About section only — no long-form Profile</li>
            <li class="no">No phone or public email</li>
            <li class="no">No logo or photos</li>
          <?php endif; ?>
          <?php if (!empty($p['concierge'])): ?>
            <li>One article a month, written for you</li>
            <li>We post it to our Facebook, Instagram, TikTok, YouTube, Pinterest &amp; Reddit</li>
            <li>…and promote it to each of yours</li>
          <?php endif; ?>
          <?php if ($p['analytics']): ?><li>Views &amp; clicks analytics</li><?php endif; ?>
          <?php if ($p['featured']): ?><li>Priority placement — homepage + top of city &amp; category pages</li><?php endif; ?>
        </ul>
        <?php if ($key === 'free'): ?>
          <?php // Primary, not ghost: this is the highlighted card, and a ghost
                // button on it would be the weakest call to action on the page. ?>
          <a class="btn btn-primary btn-block btn-xl" href="/signup">Start free</a>
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
