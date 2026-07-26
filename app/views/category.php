<div class="wrap">
  <nav class="crumbs">
    <?php foreach ($crumbs as $i => $cr): ?>
      <?php if ($i): ?><span>›</span><?php endif; ?>
      <?php if ($i < count($crumbs) - 1): ?><a href="<?= e($cr['path']) ?>"><?= e($cr['name']) ?></a>
      <?php else: ?><?= e($cr['name']) ?><?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <section class="section">
    <h1><?= e($cat['label']) ?><?= $place ? ' in ' . e($place) : '' ?></h1>
    <p class="mute" style="max-width:760px"><?= e($intro) ?></p>

    <?php if (!$list): ?>
      <div class="card card-pad" style="margin-top:16px">
        <strong>No listings here yet.</strong>
        <p class="mute">Run a <?= e(strtolower($cat['label'])) ?> business<?= $place ? ' in ' . e($place) : '' ?>? Get listed free.</p>
        <a class="btn btn-primary" href="/add-listing">Add your business</a>
      </div>
    <?php else: ?>
      <div class="grid" style="margin-top:16px">
        <?php foreach ($list as $b): ?>
          <a class="card listing" href="<?= e(business_path($b)) ?>">
            <span class="avatar"><?php if (!empty($b['logo_url'])): ?><img src="<?= e($b['logo_url']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:12px"><?php else: ?><?= e(mb_substr($b['name'], 0, 1)) ?><?php endif; ?></span>
            <span class="listing-body">
              <span class="listing-title">
                <?= e($b['name']) ?>
                <?php if ($b['tier'] === 'featured'): ?><span class="badge badge-featured">Featured</span><?php endif; ?>
                <?php if ($b['tier'] === 'pro'): ?><span class="badge badge-pro">Pro</span><?php endif; ?>
                <?php if ($b['verified']): ?><span class="badge badge-verified">Verified</span><?php endif; ?>
              </span>
              <span class="listing-meta"><?= e($b['city_name']) ?><?= !empty($b['region_name']) ? ', ' . e($b['region_name']) : '' ?>, <?= e($b['country_name']) ?></span>
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
            <?php else: ?><a href="<?= e($locPath) ?><?= $p > 1 ? '?page=' . $p : '' ?>"><?= $p ?></a><?php endif; ?>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($nearby): ?>
      <h2 style="margin-top:34px"><?= e($cat['label']) ?> in nearby cities</h2>
      <div>
        <?php foreach ($nearby as $nc): ?>
          <?php $ncPath = '/category/' . $cat['id'] . '/' . strtolower($nc['country_code']) . (!empty($nc['region_slug']) ? '/' . $nc['region_slug'] : '') . '/' . $nc['slug']; ?>
          <a class="chip" href="<?= e($ncPath) ?>"><?= e($nc['name']) ?> (<?= (int)$nc['cnt'] ?>)</a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($otherCats && $city): ?>
      <h2 style="margin-top:26px">Other services in <?= e($city['name']) ?></h2>
      <div>
        <?php foreach ($otherCats as $oc): ?>
          <?php $ocPath = '/category/' . $oc['id'] . '/' . strtolower($country['code']) . ($region ? '/' . $region['slug'] : '') . '/' . $city['slug']; ?>
          <a class="chip" href="<?= e($ocPath) ?>"><?= e($oc['label']) ?> (<?= (int)$oc['cnt'] ?>)</a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h2 style="margin-top:34px">Frequently asked questions</h2>
    <?php foreach ($faq as $qa): ?>
      <div class="card card-pad" style="margin-bottom:10px;max-width:760px">
        <strong><?= e($qa[0]) ?></strong>
        <p class="mute" style="margin:6px 0 0"><?= e($qa[1]) ?></p>
      </div>
    <?php endforeach; ?>
  </section>
</div>
