<div class="wrap">
  <nav class="crumbs"><a href="/">Home</a><span>›</span><?= e($country['name']) ?></nav>
  <section class="section">
    <h1>Small businesses in <?= e($country['name']) ?></h1>
    <?php if ($isUS): ?>
      <p class="mute">Choose a state to browse cities and local listings.</p>
      <input type="text" placeholder="Filter states…" data-filter="#geo-list" style="max-width:320px;margin-bottom:14px">
      <div class="browse-list" id="geo-list">
        <?php foreach ($regions as $r): ?>
          <a href="/us/<?= e($r['slug']) ?>"><?= e($r['name']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="mute">Choose a city to see trusted local listings.</p>
      <?php if (!$cities): ?>
        <div class="card card-pad">No cities listed yet for <?= e($country['name']) ?>. <a href="/add-listing" style="color:var(--accent);font-weight:700">Add the first business →</a></div>
      <?php else: ?>
      <input type="text" placeholder="Filter cities…" data-filter="#geo-list" style="max-width:320px;margin-bottom:14px">
      <div class="browse-list" id="geo-list">
        <?php foreach ($cities as $ci): ?>
          <a href="/<?= e(strtolower($country['code'])) ?>/<?= e($ci['slug']) ?>"><?= e($ci['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>
