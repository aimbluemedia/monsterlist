<?php
require __DIR__ . '/_top.php';
$posted = $_SERVER['REQUEST_METHOD'] === 'POST';
$v = fn(string $field, $default = '') => $posted ? post($field) : (string)($biz[$field] ?? $default);
$social     = json_decode((string)$biz['social'], true) ?: [];
$selCountry = $posted ? strtoupper(post('country')) : (!empty($cityRow) ? $cityRow['country_code'] : 'US');
$selRegion  = $posted ? post('region') : (!empty($cityRow['region_slug']) ? $cityRow['region_slug'] : '');
$selCity    = $posted ? post('city') : (!empty($cityRow) ? $cityRow['name'] : '');
$selOwner   = $posted ? post('owner_email') : (string)($owner['email'] ?? '');
// Carry the search term back so "Back to listings" returns to the filtered view.
$listUrl    = e(listings_url($back, $_GET['q'] ?? ''));
?>
<div class="section-head">
  <h1>Edit listing</h1>
  <a class="mute" href="<?= $listUrl ?>">← Back to listings</a>
</div>
<?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data"><?= csrf_field() ?>

  <div class="card card-pad" style="margin-bottom:14px">
    <h3>Moderation</h3>
    <p class="mute" style="font-size:.85rem;margin-top:0">Staff edits save straight through — the listing is not sent back for review.</p>
    <div class="form-grid">
      <div>
        <label>Status</label>
        <select name="status">
          <?php foreach (['pending' => 'Pending review', 'live' => 'Live', 'rejected' => 'Rejected'] as $k => $lbl): ?>
            <option value="<?= $k ?>" <?= ($posted ? post('status') : $biz['status']) === $k ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Tier</label>
        <select name="tier">
          <?php foreach (['free' => 'Free', 'pro' => 'Pro', 'featured' => 'Featured'] as $k => $lbl): ?>
            <option value="<?= $k ?>" <?= ($posted ? post('tier') : $biz['tier']) === $k ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <label>Owner email</label>
    <input type="text" name="owner_email" value="<?= e($selOwner) ?>" placeholder="Leave empty to make this listing unclaimed">
    <p class="form-note">Must match an existing member account. Empty means unclaimed, so anyone can claim it.</p>
    <label style="font-weight:500"><input type="checkbox" name="verified" value="1" style="width:auto" <?= ($posted ? !empty($_POST['verified']) : $biz['verified']) ? 'checked' : '' ?>> Verified business</label>
  </div>

  <div class="card card-pad">
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
            <option value="<?= e($c['id']) ?>" <?= $v('category_id') === $c['id'] ? 'selected' : '' ?>><?= e($c['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <label>Tagline</label>
    <input type="text" name="tagline" value="<?= e($v('tagline')) ?>" maxlength="255">
    <label>Description</label>
    <textarea name="description" rows="5" maxlength="5000"><?= e($v('description')) ?></textarea>

    <h3 style="margin-top:24px">Location</h3>
    <div class="form-grid">
      <div>
        <label>Country *</label>
        <select name="country" required>
          <?php foreach ($countries as $co): ?>
            <option value="<?= e($co['code']) ?>" <?= $selCountry === $co['code'] ? 'selected' : '' ?>><?= e($co['name']) ?></option>
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
        <input type="text" name="city" value="<?= e($selCity) ?>" required maxlength="140">
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

    <h3 style="margin-top:24px">Logo</h3>
    <?php if ($biz['logo_url']): ?>
      <p><img src="<?= e($biz['logo_url']) ?>" alt="Current logo" style="width:72px;height:72px;object-fit:cover;border-radius:12px;border:1px solid var(--border)">
      <label style="display:inline;font-weight:500;margin-left:10px"><input type="checkbox" name="remove_logo" value="1" style="width:auto"> Remove current logo</label></p>
    <?php endif; ?>
    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif">
    <p class="form-note">JPG, PNG, WebP or GIF up to 5 MB.</p>

    <h3 style="margin-top:24px">Storefront extras</h3>
    <label>Photo gallery (up to 6 photos)</label>
    <?php if (!empty($gallery)): ?>
      <div class="gallery-grid" style="margin-bottom:10px">
        <?php foreach ($gallery as $g): ?>
          <div>
            <img src="<?= e($g['url']) ?>" alt="">
            <label style="font-weight:500;margin-top:4px"><input type="checkbox" name="remove_gallery[]" value="<?= (int)$g['id'] ?>" style="width:auto"> Remove</label>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <input type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
    <p class="form-note">Add up to <?= max(0, 6 - count($gallery ?? [])) ?> more photos, 5 MB each.</p>
    <label>Video URL</label>
    <input type="text" name="video_url" value="<?= e($v('video_url')) ?>">
    <div class="form-grid">
      <?php foreach (['facebook','instagram','tiktok','youtube','pinterest','linkedin','x'] as $net): ?>
        <div>
          <label><?= e(ucfirst($net)) ?></label>
          <input type="text" name="social_<?= $net ?>" value="<?= e($posted ? post('social_' . $net) : ($social[$net] ?? '')) ?>" placeholder="https://…">
        </div>
      <?php endforeach; ?>
    </div>

    <button class="btn btn-primary" style="margin-top:22px">Save changes</button>
    <a class="btn btn-ghost" style="margin-top:22px" href="<?= $listUrl ?>">Cancel</a>
  </div>
</form>
<?php require __DIR__ . '/_bottom.php'; ?>
