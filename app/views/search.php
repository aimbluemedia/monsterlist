<div class="wrap">
  <section class="section">
    <h1>Search the directory</h1>
    <form class="search-bar" action="/search" method="get" style="margin:18px 0 26px;max-width:640px">
      <input type="text" name="q" value="<?= e($qstr) ?>" placeholder="Search businesses, services, trades…" aria-label="Search">
      <button class="btn btn-primary" type="submit">Search</button>
    </form>

    <?php if ($qstr === ''): ?>
      <p class="mute">Try “roofing”, “web design”, “catering” — or <a href="/browse" style="color:var(--accent);font-weight:700">browse by location</a>.</p>
    <?php elseif (!$list): ?>
      <div class="card card-pad">No results for “<?= e($qstr) ?>”. Try a broader term or <a href="/browse" style="color:var(--accent);font-weight:700">browse by location</a>.</div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($list as $b): ?>
          <a class="card listing" href="<?= e(business_path($b)) ?>">
            <span class="avatar"><?php if ($logo = listing_logo($b)): ?><img src="<?= e($logo) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:12px"><?php else: ?><?= e(mb_substr($b['name'], 0, 1)) ?><?php endif; ?></span>
            <span class="listing-body">
              <span class="listing-title">
                <?= e($b['name']) ?>
                <?php if ($b['tier'] === 'featured'): ?><span class="badge badge-featured">Featured</span><?php endif; ?>
                <?php if ($b['tier'] === 'pro'): ?><span class="badge badge-pro">Pro</span><?php endif; ?>
                <?php if ($b['verified']): ?><span class="badge badge-verified">Verified</span><?php endif; ?>
              </span>
              <span class="listing-meta"><?= e($b['category_label'] ?? '') ?> · <?= e($b['city_name']) ?><?= !empty($b['region_name']) ? ', ' . e($b['region_name']) : '' ?>, <?= e($b['country_name']) ?></span>
              <?php if ($b['tagline']): ?><span class="listing-meta"><?= e($b['tagline']) ?></span><?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
      <?php if (count($list) === 20): ?>
        <nav class="pager"><a href="/search?q=<?= e(urlencode($qstr)) ?>&page=<?= $page + 1 ?>">Next page →</a></nav>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>
