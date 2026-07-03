<?php
$social   = json_decode((string)$b['social'], true) ?: [];
$hours    = json_decode((string)$b['hours'], true) ?: [];
$enhanced = in_array($b['tier'], ['pro', 'featured'], true);
?>
<div class="wrap">
  <nav class="crumbs">
    <?php foreach ($crumbs as $i => $cr): ?>
      <?php if ($i): ?><span>›</span><?php endif; ?>
      <?php if ($i < count($crumbs) - 1): ?><a href="<?= e($cr['path']) ?>"><?= e($cr['name']) ?></a>
      <?php else: ?><?= e($cr['name']) ?><?php endif; ?>
    <?php endforeach; ?>
  </nav>

  <section class="store-hero">
    <div class="store-title">
      <span class="avatar" style="width:64px;height:64px;font-size:1.6rem;border-radius:14px;background:var(--accent-soft);color:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:800"><?= e(mb_substr($b['name'], 0, 1)) ?></span>
      <div>
        <h1 style="margin:0"><?= e($b['name']) ?>
          <?php if ($b['tier'] === 'featured'): ?><span class="badge badge-featured">Featured</span><?php endif; ?>
          <?php if ($b['tier'] === 'pro'): ?><span class="badge badge-pro">Pro</span><?php endif; ?>
          <?php if ($b['verified']): ?><span class="badge badge-verified">Verified</span><?php endif; ?>
        </h1>
        <div class="mute">
          <?= e($b['category_icon'] ?? '') ?> <?= e($b['category_label'] ?? 'Local business') ?>
          · <?= e($city['name']) ?><?= $region ? ', ' . e($region['name']) : '' ?>, <?= e($country['name']) ?>
          <?php if ((float)$b['rating'] > 0): ?>
            · <span class="stars">★</span> <?= fmt_rating($b['rating']) ?> (<?= (int)$b['review_count'] ?>)
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php if ($b['tagline']): ?><p class="mute" style="margin-top:10px"><?= e($b['tagline']) ?></p><?php endif; ?>
  </section>

  <div class="two-col section" style="padding-top:0">
    <div>
      <?php if ($b['description']): ?>
        <div class="card card-pad" style="margin-bottom:14px">
          <h3>About</h3>
          <p style="white-space:pre-line"><?= e($b['description']) ?></p>
          <?php if ($b['founded']): ?><p class="mute">In business since <?= (int)$b['founded'] ?>.</p><?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($enhanced && $gallery): ?>
        <div class="card card-pad" style="margin-bottom:14px">
          <h3>Photos</h3>
          <div class="gallery-grid">
            <?php foreach ($gallery as $g): ?>
              <img src="<?= e($g['url']) ?>" alt="<?= e($g['label'] ?: $b['name']) ?>" loading="lazy">
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($enhanced && $b['video_url']): ?>
        <div class="card card-pad" style="margin-bottom:14px">
          <h3>Video</h3>
          <p><a href="<?= e($b['video_url']) ?>" target="_blank" rel="noopener nofollow" style="color:var(--accent);font-weight:700">Watch video →</a></p>
        </div>
      <?php endif; ?>

      <?php if ($services): ?>
        <div class="card card-pad" style="margin-bottom:14px">
          <h3>Services</h3>
          <?php foreach ($services as $s): ?>
            <div class="info-row"><span><strong><?= e($s['name']) ?></strong><?php if ($s['description']): ?> <span class="mute">— <?= e($s['description']) ?></span><?php endif; ?></span><span class="mute"><?= e($s['price']) ?></span></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($products): ?>
        <div class="card card-pad" style="margin-bottom:14px">
          <h3>Products</h3>
          <?php foreach ($products as $p): ?>
            <div class="info-row"><span><strong><?= e($p['name']) ?></strong><?php if ($p['note']): ?> <span class="mute">— <?= e($p['note']) ?></span><?php endif; ?></span><span class="mute"><?= e($p['price']) ?></span></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="card card-pad" id="reviews">
        <h3>Reviews (<?= (int)$b['review_count'] ?>)</h3>
        <?php if (!$reviews): ?><p class="mute">No reviews yet — be the first.</p><?php endif; ?>
        <?php foreach ($reviews as $r): ?>
          <div class="review">
            <div class="review-head">
              <strong><?= e($r['author_name']) ?></strong>
              <span class="stars"><?= str_repeat('★', (int)$r['rating']) ?><span style="color:var(--border)"><?= str_repeat('★', 5 - (int)$r['rating']) ?></span></span>
            </div>
            <?php if ($r['body']): ?><p style="margin:6px 0 0"><?= e($r['body']) ?></p><?php endif; ?>
            <div class="faint" style="font-size:.8rem;margin-top:4px"><?= e(date('M j, Y', strtotime($r['created_at']))) ?></div>
          </div>
        <?php endforeach; ?>

        <?php if (is_logged_in()): ?>
          <form method="post" style="margin-top:16px">
            <?= csrf_field() ?>
            <h3>Write a review</h3>
            <label>Rating</label>
            <select name="review_rating">
              <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?>
            </select>
            <label>Your review</label>
            <textarea name="review_body" rows="4" maxlength="2000" placeholder="Share your experience…"></textarea>
            <button class="btn btn-primary" style="margin-top:12px">Post review</button>
          </form>
        <?php else: ?>
          <p class="mute" style="margin-top:12px"><a href="/login" style="color:var(--accent);font-weight:700">Log in</a> to write a review.</p>
        <?php endif; ?>
      </div>
    </div>

    <aside>
      <div class="card card-pad" style="margin-bottom:14px">
        <h3>Contact</h3>
        <?php if ($b['phone']): ?><div class="info-row"><span class="mute">Phone</span><a href="tel:<?= e($b['phone']) ?>" style="font-weight:700"><?= e($b['phone']) ?></a></div><?php endif; ?>
        <?php if ($b['website']): ?><div class="info-row"><span class="mute">Website</span><a href="/out/<?= (int)$b['id'] ?>" rel="nofollow" target="_blank" style="color:var(--accent);font-weight:700">Visit site ↗</a></div><?php endif; ?>
        <?php if ($b['email']): ?><div class="info-row"><span class="mute">Email</span><a href="mailto:<?= e($b['email']) ?>" style="font-weight:700"><?= e($b['email']) ?></a></div><?php endif; ?>
        <?php if ($b['address']): ?><div class="info-row"><span class="mute">Address</span><span style="text-align:right"><?= e($b['address']) ?></span></div><?php endif; ?>
      </div>

      <?php if ($hours): ?>
        <div class="card card-pad" style="margin-bottom:14px">
          <h3>Hours</h3>
          <?php foreach ($hours as $h): ?>
            <div class="info-row"><span class="mute"><?= e($h['d'] ?? '') ?></span><span><?= !empty($h['open']) ? e($h['h'] ?? '') : 'Closed' ?></span></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($enhanced && $social): ?>
        <div class="card card-pad" style="margin-bottom:14px">
          <h3>Social</h3>
          <?php foreach ($social as $net => $url): if (!$url) continue; ?>
            <div class="info-row"><span class="mute"><?= e(ucfirst($net)) ?></span><a href="<?= e($url) ?>" target="_blank" rel="noopener nofollow" style="color:var(--accent);font-weight:700">Open ↗</a></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!$b['owner_id']): ?>
        <div class="card card-pad" style="background:var(--accent-soft);border-color:var(--accent)">
          <h3>Is this your business?</h3>
          <p class="mute">Claim this listing to update details, add photos and reply to reviews.</p>
          <a class="btn btn-primary btn-block" href="/signup">Claim this listing</a>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</div>
