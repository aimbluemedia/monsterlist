<div class="wrap">
  <nav class="crumbs"><a href="/">Home</a><span>›</span>Browse</nav>
  <section class="section">
    <h1>Browse the directory</h1>
    <p class="mute">Pick a category, or drill down by location: country → state → city.</p>

    <h2 style="margin-top:26px">Categories</h2>
    <div class="grid grid-4">
      <?php foreach ($cats as $c): ?>
        <a class="card cat-card" href="/category/<?= e($c['id']) ?>">
          <strong><?= e($c['label']) ?></strong>
        </a>
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
