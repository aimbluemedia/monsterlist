<div class="wrap">
  <nav class="crumbs"><a href="/">Home</a><span>›</span><?= e($country['name']) ?></nav>
  <section class="section">
    <h1>Small businesses in <?= e($country['name']) ?></h1>
    <?php
      // A country can show both lists: regions where the extra level is worth
      // having, plus any cities that sit directly under the country. When both
      // are here they get their own heading — a flat list of "Bavaria, Berlin"
      // gives no clue that one leads to cities and the other to businesses.
      $rWord = $country['code'] === 'US' ? 'state' : 'region';
      $both  = $regions && $cities;
    ?>
    <?php if (!$regions && !$cities): ?>
      <p class="mute">Choose a city to see trusted local listings.</p>
      <div class="card card-pad">No cities listed yet for <?= e($country['name']) ?>. <a href="/add-listing" style="color:var(--accent);font-weight:700">Add the first business →</a></div>
    <?php else: ?>
      <p class="mute">
        <?php if ($both): ?>Choose a <?= e($rWord) ?> or a city to see trusted local listings.
        <?php elseif ($regions): ?>Choose a <?= e($rWord) ?> to browse cities and local listings.
        <?php else: ?>Choose a city to see trusted local listings.<?php endif; ?>
      </p>
      <input type="text" placeholder="Filter…" data-filter="#geo-list" style="max-width:320px;margin-bottom:14px">
      <div id="geo-list">
        <?php if ($regions): ?>
          <?php if ($both): ?><h2 class="geo-group">By <?= e($rWord) ?></h2><?php endif; ?>
          <div class="browse-list">
            <?php foreach ($regions as $r): ?>
              <a href="<?= e(region_path($r)) ?>"><?= e($r['name']) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($cities): ?>
          <?php if ($both): ?><h2 class="geo-group">By city</h2><?php endif; ?>
          <div class="browse-list">
            <?php foreach ($cities as $ci): ?>
              <a href="/<?= e(strtolower($country['code'])) ?>/<?= e($ci['slug']) ?>"><?= e($ci['name']) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
