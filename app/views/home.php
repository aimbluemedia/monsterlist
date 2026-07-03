<section class="hero">
  <div class="wrap">
    <h1>Find trusted local businesses,<br>anywhere in the world.</h1>
    <p><?= e(setting('site_name')) ?> is the worldwide small-business directory. Every listing is reviewed before it goes live.</p>
    <form class="search-bar" action="/search" method="get" role="search">
      <input type="text" name="q" placeholder="Search businesses, services, trades…" aria-label="Search">
      <button class="btn btn-primary" type="submit">Search</button>
    </form>
  </div>
</section>

<section class="section wrap">
  <div class="section-head"><h2>Top categories</h2><a class="mute" href="/browse">Browse all →</a></div>
  <div class="grid grid-4">
    <?php foreach (array_slice($cats, 0, 8) as $c): ?>
      <a class="card cat-card" href="/category/<?= e($c['id']) ?>">
        <span class="ico"><?= e($c['icon']) ?></span>
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
  <div class="section-head"><h2>New on <?= e(setting('site_name')) ?></h2></div>
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
      <a class="chip" href="<?= e(city_path($ci)) ?>"><?= e($ci['flag']) ?> <?= e($ci['name']) ?></a>
    <?php endforeach; ?>
  </div>
  <div style="margin-top:10px">
    <?php foreach ($popStates as $s): ?>
      <a class="chip" href="/us/<?= e($s['slug']) ?>"><?= e($s['name']) ?></a>
    <?php endforeach; ?>
  </div>
  <div style="margin-top:10px">
    <?php foreach ($popCtries as $co): ?>
      <a class="chip" href="/<?= e(strtolower($co['code'])) ?>"><?= e($co['flag']) ?> <?= e($co['name']) ?></a>
    <?php endforeach; ?>
  </div>
</section>

<div class="wrap">
  <div class="cta-band">
    <h2>Own a business? Get listed free.</h2>
    <p>Create a free listing in minutes — or go Pro for a full storefront with photos, video and analytics.</p>
    <a class="btn" href="/add-listing">Add your business</a>
  </div>
</div>
