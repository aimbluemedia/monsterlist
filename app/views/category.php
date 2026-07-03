<div class="wrap">
  <nav class="crumbs"><a href="/">Home</a><span>›</span><a href="/browse">Browse</a><span>›</span><?= e($cat['label']) ?></nav>
  <section class="section">
    <h1><?= e($cat['icon']) ?> <?= e($cat['label']) ?></h1>
    <p class="mute"><?= (int)$total ?> business<?= $total === 1 ? '' : 'es' ?> worldwide in this category.</p>

    <?php if (!$list): ?>
      <div class="card card-pad">
        <strong>No listings in this category yet.</strong>
        <p class="mute">Run a <?= e(strtolower($cat['label'])) ?> business? Get listed free.</p>
        <a class="btn btn-primary" href="/add-listing">Add your business</a>
      </div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($list as $b): ?>
          <a class="card listing" href="<?= e(business_path($b)) ?>">
            <span class="avatar"><?= e(mb_substr($b['name'], 0, 1)) ?></span>
            <span class="listing-body">
              <span class="listing-title">
                <?= e($b['name']) ?>
                <?php if ($b['tier'] === 'featured'): ?><span class="badge badge-featured">Featured</span><?php endif; ?>
                <?php if ($b['tier'] === 'pro'): ?><span class="badge badge-pro">Pro</span><?php endif; ?>
                <?php if ($b['verified']): ?><span class="badge badge-verified">Verified</span><?php endif; ?>
              </span>
              <span class="listing-meta"><?= e($b['city_name']) ?><?= !empty($b['region_name']) ? ', ' . e($b['region_name']) : '' ?>, <?= e($b['country_name']) ?></span>
              <?php if ($b['tagline']): ?><span class="listing-meta"><?= e($b['tagline']) ?></span><?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
      <?php if ($pages > 1): ?>
        <nav class="pager">
          <?php for ($p = 1; $p <= $pages; $p++): ?>
            <?php if ($p === $page): ?><span class="current"><?= $p ?></span>
            <?php else: ?><a href="/category/<?= e($cat['id']) ?><?= $p > 1 ? '?page=' . $p : '' ?>"><?= $p ?></a><?php endif; ?>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>
