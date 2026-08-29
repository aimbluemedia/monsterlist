<div class="wrap">
  <nav class="crumbs"><a href="/">Home</a><span>›</span>Browse</nav>
  <section class="section">
    <h1>Browse the directory</h1>
    <p class="mute">Pick a category or a trade, or drill down by location: country → state → city.</p>

    <?php // Both levels. The category is the heading and a link of its own; the
          // trades under it are what somebody is actually looking for — "I need
          // a plumber", not "I need home & repair" — and each is a real page. ?>
    <h2 style="margin-top:26px">Categories &amp; trades</h2>
    <div class="bcat-grid">
      <?php foreach ($cats as $c): ?>
        <?php $mine = $subs[$c['id']] ?? []; ?>
        <div class="bcat">
          <a class="bcat-head" href="/category/<?= e($c['id']) ?>">
            <?php if (!empty($c['icon'])): ?><span class="bcat-icon"><?= e($c['icon']) ?></span><?php endif; ?>
            <strong><?= e($c['label']) ?></strong>
          </a>
          <?php if ($mine): ?>
            <div class="bcat-subs">
              <?php foreach ($mine as $key => $label): ?>
                <a href="/category/<?= e($c['id']) ?>?type=<?= e(rawurlencode((string)$key)) ?>"><?= e($label) ?></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <h2 style="margin-top:34px">Countries</h2>
    <input type="text" placeholder="Filter countries…" data-filter="#country-list" style="max-width:320px;margin-bottom:14px">
    <div class="browse-list" id="country-list">
      <?php foreach ($countries as $co): ?>
        <a href="/<?= e(strtolower($co['code'])) ?>"><?= e($co['name']) ?></a>
      <?php endforeach; ?>
    </div>
  </section>
</div>
