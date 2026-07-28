<?php $site = setting('site_name'); ?>

<!-- scrolling ticker -->
<div class="lp-ticker" aria-hidden="true">
  <div class="lp-ticker-track">
    <?php for ($i = 0; $i < 2; $i++): ?>
      <span>100% FREE TO JOIN</span><span>&bull;</span>
      <span>AI BUILDS YOUR PAGE IN SECONDS</span><span>&bull;</span>
      <span>FOUND ON GOOGLE + CHATGPT + CLAUDE + PERPLEXITY</span><span>&bull;</span>
      <span>ONE PAGE, EVERY SOCIAL CHANNEL</span><span>&bull;</span>
      <span>REAL REVIEWS, REAL CUSTOMERS</span><span>&bull;</span>
      <span>LIVE ANALYTICS</span><span>&bull;</span>
    <?php endfor; ?>
  </div>
</div>

<!-- hero -->
<section class="lp-hero">
  <div class="wrap">
    <span class="lp-badge">The promotion engine for small business &amp; creators</span>
    <h1 class="lp-h1">GET FOUND<br><span class="lp-accent">EVERYWHERE</span><br>IN MINUTES.</h1>
    <p class="lp-sub">The only all-in-one platform where <strong>AI builds your page</strong>, search engines
      and AI assistants <strong>promote it</strong>, and your reviews, socials and content
      <strong>work together</strong> to bring customers in. <span class="lp-free">100% free to join.</span></p>
    <div class="lp-ctas">
      <a class="btn btn-primary lp-btn-big" href="/signup">Join free — get listed today</a>
      <a class="btn btn-ghost lp-btn-big" href="/browse">Explore the directory</a>
    </div>
    <div class="lp-pills">
      <span class="lp-pill-label">Your page gets seen on</span>
      <span class="lp-pill">Google</span>
      <span class="lp-pill">ChatGPT</span>
      <span class="lp-pill">Claude</span>
      <span class="lp-pill">Perplexity</span>
      <span class="lp-pill">Bing</span>
    </div>
  </div>
</section>

<!-- icon strip -->
<div class="lp-strip">
  <div class="lp-strip-track">
    <div><b>Live in minutes</b><small>AI does the writing</small></div>
    <div><b>Free forever</b><small>No card required</small></div>
    <div><b>Human-reviewed</b><small>Quality you can trust</small></div>
    <div><b>Social hub</b><small>All 7 networks linked</small></div>
    <div><b>Reviews</b><small>Build your reputation</small></div>
    <div><b>Analytics</b><small>Views, clicks &amp; calls</small></div>
  </div>
</div>

<!-- platform mockup -->
<section class="lp-section">
  <div class="wrap">
    <h2 class="lp-h2">The All-In-One Platform That <span class="lp-accent">Does It For You</span></h2>
    <p class="lp-center-sub">You run your business. <?= e($site) ?> runs your presence — one profile that
      feeds search engines, AI assistants and your customers everything they need to choose you.</p>

    <?php require __DIR__ . '/_promo-graphic.php'; ?>
  </div>
</section>

<!-- benefit tiles -->
<section class="lp-section lp-tint">
  <div class="wrap">
    <div class="lp-tiles">
      <div><b>Launch in minutes</b><p>Paste your website — AI writes your whole page.</p></div>
      <div><b>100% free</b><p>Free listing forever. Upgrade only if you want more.</p></div>
      <div><b>No tech skills</b><p>If you can copy &amp; paste, you can join.</p></div>
      <div><b>SEO built in</b><p>Every page engineered to rank and be cited.</p></div>
      <div><b>Promote your socials</b><p>Facebook, Instagram, TikTok, YouTube &amp; more.</p></div>
      <div><b>See results</b><p>Real-time views, clicks and calls in your dashboard.</p></div>
    </div>
  </div>
</section>

