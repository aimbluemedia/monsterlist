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
  <div class="wrap lp-hero-grid">

    <div class="lp-hero-copy">
      <span class="lp-eyebrow">For small businesses</span>
      <?php // Sentence case, not caps: at hero size, all-caps is what makes a
            // page read as a flyer rather than a product. The accent word
            // carries the emphasis instead. ?>
      <h1 class="lp-h1">Get found everywhere<br>customers are<br>
        <span class="lp-accent lp-underline">already looking.</span></h1>
      <p class="lp-sub">List your business once and <strong>MonsterList puts it in front of people
        searching</strong> — on Google, and in the AI assistants they now ask instead.
        Every listing is read by a person before it goes live.</p>

      <div class="lp-ctas">
        <a class="btn btn-primary lp-btn-big" href="/signup">Add your business free</a>
        <a class="btn btn-ghost lp-btn-big" href="/browse">Explore the directory</a>
      </div>

      <ul class="lp-ticks">
        <li>Free forever</li>
        <li>No credit card</li>
        <li>Live in minutes</li>
      </ul>

      <div class="lp-pills">
        <span class="lp-pill-label">Found on</span>
        <span class="lp-pill">Google</span>
        <span class="lp-pill">ChatGPT</span>
        <span class="lp-pill">Claude</span>
        <span class="lp-pill">Perplexity</span>
        <span class="lp-pill">Bing</span>
      </div>
    </div>

    <?php // A picture of the product, drawn in CSS rather than shipped as an
          // image: nothing extra to load, it stays sharp on any screen, and it
          // cannot go out of date the way a screenshot does. Decorative, so it
          // is hidden from screen readers — everything it says is said in the
          // copy beside it. ?>
    <div class="lp-hero-art" aria-hidden="true">
      <div class="lp-mock">
        <div class="lp-mock-search">
          <span class="lp-mock-ico"></span>
          <span class="lp-mock-q">plumbers in Phoenix</span>
          <span class="lp-mock-go">Search</span>
        </div>
        <div class="lp-mock-rows">
          <div class="lp-mock-row lp-mock-row-top">
            <span class="lp-mock-logo">DP</span>
            <span class="lp-mock-txt"><b>Desert Pipe &amp; Drain</b><em>Plumber · Phoenix, AZ</em></span>
            <span class="lp-mock-tag">Featured</span>
          </div>
          <div class="lp-mock-row">
            <span class="lp-mock-logo lp-mock-logo-2">VR</span>
            <span class="lp-mock-txt"><b>Valley Rooter Co.</b><em>Plumber · Phoenix, AZ</em></span>
            <span class="lp-mock-stars">4.9</span>
          </div>
          <div class="lp-mock-row">
            <span class="lp-mock-logo lp-mock-logo-3">CW</span>
            <span class="lp-mock-txt"><b>Cactus Water Works</b><em>Plumber · Tempe, AZ</em></span>
            <span class="lp-mock-stars">4.8</span>
          </div>
        </div>
      </div>

      <div class="lp-float lp-float-ai">
        <span class="lp-float-k">Asked an AI assistant</span>
        <span class="lp-float-q">“Best plumber near Phoenix?”</span>
        <span class="lp-float-a">Desert Pipe &amp; Drain — 4.9★, open now</span>
      </div>

    </div>

  </div>

  <?php // The sister products, from the same list that feeds the band above
        // the footer — one source of truth, so a product added there appears
        // here too. 'short' rather than 'blurb': six cards share one row. ?>
  <div class="wrap lp-member">
    <p class="lp-member-h"><span class="lp-member-tick" aria-hidden="true"></span>Membership includes access to</p>
    <div class="lp-member-grid">
      <?php foreach (toutify_products() as $lpP): ?>
        <div class="lp-member-card">
          <span class="lp-member-ico"><?= toutify_icon($lpP['icon']) ?></span>
          <b><?= e($lpP['name']) ?></b>
          <em><?= e($lpP['short'] ?? '') ?></em>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php // The directory itself, directly under the hero. Somebody arriving at a
      // business directory is usually looking for a business, and everything
      // that used to sit here — the mockup, the benefit tiles, the promotion
      // pitch — is aimed at the other visitor, the one thinking of joining.
      // They still get all of it, just after the proof that the thing works. ?>
<?php if ($featured): ?>
<section class="sky">
  <div class="wrap">
    <div class="sky-head">
      <h2>Featured members</h2>
      <span>Top of the results in their city and category</span>
    </div>
    <div class="mc-grid">
      <?php foreach ($featured as $b): ?>
        <?php $mcBadge = 'Featured'; require __DIR__ . '/_member-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($newest): ?>
