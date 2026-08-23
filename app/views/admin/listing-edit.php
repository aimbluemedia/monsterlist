<?php
require __DIR__ . '/_top.php';
$posted = $_SERVER['REQUEST_METHOD'] === 'POST';
$v = fn(string $field, $default = '') => $posted ? post($field) : (string)($biz[$field] ?? $default);
$social     = json_decode((string)$biz['social'], true) ?: [];
$revLinks   = wizard_links($biz['review_links'] ?? null);
$selCountry = $posted ? strtoupper(post('country')) : (!empty($cityRow) ? $cityRow['country_code'] : 'US');
$selRegion  = $posted ? post('region') : (!empty($cityRow['region_slug']) ? $cityRow['region_slug'] : '');
$selCity    = $posted ? post('city') : (!empty($cityRow) ? $cityRow['name'] : '');
$selOwner   = $posted ? post('owner_email') : (string)($owner['email'] ?? '');
// The tier this form is currently showing, which on a post is the one just
// chosen rather than the one on file. Paid-tier fields key off this.
$vTier      = $posted ? (string)post('tier') : (string)$biz['tier'];
// Carry the search term back so "Back to listings" returns to the filtered view.
$listUrl    = e(listings_url($back, $_GET['q'] ?? ''));
?>
<div class="section-head">
  <h1>Edit listing</h1>
  <a class="mute" href="<?= $listUrl ?>">← Back to listings</a>
