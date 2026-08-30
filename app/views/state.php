<div class="wrap">
  <nav class="crumbs">
    <a href="/">Home</a><span>›</span><a href="/<?= e(strtolower($country['code'])) ?>"><?= e($country['name']) ?></a><span>›</span><?= e($region['name']) ?>
  </nav>
  <section class="section">
    <h1>Local businesses in <?= e($region['name']) ?></h1>
    <p class="mute">Choose a city to see trusted local listings.</p>
    <?php if (!$cities): ?>
      <div class="card card-pad">No cities listed yet in <?= e($region['name']) ?>. <a href="/add-listing" style="color:var(--accent);font-weight:700">Add the first business →</a></div>
    <?php else: ?>
      <input type="text" placeholder="Filter cities…" data-filter="#geo-list" style="max-width:320px;margin-bottom:14px">
      <div class="browse-list" id="geo-list">
        <?php foreach ($cities as $ci): ?>
          <a href="<?= e($path) ?>/<?= e($ci['slug']) ?>"><?= e($ci['name']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
