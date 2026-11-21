<?php
// The card bullets quote plans() and token_rules() for every number, so a
// price or allowance changed in Settings changes this page with it. The
// wording is curated rather than generated: plan_gains() returns ten lines for
// Free→Pro, and ten bullets on a card is a wall nobody reads. The full,
// generated detail is in the comparison table further down.
$pkTok = [];
foreach (['free','pro','featured'] as $pkK) $pkTok[$pkK] = token_rules($pkK);
$pkFeat = [
  'free' => [
    [true,  'Your description, category, city and website link'],
    [true,  'Services, social and review-site links'],
    [true,  'Shown in search, and on your city and category pages'],
    [true,  number_format((int)$pkTok['free']['grant']) . ' tokens a month, up to ' . (int)$pkTok['free']['promos_max'] . ' promotions'],
    [true,  'Read by a person before it goes live'],
    [false, 'No phone, logo, photos, video or analytics'],
  ],
  'pro' => [
    [true, 'Everything in Free, and your listing becomes a storefront'],
    [true, number_format(PROFILE_MAX_WORDS) . '-word profile, logo, photo gallery and video'],
    [true, 'Your phone number and a public email address'],
    [true, 'Verified badge, and views and click analytics'],
    [true, number_format((int)$pkTok['pro']['grant']) . ' tokens a month, up to ' . (int)$pkTok['pro']['promos_max'] . ' promotions'],
    [true, plan_feed_boost('pro') . ' extra days at the top of the promotions feed'],
  ],
  'featured' => [
    [true, 'Everything in Pro'],
    [true, 'Priority placement on the homepage, city and category pages'],
    [true, 'One article a month, written for you'],
    [true, 'Posted to our ' . implode(', ', article_channels())],
    [true, number_format((int)$pkTok['featured']['grant']) . ' tokens a month, up to ' . (int)$pkTok['featured']['promos_max'] . ' promotions'],
    [true, plan_feed_boost('featured') . ' extra days at the top of the promotions feed'],
  ],
];
?>
<div class="wrap">
  <section class="pk-head">
    <p class="pk-eyebrow"><?= e(setting('site_name')) ?> membership</p>
    <h1 class="pk-h1">Pick your plan</h1>
    <p class="pk-sub">One listing per account on every plan — what you pay for is how much that
      listing can do. A person checks it before it goes live. Paid plans are monthly, cancel any time.</p>
  </section>

  <div class="pk-plans">
    <?php foreach ($planList as $key => $p): ?>
      <?php
        $pkPopular = $key === 'pro';
        $pkName    = plan_public_label($key);
        // One listing on every plan, so these three lines have to say what
        // actually separates them: being findable, being worth landing on,
        // and being at the top with the marketing done for you.
        $pkLead    = [
          'free'     => 'Be findable. Your business, in the directory.',
          'pro'      => 'The same listing, turned into a full storefront.',
          'featured' => 'Top of the page — and we write the marketing.',
        ][$key];
      ?>
      <div class="pk-card<?= $pkPopular ? ' pk-pop' : '' ?>">
        <?php if ($pkPopular): ?><span class="pk-badge">Most popular</span><?php endif; ?>
        <h2 class="pk-name"><?= e($pkName) ?></h2>
        <p class="pk-price">$<?= number_format($p['price'], 0) ?><small><?= $p['price'] > 0 ? '/month' : ' forever' ?></small></p>
        <p class="pk-lead"><?= e($pkLead) ?></p>

        <ul class="pk-list">
          <?php foreach ($pkFeat[$key] as [$pkOn, $pkTxt]): ?>
            <li<?= $pkOn ? '' : ' class="pk-off"' ?>><?= e($pkTxt) ?></li>
          <?php endforeach; ?>
        </ul>

        <?php if ($key === 'free'): ?>
          <a class="btn btn-block pk-btn pk-btn-ghost" href="/signup">Start free</a>
        <?php else: ?>
          <form method="post" action="/stripe/checkout"><?= csrf_field() ?>
            <input type="hidden" name="plan" value="<?= e($key) ?>">
            <button class="btn btn-block pk-btn <?= $pkPopular ? 'pk-btn-solid' : 'pk-btn-ghost' ?>">Choose <?= e($pkName) ?></button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="wrap">

  <?php
  // The full comparison, built from the same plans() and token_rules() the site
  // actually enforces — change a number in Settings and this table changes with
  // it, so the page can never promise something the code does not do.
  // plan_public_label(), not the raw labels: the cards above say Premium and
  // a table underneath them saying Featured for the same \$49 reads as two
  // different plans.
  $cols = [];
  foreach (plan_ladder() as $cK) $cols[$cK] = plan_public_label($cK);
  $tkr  = [];
  foreach ($cols as $k => $_) $tkr[$k] = token_rules($k);
  $yes  = fn(bool $on) => $on ? '<span class="cmp-yes">✓</span>' : '<span class="cmp-no">—</span>';

  $groups = [
    'Your listing' => [
      ['Business listings',               fn($k) => plan_listing_limit($planList[$k]) === 0 ? 'Unlimited' : plan_listing_limit($planList[$k])],
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

</div>

<?php // <details> rather than a script: it opens and closes, it is keyboard
      // operable and screen readers announce its state, all without a line of
      // JavaScript — and it still works if the JS on the page ever fails.
      // Two columns, filled down the left then the right, so reading order
      // and visual order agree.
$fqHalf = (int)ceil(count($faq) / 2);
$fqCols = [array_slice($faq, 0, $fqHalf), array_slice($faq, $fqHalf)];
?>
<section class="pk-faq">
  <div class="wrap">
    <h2 class="pk-faq-h">Questions</h2>
    <div class="pk-faq-grid">
      <?php foreach ($fqCols as $fqCol): ?>
        <div class="pk-faq-col">
          <?php foreach ($fqCol as $qa): ?>
            <details class="pk-q">
              <summary><span><?= e($qa[0]) ?></span><i aria-hidden="true"></i></summary>
              <p><?= e($qa[1]) ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