<section class="sky sky-alt">
  <div class="wrap">
    <div class="sky-head">
      <h2>Just joined</h2>
      <span>Welcome our newest members</span>
    </div>
    <div class="mc-grid">
      <?php foreach ($newest as $b): ?>
        <?php $mcBadge = 'New'; require __DIR__ . '/_member-card.php'; ?>
      <?php endforeach; ?>
    </div>
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
      <a class="chip" href="<?= e(region_path($s)) ?>"><?= e($s['name']) ?></a>
    <?php endforeach; ?>
    <?php foreach ($popCtries as $co): ?>
      <a class="chip" href="/<?= e(strtolower($co['code'])) ?>"><?= e($co['name']) ?></a>
    <?php endforeach; ?>
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

    <div class="pe-head">
      <span class="pe-eyebrow">AI Promotion Engine</span>
      <h2 class="pe-title">You published it.<br><span>Now get it seen.</span></h2>
      <p class="pe-lead">Paste a link to anything you have already published and the engine does the rest —
        writing the pitch, filing it under the right category and city, and putting it in front of people
        who have never heard of you.</p>
    </div>

    <?php // Three numbered steps, because the question a visitor actually has
          // is "what do I have to do?" — and the honest answer is "paste one
          // link". That was previously buried in the middle of a paragraph. ?>
    <ol class="pe-steps">
      <li>
        <span class="pe-step-n" aria-hidden="true"></span>
        <h3>Paste a link</h3>
        <p>A blog post, a product, a service page, a new review, a video — anything with a URL.
          That is the whole of your side of it.</p>
      </li>
      <li>
        <span class="pe-step-n" aria-hidden="true"></span>
        <h3>The AI writes the pitch</h3>
        <p>It reads the page, writes a short pitch in your voice, and files it under the right
          category and city. Nothing to write, nothing to schedule.</p>
      </li>
      <li>
        <span class="pe-step-n" aria-hidden="true"></span>
        <h3>It goes out</h3>
        <p>To the member network, your storefront page, Google, and the AI assistants your
          customers now ask for recommendations.</p>
      </li>
    </ol>

    <?php // The two lists were 17 bulleted rows in side-by-side columns, which
          // reads as a specification. As chips they are scanned in a second,
          // which is all anybody does with a compatibility list. ?>
    <div class="pe-rows">
      <div class="pe-row">
        <h4>Works with anything you publish</h4>
        <ul>
          <?php foreach ([
              ['blog', 'Blog posts'], ['prod', 'Products'], ['serv', 'Services'], ['star', 'Reviews'],
              ['yt', 'YouTube'], ['fb', 'Facebook'], ['ig', 'Instagram'],
              ['tt', 'TikTok'], ['rd', 'Reddit'], ['pt', 'Pinterest'],
          ] as [$peI, $peL]): ?>
            <li><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-<?= $peI ?>"/></svg><?= e($peL) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="pe-row">
        <h4>Shows up where people look</h4>
        <ul>
          <?php foreach ([
              ['people', 'Member network'], ['blog', 'Your storefront'], ['sr', 'Google Search'],
              ['sp', 'ChatGPT'], ['cl', 'Claude'], ['pp', 'Perplexity'], ['gm', 'Gemini'],
          ] as [$peI, $peL]): ?>
            <li><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-<?= $peI ?>"/></svg><?= e($peL) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <?php // What the engine actually produces, shown rather than described.
          // The old demo animated invented view counts and then admitted in
          // small print that they were invented — which teaches a visitor that
          // the numbers on this page cannot be trusted. The real output is the
          // written pitch, the category and the city, so that is what this
          // shows, and nothing here claims to be a result. ?>
    <div class="pe-demo">
      <div class="pe-try">
        <span class="pe-kicker">See it work</span>
        <h3>What the engine writes</h3>
        <p>Pick something you might publish. This is the promotion it would file for you.</p>
        <div class="pe-picks" role="group" aria-label="Choose an example">
          <button type="button" class="on" data-demo="yt" aria-pressed="true">YouTube</button>
          <button type="button" data-demo="blog" aria-pressed="false">Blog post</button>
          <button type="button" data-demo="ig" aria-pressed="false">Instagram</button>
          <button type="button" data-demo="prod" aria-pressed="false">Product</button>
        </div>
        <div class="pe-field">
          <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-link"/></svg>
          <span id="pe-url">https://youtube.com/watch?v=your-latest-video</span>
        </div>
        <a class="btn pe-boost" href="/signup">Promote my business — free</a>
        <small class="pe-demo-note">Example wording. Yours is written from your own page.</small>
      </div>

      <div class="pe-card" id="pe-card" aria-live="polite">
        <div class="pe-card-head">
          <span class="pe-badge"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-yt" id="pe-badge-icon"/></svg><span id="pe-badge-text">YouTube</span></span>
          <span class="pe-status">Written for you</span>
        </div>
        <div class="pe-thumb" id="pe-thumb">
          <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-yt" id="pe-thumb-icon"/></svg>
        </div>
        <b class="pe-card-title" id="pe-title">How we doubled our bookings in 60 days</b>
        <p class="pe-card-pitch" id="pe-pitch">A short walkthrough of the two changes that filled our
          calendar — what we stopped doing, and the one question we started asking every caller.</p>
        <div class="pe-tags">
          <span class="pe-tag" id="pe-cat">Home services</span>
          <span class="pe-tag pe-tag-city" id="pe-city">Phoenix, AZ</span>
        </div>
        <p class="pe-card-foot">Views, clicks and calls land back in your dashboard.</p>
      </div>
    </div>

    <div class="pe-close">
      <a class="btn btn-primary pe-btn" href="/signup">Add my business free</a>
      <a class="pe-see" href="/promotions">See live member promotions →</a>
      <p class="pe-note">Free forever. No card, no contract. Add your business, drop in your first link,
        and the engine starts working today.</p>
    </div>

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
