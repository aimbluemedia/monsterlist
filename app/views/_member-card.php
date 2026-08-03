<?php
// ---------------------------------------------------------------------------
// Member card for the homepage sky sections. Expects $b (a business row from
// featured_businesses() / newest_businesses()) and optional $mcBadge.
//
// The MonsterScore is computed from the row by monster_score(), so it is the
// listing's real setup score rather than decoration.
// ---------------------------------------------------------------------------
$mcScore = monster_score($b);
$mcBand  = monster_score_band($mcScore);
$mcText  = trim((string)($b['description'] ?? '')) ?: trim((string)($b['tagline'] ?? ''));
$mcText  = words($mcText, 20);
?>
<a class="mc mc-<?= e($mcBand) ?>" href="<?= e(business_path($b)) ?>">
  <div class="mc-head">
    <span class="mc-avatar"><?php if (!empty($b['logo_url'])): ?><img src="<?= e($b['logo_url']) ?>" alt=""><?php else: ?><?= e(mb_substr($b['name'], 0, 1)) ?><?php endif; ?></span>
    <span class="mc-id">
      <b><?= e($b['name']) ?></b>
      <small><?= e($b['category_label'] ?? 'Local business') ?><?= !empty($b['city_name']) ? ' · ' . e($b['city_name']) : '' ?></small>
    </span>
    <?php if (!empty($mcBadge)): ?><span class="mc-tag"><?= e($mcBadge) ?></span><?php endif; ?>
  </div>

  <p class="mc-desc"><?= $mcText !== '' ? e($mcText) : 'This member has not added a description yet.' ?></p>

  <div class="mc-foot">
    <span class="mc-rating">
      <?php if ((float)($b['rating'] ?? 0) > 0): ?>
        <span class="stars">★</span> <?= fmt_rating($b['rating']) ?> · <?= (int)$b['review_count'] ?> review<?= (int)$b['review_count'] === 1 ? '' : 's' ?>
      <?php else: ?>
        <span class="mute">No reviews yet</span>
      <?php endif; ?>
      <?php if (!empty($b['verified'])): ?><span class="badge badge-verified">Verified</span><?php endif; ?>
    </span>

    <span class="mc-score" title="MonsterScore <?= $mcScore ?> out of 100">
      <b><?= $mcScore ?></b>
      <small>MonsterScore</small>
    </span>
  </div>
</a>