<!-- steps -->
<section class="lp-section">
  <div class="wrap">
    <h2 class="lp-h2">Three Steps To <span class="lp-accent">More Customers</span></h2>
    <div class="lp-steps">
      <div class="card card-pad step"><span class="step-num">1</span><h3>AI builds your page</h3>
        <p class="mute">Sign up free and paste your website. Our AI writes your description, picks your category and links your socials — you just hit submit.</p></div>
      <div class="card card-pad step"><span class="step-num">2</span><h3>We put you everywhere</h3>
        <p class="mute">Your page is engineered for Google rankings and structured so ChatGPT, Claude and Perplexity can recommend you by name.</p></div>
      <div class="card card-pad step"><span class="step-num">3</span><h3>You grow</h3>
        <p class="mute">Collect reviews, showcase photos and video, and track every view, click and call. More participation = more reach.</p></div>
    </div>
    <p style="text-align:center;margin-top:26px">
      <a class="btn btn-primary lp-btn-big" href="/signup">Start free — takes 5 minutes</a>
    </p>
  </div>
</section>

<!-- pricing -->
<section class="lp-section lp-tint" id="plans">
  <div class="wrap">
    <h2 class="lp-h2">Start Free. <span class="lp-accent">Upgrade When You're Ready.</span></h2>
    <p class="lp-center-sub">Every plan gets the AI page builder, SEO placement and human-reviewed quality.</p>
    <div class="lp-plans">
      <?php foreach ($planList as $key => $p): ?>
        <div class="lp-plan <?= $key === 'pro' ? 'hot' : '' ?>">
          <?php if ($key === 'pro'): ?><span class="lp-plan-tag">MOST POPULAR</span><?php endif; ?>
          <h3><?= e($p['label']) ?></h3>
          <div class="lp-price">$<?= number_format($p['price'], 0) ?><small>/mo</small></div>
          <p class="lp-plan-blurb"><?= e($p['blurb']) ?></p>
          <ul>
            <li><?= (int)$p['max_listings'] ?> listing<?= $p['max_listings'] > 1 ? 's' : '' ?></li>
            <li>AI page builder</li>
            <li>Google + AI search placement</li>
            <?php if ($p['enhanced']): ?><li>Photo gallery, video &amp; social hub</li><li>Verified badge</li><?php endif; ?>
            <?php if ($p['analytics']): ?><li>Views, clicks &amp; calls analytics</li><?php endif; ?>
            <?php if ($p['featured']): ?><li>Priority placement — homepage &amp; top of results</li><?php endif; ?>
          </ul>
          <?php if ($key === 'free'): ?>
            <a class="btn btn-ghost btn-block" href="/signup">Join free</a>
          <?php else: ?>
            <form method="post" action="/stripe/checkout"><?= csrf_field() ?>
              <input type="hidden" name="plan" value="<?= e($key) ?>">
              <button class="btn btn-primary btn-block">Get <?= e($p['label']) ?></button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- two audiences -->
<section class="lp-dark">
  <div class="wrap">
    <h2 class="lp-h2 lp-h2-light">ONE ENGINE. <span class="lp-accent2">TWO WAYS TO GROW.</span></h2>
    <p class="lp-center-sub lp-light-sub">Built for anyone with something to promote.</p>
    <div class="lp-duo">
      <div class="lp-duo-card">
        <span class="lp-duo-tag">FOR SMALL BUSINESSES</span>
        <h3>Turn searches into customers</h3>
        <ul>
          <li>Professional storefront page with your branding</li>
          <li>Show up when locals search — and when they ask AI</li>
          <li>Reviews and a verified badge that build instant trust</li>
          <li>Analytics that show exactly what's working</li>
        </ul>
        <a class="btn btn-primary btn-block" href="/signup">List my business free</a>
      </div>
      <div class="lp-duo-card">
        <span class="lp-duo-tag alt">FOR INFLUENCERS &amp; CREATORS</span>
        <h3>Extend your reach beyond the feed</h3>
        <ul>
          <li>One hub page linking every channel you're on</li>
          <li>Get discovered by brands and fans searching your niche</li>
          <li>AI-search visibility your bio link will never give you</li>
          <li>Free forever — your audience, your page, your terms</li>
        </ul>
        <a class="btn btn-primary btn-block" href="/signup">Claim my creator page</a>
      </div>
    </div>
  </div>
</section>

