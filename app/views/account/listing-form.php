<?php
$editing = $biz !== null;
$v = fn(string $field, $default = '') => post($field) !== '' && $_SERVER['REQUEST_METHOD'] === 'POST'
    ? post($field)
    : ($editing ? (string)($biz[$field] ?? $default) : $default);
$social = $editing ? (json_decode((string)$biz['social'], true) ?: []) : [];
$galleryText = '';
if (!empty($gallery)) $galleryText = implode("\n", array_column($gallery, 'url'));
$selCountry = $_SERVER['REQUEST_METHOD'] === 'POST' ? strtoupper(post('country')) : (!empty($cityRow) ? $cityRow['country_code'] : 'US');
$selRegion  = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('region') : (!empty($cityRow['region_slug']) ? $cityRow['region_slug'] : '');
$selCity    = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('city') : (!empty($cityRow) ? $cityRow['name'] : '');
?>
<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <h1><?= $editing ? 'Edit listing' : 'New listing' ?></h1>
    <?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" class="card card-pad"><?= csrf_field() ?>
      <h3>Basics</h3>
      <div class="form-grid">
        <div>
          <label>Business name *</label>
          <input type="text" name="name" value="<?= e($v('name')) ?>" required maxlength="180">
        </div>
        <div>
          <label>Category *</label>
          <select name="category_id" required>
            <option value="">Choose…</option>
            <?php foreach ($cats as $c): ?>
              <option value="<?= e($c['id']) ?>" <?= $v('category_id') === $c['id'] ? 'selected' : '' ?>><?= e($c['icon']) ?> <?= e($c['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <label>Tagline</label>
      <input type="text" name="tagline" value="<?= e($v('tagline')) ?>" maxlength="255" placeholder="One line that sells your business">
      <label>Description <?= $plan['enhanced'] ? '' : '(300 characters on the Free plan — upgrade for more)' ?></label>
      <textarea name="description" rows="5" maxlength="<?= $plan['enhanced'] ? 5000 : 300 ?>"><?= e($v('description')) ?></textarea>

      <h3 style="margin-top:24px">Location</h3>
      <div class="form-grid">
        <div>
          <label>Country *</label>
          <select name="country" required>
            <?php foreach ($countries as $co): ?>
              <option value="<?= e($co['code']) ?>" <?= $selCountry === $co['code'] ? 'selected' : '' ?>><?= e($co['flag']) ?> <?= e($co['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>State (US only)</label>
          <select name="region">
            <option value="">—</option>
            <?php foreach ($usStates as $s): ?>
              <option value="<?= e($s['slug']) ?>" <?= $selRegion === $s['slug'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-grid">
        <div>
          <label>City *</label>
          <input type="text" name="city" value="<?= e($selCity) ?>" required maxlength="140" placeholder="e.g. Phoenix">
        </div>
        <div>
          <label>Street address</label>
          <input type="text" name="address" value="<?= e($v('address')) ?>" maxlength="255">
        </div>
      </div>

      <h3 style="margin-top:24px">Contact</h3>
      <div class="form-grid">
        <div><label>Phone</label><input type="text" name="phone" value="<?= e($v('phone')) ?>" maxlength="40"></div>
        <div><label>Public email</label><input type="email" name="email" value="<?= e($v('email')) ?>"></div>
        <div><label>Website</label><input type="text" name="website" value="<?= e($v('website')) ?>" placeholder="https://…"></div>
        <div><label>Year founded</label><input type="number" name="founded" value="<?= e($v('founded')) ?>" min="1800" max="<?= date('Y') ?>"></div>
      </div>

      <?php if ($plan['enhanced']): ?>
        <h3 style="margin-top:24px">Storefront extras <span class="badge badge-pro">Pro</span></h3>
        <label>Photo gallery — one image URL per line (max 6)</label>
        <textarea name="gallery_urls" rows="4" placeholder="https://example.com/photo1.jpg"><?= e($_SERVER['REQUEST_METHOD'] === 'POST' ? post('gallery_urls') : $galleryText) ?></textarea>
        <label>Video URL (YouTube, Vimeo…)</label>
        <input type="text" name="video_url" value="<?= e($v('video_url')) ?>">
        <div class="form-grid">
          <?php foreach (['facebook','instagram','tiktok','youtube','pinterest','linkedin','x'] as $net): ?>
            <div>
              <label><?= e(ucfirst($net)) ?></label>
              <input type="text" name="social_<?= $net ?>" value="<?= e($_SERVER['REQUEST_METHOD'] === 'POST' ? post('social_' . $net) : ($social[$net] ?? '')) ?>" placeholder="https://…">
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="card card-pad" style="margin-top:24px;background:var(--accent-soft);border-color:var(--accent)">
          <strong>Want photos, video and social links on your listing?</strong>
          <p class="mute" style="margin:6px 0 10px">Pro members get a full storefront plus analytics.</p>
          <a class="btn btn-primary btn-sm" href="/pricing">See plans</a>
        </div>
      <?php endif; ?>

      <button class="btn btn-primary" style="margin-top:22px"><?= $editing ? 'Save changes' : 'Submit listing for review' ?></button>
      <?php if (!$editing): ?><p class="form-note">New listings are reviewed by our team before going live — usually within 24 hours.</p><?php endif; ?>
    </form>
  </div>
</div>
