<?php
$editing = $biz !== null;
// $prefill seeds a NEW listing (e.g. the domain given at signup) without making
// the form think it is editing — $biz stays null so the heading, button, AI-fill
// block and image sections all keep their new-listing behaviour.
$prefill = $prefill ?? [];
$v = fn(string $field, $default = '') => post($field) !== '' && $_SERVER['REQUEST_METHOD'] === 'POST'
    ? post($field)
    : ($editing ? (string)($biz[$field] ?? $default) : (string)($prefill[$field] ?? $default));
$social = $editing ? (json_decode((string)$biz['social'], true) ?: []) : [];
$selCountry = $_SERVER['REQUEST_METHOD'] === 'POST' ? strtoupper(post('country')) : (!empty($cityRow) ? $cityRow['country_code'] : 'US');
$selRegion  = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('region') : (!empty($cityRow['region_slug']) ? $cityRow['region_slug'] : '');
$selCity    = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('city') : (!empty($cityRow) ? $cityRow['name'] : '');
?>
<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <h1><?= $editing ? 'Edit listing' : 'New listing' ?></h1>
    <?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>

    <?php
    // The website is fixed to the domain the account was registered with, so
    // there is nothing to type and nothing to point somewhere else.
    $aiUrl  = (string)($prefill['website'] ?? '');
    $aiLock = $aiUrl !== '';
    // Collapse the long form until AI has filled it — but never when the member
    // is already correcting a rejected submission, and never without JS.
    $aiCollapse = !$errors && $_SERVER['REQUEST_METHOD'] !== 'POST';
    ?>
    <?php if (!$editing && ai_configured()): ?>
      <div class="card card-pad aifill-card" id="ai-fill-card" data-collapse="<?= $aiCollapse ? '1' : '0' ?>">
        <h3>Create your profile with AI</h3>
        <p class="mute" style="margin:4px 0 14px">We'll read <?= $aiLock ? '<strong>' . e($aiUrl) . '</strong>' : 'your website' ?> and write your listing for you. You review everything before it's submitted.</p>
        <div class="aifill-row">
          <input type="text" id="ai-url" value="<?= e($aiUrl) ?>"
                 placeholder="https://yourbusiness.com"
                 <?= $aiLock ? 'readonly aria-readonly="true" title="This is the website your account was registered with"' : '' ?>
                 data-csrf="<?= e(csrf_token()) ?>">
          <button type="button" class="btn btn-primary btn-xl" id="ai-fill-btn">Create Profile with AI</button>
        </div>
        <p class="form-note" id="ai-status" style="min-height:1.2em"></p>
        <?php if ($aiCollapse): ?>
          <p class="form-note" id="ai-manual-wrap" hidden>
            Prefer to type it yourself? <a href="#" id="ai-manual">Fill in the form manually</a>.
          </p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="card card-pad" id="listing-form"><?= csrf_field() ?>
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
      <input type="text" name="tagline" value="<?= e($v('tagline')) ?>" maxlength="255" placeholder="One line that sells your business">
      <label>Description <?= $plan['enhanced'] ? '' : '(300 characters on the Free plan — upgrade for more)' ?></label>
      <textarea name="description" rows="5" maxlength="<?= $plan['enhanced'] ? 5000 : 300 ?>"><?= e($v('description')) ?></textarea>

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
          <input type="text" name="city" value="<?= e($selCity) ?>" required maxlength="140" placeholder="e.g. Phoenix">
        </div>
        <div>
          <label>Street address</label>
          <input type="text" name="address" value="<?= e($v('address')) ?>" maxlength="255">
        </div>
      </div>

      <h3 style="margin-top:24px">Contact</h3>
      <div class="form-grid">
        <div><label>Website</label><input type="text" name="website" value="<?= e($v('website')) ?>" placeholder="https://…"></div>
        <div><label>Year founded</label><input type="number" name="founded" value="<?= e($v('founded')) ?>" min="1800" max="<?= date('Y') ?>"></div>
      </div>

      <?php // Phone, public email and images are paid features. The fields are
            // not rendered at all on the Free plan — the save path ignores them
            // too, so this is the honest view of it rather than a disabled tease.
            if ($plan['enhanced']): ?>
        <div class="form-grid">
          <div><label>Phone</label><input type="text" name="phone" value="<?= e($v('phone')) ?>" maxlength="40"></div>
          <div><label>Public email</label><input type="email" name="email" value="<?= e($v('email')) ?>"></div>
        </div>

        <h3 style="margin-top:24px">Logo <span class="badge badge-pro">Pro</span></h3>
        <?php if ($editing && $biz['logo_url']): ?>
          <p><img src="<?= e($biz['logo_url']) ?>" alt="Current logo" style="width:72px;height:72px;object-fit:cover;border-radius:12px;border:1px solid var(--border)">
          <label style="display:inline;font-weight:500;margin-left:10px"><input type="checkbox" name="remove_logo" value="1" style="width:auto"> Remove current logo</label></p>
        <?php endif; ?>
        <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif">
        <p class="form-note">JPG, PNG, WebP or GIF up to 5 MB. Shown on your listing card and storefront.</p>

        <h3 style="margin-top:24px">Storefront extras <span class="badge badge-pro">Pro</span></h3>
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
        <p class="form-note">Add up to <?= max(0, 6 - count($gallery ?? [])) ?> more photos, 5 MB each. They're resized automatically.</p>
        <label>Video URL (YouTube, Vimeo…)</label>
        <input type="text" name="video_url" value="<?= e($v('video_url')) ?>">
        <div class="form-grid">
          <?php // social_nets() is the shared list — a form that renders fewer
                // fields than the save path writes silently drops the rest.
                foreach (social_nets() as $net => $netLabel): ?>
            <div>
              <label><?= e($netLabel) ?></label>
              <input type="text" name="social_<?= e($net) ?>" value="<?= e($_SERVER['REQUEST_METHOD'] === 'POST' ? post('social_' . $net) : ($social[$net] ?? '')) ?>" placeholder="https://…">
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="card card-pad" style="margin-top:24px;background:var(--accent-soft);border-color:var(--accent)">
          <strong>Want a phone number, contact email and photos on your listing?</strong>
          <p class="mute" style="margin:6px 0 10px">Free listings show your name, description, category, location and
            website link. Pro adds your phone and public email, your logo, a photo gallery, video, social links
            and analytics.</p>
          <a class="btn btn-primary btn-sm" href="/pricing">See plans</a>
        </div>
      <?php endif; ?>

      <button class="btn btn-primary" style="margin-top:22px"><?= $editing ? 'Save changes' : 'Submit listing for review' ?></button>
      <?php if (!$editing): ?><p class="form-note">New listings are reviewed by our team before going live — usually within 24 hours.</p><?php endif; ?>
    </form>
  </div>
</div>
