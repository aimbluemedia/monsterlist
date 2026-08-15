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
        <?php // Headlines only. Fifteen bullets on one card is a wall nobody
              // reads — the full detail is in the comparison table below. ?>
        <?php $tk = token_rules($key); ?>
        <ul>
          <li><?= (int)$p['max_listings'] ?> listing<?= $p['max_listings'] > 1 ? 's' : '' ?></li>
          <li><?= number_format((int)$tk['grant']) ?> tokens a month</li>
          <li>Up to <?= (int)$tk['promos_max'] ?> promotions a month</li>
          <?php if ($p['enhanced']): ?>
            <li><?= number_format(PROFILE_MAX_WORDS) ?>-word Profile, logo, photos &amp; video</li>
            <li>Phone &amp; public email on your listing</li>
          <?php else: ?>
            <li class="no">About section only, no contact details or images</li>
          <?php endif; ?>
          <?php if (!empty($p['concierge'])): ?>
            <li>One article a month, written and posted out for you</li>
          <?php endif; ?>
          <?php if ($p['featured']): ?><li>Priority placement across the site</li><?php endif; ?>
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

  <?php
  // The full comparison, built from the same plans() and token_rules() the site
  // actually enforces — change a number in Settings and this table changes with
  // it, so the page can never promise something the code does not do.
  $cols = ['free' => 'Free', 'pro' => 'Pro', 'featured' => 'Featured'];
  $tkr  = [];
  foreach ($cols as $k => $_) $tkr[$k] = token_rules($k);
  $yes  = fn(bool $on) => $on ? '<span class="cmp-yes">✓</span>' : '<span class="cmp-no">—</span>';

  $groups = [
    'Your listing' => [
      ['Listings',                        fn($k) => (int)$planList[$k]['max_listings']],
      ['Shown in search, city &amp; category pages', fn($k) => $yes(true)],
      ['Link to your website',            fn($k) => $yes(true)],
      ['About section',                   fn($k) => $yes(true)],
      ['Services, social &amp; review-site links', fn($k) => $yes(true)],
      [number_format(PROFILE_MAX_WORDS) . '-word Profile section', fn($k) => $yes(!empty($planList[$k]['profile']))],
      ['Phone &amp; public email',        fn($k) => $yes((bool)$planList[$k]['enhanced'])],
      ['Logo',                            fn($k) => $yes((bool)$planList[$k]['enhanced'])],
      ['Photo gallery (up to 6)',         fn($k) => $yes((bool)$planList[$k]['enhanced'])],
      ['Video',                           fn($k) => $yes((bool)$planList[$k]['enhanced'])],
      ['Verified badge',                  fn($k) => $yes((bool)$planList[$k]['enhanced'])],
      ['Views &amp; clicks analytics',    fn($k) => $yes((bool)$planList[$k]['analytics'])],
      ['Priority placement — homepage, city &amp; category pages', fn($k) => $yes((bool)$planList[$k]['featured'])],
    ],
    'Promotion engine' => [
      ['Tokens each month',               fn($k) => number_format((int)$tkr[$k]['grant'])],
      ['Promotions you can run a month',  fn($k) => (int)$tkr[$k]['promos_max']],
      ['Tokens per promotion',            fn($k) => (int)$tkr[$k]['cost_promo']],
      ['Earned for opening a member promotion', fn($k) => (int)$tkr[$k]['earn_view'] . ' tokens'],
      ['Most you can earn in a day',      fn($k) => (int)$tkr[$k]['daily_earn_cap'] ?: '—'],
      ['Promotions to open to earn one of your own',
                                          fn($k) => token_views_per_promo($k) ?: '—'],
      ['Extra days at the top of the feed',
                                          fn($k) => $k === 'free' ? $yes(false)
                                              : (int)setting('feed_boost_' . $k, $k === 'pro' ? '7' : '14') . ' days'],
    ],
    'We do it for you' => [
      ['One article a month, written for you', fn($k) => $yes(!empty($planList[$k]['concierge']))],
      ['Posted to our ' . implode(', ', article_channels()), fn($k) => $yes(!empty($planList[$k]['concierge']))],
      ['Promoted to each of your own channels', fn($k) => $yes(!empty($planList[$k]['concierge']))],
    ],
  ];
  ?>
  <section class="section">
    <h2 style="text-align:center">Everything, side by side</h2>
    <div class="card card-pad table-wrap cmp-wrap">
      <table class="table cmp">
        <thead>
          <tr>
            <th></th>
            <?php foreach ($cols as $k => $label): ?>
              <th class="cmp-col<?= $k === 'pro' ? ' cmp-pop' : '' ?>">
                <?= e($label) ?><br><span class="faint">$<?= number_format($planList[$k]['price'], 0) ?>/mo</span>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <?php foreach ($groups as $title => $lines): ?>
          <tbody>
            <tr class="cmp-group"><th colspan="4"><?= $title ?></th></tr>
            <?php foreach ($lines as [$label, $cell]): ?>
              <tr>
                <td><?= $label ?></td>
                <?php foreach ($cols as $k => $_): ?>
                  <td class="cmp-col<?= $k === 'pro' ? ' cmp-pop' : '' ?>"><?= $cell($k) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        <?php endforeach; ?>
      </table>
    </div>
    <p class="mute" style="text-align:center;margin-top:12px;font-size:.9rem">
      Tokens are how promotions work: you spend them to put a link in front of the network, and earn them by
      opening other members' links. Paying earns you more per view, lets you run more, and keeps your posts
      at the top for longer.
    </p>
  </section>

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
