<div class="wrap">
  <nav class="crumbs">
    <?php foreach ($crumbs as $i => $cr): ?>
      <?php if ($i): ?><span>›</span><?php endif; ?>
      <?php if ($i < count($crumbs) - 1): ?><a href="<?= e($cr['path']) ?>"><?= e($cr['name']) ?></a>
      <?php else: ?><?= e($cr['name']) ?><?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <section class="section">
    <h1>Local businesses in <?= e($city['name']) ?><?= $region ? ', ' . e($region['name']) : '' ?></h1>
    <p class="mute"><?= (int)$total ?> listing<?= $total === 1 ? '' : 's' ?> in <?= e($city['name']) ?>, <?= e($country['name']) ?>.</p>

    <?php if (!empty($cityCats)): ?>
      <div style="margin-bottom:16px">
        <?php foreach ($cityCats as $cc2): ?>
          <a class="chip" href="<?= e(sprintf($catCityBase, $cc2['id'])) ?>"><?= e($cc2['label']) ?> (<?= (int)$cc2['cnt'] ?>)</a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$list): ?>
      <div class="card card-pad">
        <strong>No businesses listed here yet.</strong>
        <p class="mute">Own a business in <?= e($city['name']) ?>? Be the first — free listings take minutes.</p>
        <a class="btn btn-primary" href="/add-listing">Add your business</a>
      </div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($list as $b): ?>
          <a class="card listing" href="<?= e($path . '/' . $b['slug']) ?>">
            <span class="avatar"><?php if ($logo = listing_logo($b)): ?><img src="<?= e($logo) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:12px"><?php else: ?><?= e(mb_substr($b['name'], 0, 1)) ?><?php endif; ?></span>
            <span class="listing-body">
              <span class="listing-title">
                <?= e($b['name']) ?>
                <?php if ($b['tier'] === 'featured'): ?><span class="badge badge-featured">Featured</span><?php endif; ?>
                <?php if ($b['tier'] === 'pro'): ?><span class="badge badge-pro">Pro</span><?php endif; ?>
                <?php if ($b['verified']): ?><span class="badge badge-verified">Verified</span><?php endif; ?>
              </span>
              <span class="listing-meta"><?= e($b['category_label'] ?? 'Local business') ?></span>
              <?php if ($b['tagline']): ?><span class="listing-meta"><?= e($b['tagline']) ?></span><?php endif; ?>
              <?php if ((float)$b['rating'] > 0): ?>
                <span class="listing-meta"><span class="stars">★</span> <?= fmt_rating($b['rating']) ?> · <?= (int)$b['review_count'] ?> review<?= (int)$b['review_count'] === 1 ? '' : 's' ?></span>
              <?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($pages > 1): ?>
        <nav class="pager">
          <?php for ($p = 1; $p <= $pages; $p++): ?>
            <?php if ($p === $page): ?><span class="current"><?= $p ?></span>
            <?php else: ?><a href="<?= e($path) ?><?= $p > 1 ? '?page=' . $p : '' ?>"><?= $p ?></a><?php endif; ?>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>