<!-- community / SEO sections -->
<section class="section wrap">
  <div class="section-head"><h2>Browse by category</h2><a class="mute" href="/browse">Browse all →</a></div>
  <div class="grid grid-4">
    <?php foreach (array_slice($cats, 0, 8) as $c): ?>
      <a class="card cat-card" href="/category/<?= e($c['id']) ?>">
        <strong><?= e($c['label']) ?></strong>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($featured): ?>
<section class="section wrap">
  <div class="section-head"><h2>Featured members</h2></div>
  <div class="grid grid-3">
    <?php foreach ($featured as $b): ?>
      <a class="card listing" href="<?= e(business_path($b)) ?>">
        <span class="avatar"><?php if (!empty($b['logo_url'])): ?><img src="<?= e($b['logo_url']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:12px"><?php else: ?><?= e(mb_substr($b['name'], 0, 1)) ?><?php endif; ?></span>
        <span class="listing-body">
          <span class="listing-title"><?= e($b['name']) ?> <span class="badge badge-featured">Featured</span></span>
          <span class="listing-meta"><?= e($b['category_label'] ?? '') ?> · <?= e($b['city_name']) ?></span>
          <span class="listing-meta"><span class="stars">★</span> <?= fmt_rating($b['rating']) ?> (<?= (int)$b['review_count'] ?>)</span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($newest): ?>
<section class="section wrap">
  <div class="section-head"><h2>Just joined</h2><span class="mute">Welcome our newest members</span></div>
  <div class="grid grid-3">
    <?php foreach ($newest as $b): ?>
      <a class="card listing" href="<?= e(business_path($b)) ?>">
        <span class="avatar"><?php if (!empty($b['logo_url'])): ?><img src="<?= e($b['logo_url']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:12px"><?php else: ?><?= e(mb_substr($b['name'], 0, 1)) ?><?php endif; ?></span>
        <span class="listing-body">
          <span class="listing-title"><?= e($b['name']) ?>
            <?php if ($b['tier'] === 'pro'): ?><span class="badge badge-pro">Pro</span><?php endif; ?>
            <?php if ($b['verified']): ?><span class="badge badge-verified">Verified</span><?php endif; ?>
          </span>
          <span class="listing-meta"><?= e($b['category_label'] ?? '') ?> · <?= e($b['city_name']) ?></span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section wrap">
  <div class="section-head"><h2>Top locations</h2><a class="mute" href="/browse">All locations →</a></div>
  <div>
    <?php foreach ($popCities as $ci): ?>
      <a class="chip" href="<?= e(city_path($ci)) ?>"><?= e($ci['name']) ?></a>
    <?php endforeach; ?>
    <?php foreach ($popStates as $s): ?>
      <a class="chip" href="/us/<?= e($s['slug']) ?>"><?= e($s['name']) ?></a>
    <?php endforeach; ?>
    <?php foreach ($popCtries as $co): ?>
      <a class="chip" href="/<?= e(strtolower($co['code'])) ?>"><?= e($co['name']) ?></a>
    <?php endforeach; ?>
  </div>
</section>

<!-- search for visitors -->
<section class="section wrap" style="max-width:760px">
  <h2 style="text-align:center">Looking for a business instead?</h2>
  <form class="search-bar" action="/search" method="get" role="search">
    <input type="text" name="q" placeholder="Search businesses, services, trades…" aria-label="Search">
    <button class="btn btn-primary" type="submit">Search</button>
  </form>
</section>

<!-- final CTA -->
<div class="wrap">
  <div class="lp-final">
    <span class="lp-badge" style="background:rgba(255,255,255,.15);color:#fff">100% free — no credit card, ever</span>
    <h2>YOUR NEXT CUSTOMER IS SEARCHING <span class="lp-accent2">RIGHT NOW.</span></h2>
    <p><?= number_format($stats['listings']) ?> businesses across <?= number_format($stats['countries']) ?> countries are already on <?= e($site) ?>. Don't let them get found first.</p>
    <a class="btn lp-btn-big" style="background:#fff;color:var(--accent)" href="/signup">Join free today →</a>
  </div>
</div>
