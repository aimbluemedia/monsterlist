<section class="hero hero-brand">
  <div class="wrap">
    <span class="free-badge">🎉 100% free to join — no credit card, ever</span>
    <h1>The AI-powered promotion engine<br>for small business.</h1>
    <p class="hero-sub">Paste your website and our AI builds your business page in seconds — then
      <?= e(setting('site_name')) ?> promotes it where customers actually look:
      <strong>Google, ChatGPT, Claude and Perplexity</strong>. Content, reviews,
      social links and analytics — all in one free profile.</p>
    <div class="hero-ctas">
      <a class="btn btn-primary btn-lg" href="/signup">Join free &amp; get listed</a>
      <a class="btn btn-ghost btn-lg" href="/browse">Explore the directory</a>
    </div>
    <form class="search-bar" action="/search" method="get" role="search" style="margin-top:26px">
      <input type="text" name="q" placeholder="Or search businesses, services, trades…" aria-label="Search">
      <button class="btn btn-primary" type="submit">Search</button>
    </form>
  </div>
</section>

<section class="stats-band">
  <div class="wrap stats-row">
    <div><strong><?= number_format($stats['listings']) ?></strong><span>live business listings</span></div>
    <div><strong><?= number_format($stats['countries']) ?></strong><span>countries covered</span></div>
    <div><strong><?= number_format($stats['categories']) ?></strong><span>business categories</span></div>
    <div><strong>Free</strong><span>to join, forever</span></div>
  </div>
</section>

<section class="section wrap">
  <div class="section-head" style="justify-content:center;text-align:center"><h2>Up and running in three steps</h2></div>
  <div class="grid grid-3 steps">
    <div class="card card-pad step">
      <span class="step-num">1</span>
      <h3>✨ Let AI build your page</h3>
      <p class="mute">Sign up free, paste your website address, and our AI reads it and writes your
        listing for you — description, category, contact details, social links. You just review and submit.</p>
    </div>
    <div class="card card-pad step">
      <span class="step-num">2</span>
      <h3>🔍 Get found everywhere</h3>
      <p class="mute">Your page is built to rank on Google <em>and</em> to be read by AI assistants like
        ChatGPT, Claude and Perplexity — so when customers ask "who's the best near me," you're in the answer.</p>
    </div>
    <div class="card card-pad step">
      <span class="step-num">3</span>
      <h3>📈 Grow with the community</h3>
      <p class="mute">Collect reviews, add photos and video, link every social channel, and watch your
        views and clicks in your dashboard. The more you participate, the more customers find you.</p>
    </div>
  </div>
  <p style="text-align:center;margin-top:22px">
    <a class="btn btn-primary btn-lg" href="/signup">Create your free listing →</a>
    <span class="mute" style="display:block;margin-top:8px;font-size:.88rem">Takes about five minutes. Every listing is human-reviewed, so the directory stays trustworthy.</span>
  </p>
</section>

<section class="section wrap">
  <div class="section-head" style="justify-content:center;text-align:center"><h2>Everything a small business needs to get noticed</h2></div>
  <div class="grid grid-3">
    <div class="card card-pad"><h3>🤖 AI listing builder</h3><p class="mute">Don't stare at a blank form — AI drafts your whole profile from your website in seconds.</p></div>
    <div class="card card-pad"><h3>🌐 Your own storefront page</h3><p class="mute">A clean, professional page with your logo, photos, video, services, hours and contact details.</p></div>
    <div class="card card-pad"><h3>📣 Social media hub</h3><p class="mute">Link Facebook, Instagram, TikTok, YouTube, LinkedIn, Pinterest and X — one page that promotes them all.</p></div>
    <div class="card card-pad"><h3>⭐ Reviews that build trust</h3><p class="mute">Real customer reviews with star ratings, shown to searchers and search engines alike.</p></div>
    <div class="card card-pad"><h3>⚡ Instant search visibility</h3><p class="mute">The moment your listing is approved we ping search engines, and structured data helps AI assistants cite you.</p></div>
    <div class="card card-pad"><h3>📊 Know what's working</h3><p class="mute">See views, website clicks and calls in your dashboard, day by day.</p></div>
  </div>
</section>

<section class="section wrap">
  <div class="section-head"><h2>Browse by category</h2><a class="mute" href="/browse">Browse all →</a></div>
  <div class="grid grid-4">
    <?php foreach (array_slice($cats, 0, 8) as $c): ?>
      <a class="card cat-card" href="/category/<?= e($c['id']) ?>">
        <span class="ico"><?= e($c['icon']) ?></span>
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
  <div class="section-head"><h2>Just joined</h2><span class="mute">Welcome our newest members 👋</span></div>
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
      <a class="chip" href="<?= e(city_path($ci)) ?>"><?= e($ci['flag']) ?> <?= e($ci['name']) ?></a>
    <?php endforeach; ?>
  </div>
  <div style="margin-top:10px">
    <?php foreach ($popStates as $s): ?>
      <a class="chip" href="/us/<?= e($s['slug']) ?>"><?= e($s['name']) ?></a>
    <?php endforeach; ?>
  </div>
  <div style="margin-top:10px">
    <?php foreach ($popCtries as $co): ?>
      <a class="chip" href="/<?= e(strtolower($co['code'])) ?>"><?= e($co['flag']) ?> <?= e($co['name']) ?></a>
    <?php endforeach; ?>
  </div>
</section>

<div class="wrap">
  <div class="cta-band">
    <h2>Your business belongs here. And it costs nothing.</h2>
    <p>Join thousands of small businesses using <?= e(setting('site_name')) ?> to get found by real customers —
      in classic search and in AI answers. Free listing, AI-built page, human-reviewed quality.</p>
    <a class="btn btn-lg" href="/signup">Join free today</a>
  </div>
</div>
