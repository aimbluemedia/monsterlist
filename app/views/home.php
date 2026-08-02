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

<!-- promotion engine — the core pitch -->
<section class="pe" id="engine">
  <?php require __DIR__ . '/_icons.php'; ?>
  <div class="pe-glow" aria-hidden="true"></div>
  <div class="wrap pe-inner">

    <span class="pe-eyebrow">AI Promotion Engine</span>
    <h2 class="pe-title">YOU POSTED IT.<br><span>NOBODY SAW IT.</span></h2>
    <p class="pe-lead">You wrote the post, filmed the video, listed the product — and it reached
      almost no one. The AI Promotion Engine takes anything you publish and puts it in front of
      <strong>people who have never heard of your business</strong>: our member network, Google,
      and the AI assistants your customers now ask for recommendations. One link. Free to start.</p>

    <h3 class="pe-net-h">The engine promotes <span>all of it</span></h3>
    <div class="pe-net">
      <?php foreach ([
          ['blog', 'Blog Posts', '#2563eb'], ['prod', 'Products',  '#7c3aed'],
          ['serv', 'Services',   '#0ca678'], ['star', 'Reviews',   '#f59e0b'],
          ['yt',   'YouTube',    '#dc2626'], ['fb',   'Facebook',  '#1877f2'],
          ['ig',   'Instagram',  '#db2777'], ['tt',   'TikTok',    '#0f172a'],
          ['rd',   'Reddit',     '#f97316'], ['pt',   'Pinterest', '#e11d48'],
      ] as $i => [$peId, $peLabel, $peColor]): ?>
        <div class="pe-net-tile" style="--c:<?= $peColor ?>;--d:<?= round($i * .05, 2) ?>s">
          <span class="pe-net-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-<?= $peId ?>"/></svg></span>
          <b><?= e($peLabel) ?></b>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="pe-demo">
      <div class="pe-try">
        <span class="pe-kicker">Try it</span>
        <h3>See what it does to a post.</h3>
        <p>Pick something you'd publish and press the button — this is what the engine does with it.</p>
        <div class="pe-field">
          <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-link"/></svg>
          <input type="text" id="pe-url" value="https://youtube.com/watch?v=your-latest-video"
                 aria-label="Example link to boost" spellcheck="false">
        </div>
        <div class="pe-picks" role="group" aria-label="Choose an example">
          <button type="button" class="on" data-demo="yt">YouTube</button>
          <button type="button" data-demo="blog">Blog post</button>
          <button type="button" data-demo="ig">Instagram</button>
          <button type="button" data-demo="prod">Product</button>
        </div>
        <button type="button" class="btn pe-boost" id="pe-boost">Boost it now</button>
        <small class="pe-demo-note">Interactive preview — illustrative numbers, no account needed.</small>
      </div>

      <div class="pe-card" id="pe-card">
        <div class="pe-card-head">
          <span class="pe-badge"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-yt" id="pe-badge-icon"/></svg><span id="pe-badge-text">YouTube</span></span>
          <span class="pe-status" id="pe-status">Ready</span>
        </div>
        <div class="pe-thumb" id="pe-thumb">
          <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-yt" id="pe-thumb-icon"/></svg>
        </div>
        <b class="pe-card-title" id="pe-title">How we doubled our bookings in 60 days</b>
        <div class="pe-metrics">
          <div><b data-boost="128">0</b><small>Views</small></div>
          <div><b data-boost="34">0</b><small>Clicks</small></div>
          <div><b data-boost="19">0</b><small>Engagements</small></div>
        </div>
        <div class="pe-bar"><i id="pe-bar-fill"></i></div>
        <div class="pe-faces" id="pe-faces">
          <span>AM</span><span>KT</span><span>RJ</span><span>LP</span><span>DS</span><span>BW</span>
          <em id="pe-faces-text">members ready to boost</em>
        </div>
      </div>
    </div>

    <div class="pe-close">
      <a class="btn btn-primary pe-btn" href="/signup">Promote my business — free</a>
      <a class="pe-see" href="/promotions">See live member promotions →</a>
      <p class="pe-note">Free forever. No card, no contract. Add your business, drop in your first link,
        and the engine starts working today.</p>
    </div>

  </div>
</section>

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