</div>
<?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data"><?= csrf_field() ?>

  <div class="card card-pad ed-card">
    <h3>Moderation</h3>
    <p class="mute ed-note">Staff edits save straight through — the listing is not sent back for review.</p>
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

  <div class="card card-pad ed-card">
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
      <?php // The Schema.org type, grouped by our own category so the list is
            // navigable. Optional: a listing with no type is still marked up,
            // just as a general LocalBusiness. Every option is a real type in
            // the LocalBusiness branch — see schema_types(). ?>
      <label>Business type <span class="mute" style="font-weight:400">— what Google reads you as</span></label>
      <select name="business_type">
        <option value="">Not specified — treated as a general local business</option>
        <?php foreach (schema_types() as $catId => $types): ?>
          <optgroup label="<?= e(category_by_id((string)$catId)['label'] ?? ucfirst((string)$catId)) ?>">
            <?php foreach ($types as $type => $label): ?>
              <option value="<?= e($type) ?>" <?= $v('business_type') === $type ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>
      <p class="form-note">Pick the closest match. "Plumber" or "Dentist" tells a search engine
        far more than "business", and it is what puts you in the running for the richer result
        listings that name a trade.</p>
    <label>Tagline</label>
    <input type="text" name="tagline" value="<?= e($v('tagline')) ?>" maxlength="255">
    <label>Description</label>
    <textarea name="description" rows="5" maxlength="5000"><?= e($v('description')) ?></textarea>
    <?php // Profile belongs to the paid tiers, and so does the cost of writing
          // one. Read the tier off the form rather than the database, so
          // switching the dropdown above and saving is all it takes to open the
          // section — and switching it back closes it again.
          //
          // Leaving the field out is safe for a listing that already has a
          // profile: listing_form_data() only touches the column when the field
          // was actually posted, so text written while a listing was Pro
          // survives a spell on Free and comes back when it goes up again. ?>
    <?php if (tier_enhanced($vTier)): ?>
      <label>Profile <span class="badge badge-pro">Pro</span> <span class="badge badge-featured">Premium</span></label>
      <p class="form-note" style="margin:0 0 6px">Long-form section, up to <?= number_format(PROFILE_MAX_WORDS) ?> words,
        shown as its own block on the storefront. Blank lines make paragraphs.</p>
      <?php if ($aiNotice): ?><p class="aiprof-note"><?= e($aiNotice) ?></p><?php endif; ?>
      <textarea name="profile" rows="10"><?= e($v('profile')) ?></textarea>
      <?php if (ai_configured()): ?>
        <?php // Posts this same form, so anything typed above survives the round
              // trip. The answer lands in the box; saving is still a separate act. ?>
        <div class="aiprof-row">
          <button class="btn btn-sm btn-primary" name="ai_profile" value="1"
                  data-confirm="Research <?= e($v('name') ?: 'this business') ?> and write a new Profile? This replaces what is in the box, and takes a minute or two.">
            <?= trim($v('profile')) === '' ? 'Write it with AI' : 'Rewrite it with AI' ?>
          </button>
          <span class="form-note">Reads the website, searches for what else is public, and writes
            1,000–1,500 words. It lands in the box above for you to check — nothing saves until you
            press Save changes.</span>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <label>Profile <span class="badge badge-pro">Pro</span> <span class="badge badge-featured">Premium</span></label>
      <p class="form-note" style="margin:0 0 6px">Only on the paid tiers. Set <strong>Tier</strong> above to
        Pro or Featured and save, and the box — and the AI writer — appear here.<?php
        if (trim((string)$biz['profile']) !== ''): ?> This listing already has a profile written;
        it is kept, and comes back when the tier does.<?php endif; ?></p>
    <?php endif; ?>
    <div class="form-grid">
      <div><label>Website</label><input type="text" name="website" value="<?= e($v('website')) ?>" placeholder="https://…"></div>
      <div><label>Year founded</label><input type="number" name="founded" value="<?= e($v('founded')) ?>" min="1800" max="<?= date('Y') ?>"></div>
    </div>
  </div>

  <div class="card card-pad ed-card">
    <h3>Location</h3>
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
    <div class="form-grid">
      <div>
        <label>Postcode / ZIP</label>
        <input type="text" name="postcode" value="<?= e($v('postcode')) ?>" maxlength="20">
      </div>
      <div></div>
    </div>
  </div>

  <?php // Staff get every field whatever the owner pays for, and these two have
        // to be here for a second reason: the save path treats staff as an
        // enhanced plan, so a form without them would read them as cleared and
        // wipe what the owner entered. ?>
  <div class="card card-pad ed-card">
    <h3>Opening hours</h3>
    <div class="hrs">
      <?php $hoursVal = hours_parse($biz['hours'] ?? null); ?>
      <?php foreach (hours_days() as $i => $day): ?>
        <?php
        $key = strtolower($day);
        $row = $hoursVal[$i] ?? ['open' => false, 'from' => '09:00', 'to' => '17:00'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $row = ['open' => !empty($_POST['hours_open'][$key]),
                    'from' => (string)($_POST['hours_from'][$key] ?? ''),
                    'to'   => (string)($_POST['hours_to'][$key] ?? '')];
        }
        ?>
        <div class="hrs-row">
          <label class="hrs-day">
            <input type="checkbox" name="hours_open[<?= e($key) ?>]" value="1" <?= !empty($row['open']) ? 'checked' : '' ?>>
            <span><?= e($day) ?></span>
          </label>
          <input type="time" name="hours_from[<?= e($key) ?>]" value="<?= e((string)($row['from'] ?: '09:00')) ?>" aria-label="<?= e($day) ?> opens">
          <span class="hrs-to">to</span>
          <input type="time" name="hours_to[<?= e($key) ?>]" value="<?= e((string)($row['to'] ?: '17:00')) ?>" aria-label="<?= e($day) ?> closes">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card card-pad ed-card">
    <h3>Social media</h3>
    <p class="mute ed-note">Ten channels, two columns of five. Leave any blank.</p>
    <div class="form-grid">
      <?php foreach (social_nets() as $net => $label): ?>
        <div>
          <label><?= e($label) ?></label>
          <input type="text" name="social_<?= e($net) ?>"
                 value="<?= e($posted ? post('social_' . $net) : ($social[$net] ?? '')) ?>" placeholder="https://…">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card card-pad ed-card">
    <h3>Reviews</h3>
    <p class="mute ed-note">Links to this business's profiles on review sites. Shown on the storefront as
      “Our Reviews” — we link out, we never copy the reviews.</p>
    <div class="form-grid">
      <?php foreach (wizard_reviews() as $site => [$label, $placeholder]): ?>
        <div>
          <label><?= e($label) ?></label>
          <input type="text" name="review_<?= e($site) ?>"
                 value="<?= e($posted ? post('review_' . $site) : ($revLinks[$site] ?? '')) ?>"
                 placeholder="<?= e($placeholder) ?>">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card card-pad ed-card ed-premium">
    <h3>Premium <span class="badge badge-pro">Pro</span></h3>
    <p class="mute ed-note">Only shown on the storefront for listings on a paid tier.
      Set the tier at the top of this page.</p>

    <h4 class="ed-sub">Contact</h4>
    <div class="form-grid">
      <div><label>Phone</label><input type="text" name="phone" value="<?= e($v('phone')) ?>" maxlength="40"></div>
      <div><label>Public email</label><input type="email" name="email" value="<?= e($v('email')) ?>"></div>
    </div>

    <h4 class="ed-sub">Logo</h4>
    <?php if ($biz['logo_url']): ?>
      <p><img src="<?= e($biz['logo_url']) ?>" alt="Current logo" style="width:72px;height:72px;object-fit:cover;border-radius:12px;border:1px solid var(--border)">
      <label style="display:inline;font-weight:500;margin-left:10px"><input type="checkbox" name="remove_logo" value="1" style="width:auto"> Remove current logo</label></p>
    <?php endif; ?>
    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif">
    <p class="form-note">JPG, PNG, WebP or GIF up to 5 MB.</p>

    <h4 class="ed-sub">Storefront extras</h4>
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
  </div>

  <div class="ed-actions">
    <button class="btn btn-primary">Save changes</button>
    <a class="btn btn-ghost" href="<?= $listUrl ?>">Cancel</a>
  </div>
</form>
<?php require __DIR__ . '/_bottom.php'; ?>
