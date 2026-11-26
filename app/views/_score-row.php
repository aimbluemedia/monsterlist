<?php
// ---------------------------------------------------------------------------
// One row of the Standout profiles leaderboard. Expects $b (a business row),
// an optional $srRank (1-based position) and an optional $srFlag (extra tag).
//
// $srRank is left unset by sections that are not ranked — "Just joined" is
// ordered by date, and a number down its left edge would assert a ranking
// that does not exist. The board gets .sr-board-plain in that case so the
// grid drops the column rather than leaving a gap.
//
// A row rather than a card, on purpose. This section is ranked by MonsterScore
// and three card grids already sit around it on the homepage; a fourth said
// nothing about what makes this one different. A numbered list with the score
// drawn as a bar shows the ranking instead of just asserting it.
// ---------------------------------------------------------------------------
$srRank  = $srRank ?? null;
$srFlag  = $srFlag ?? null;
$srScore = monster_score($b);
$srBand  = monster_score_band($srScore);
$srWhat  = trim((string)($b['category_label'] ?? 'Local business'));
if (!empty($b['city_name'])) $srWhat .= ' · ' . $b['city_name'];
?>
<a class="sr sr-<?= e($srBand) ?>" href="<?= e(business_path($b)) ?>">
  <?php if ($srRank !== null): ?><span class="sr-rank"><?= (int)$srRank ?></span><?php endif; ?>

  <span class="sr-avatar">
    <?php if ($srLogo = listing_logo($b)): ?><img src="<?= e($srLogo) ?>" alt="">
    <?php else: ?><?= e(mb_substr($b['name'], 0, 1)) ?><?php endif; ?>
  </span>

  <span class="sr-id">
    <b><?= e($b['name']) ?></b>
    <small><?= e($srWhat) ?></small>
  </span>

  <span class="sr-flags">
    <?php if ($srFlag): ?><em class="sr-flag sr-flag-new"><?= e($srFlag) ?></em><?php endif; ?>
    <?php if (is_featured_listing($b)): ?><em class="sr-flag sr-flag-feat">Featured</em><?php endif; ?>
    <?php if (!empty($b['verified'])): ?><em class="sr-flag sr-flag-ver">Verified</em><?php endif; ?>
  </span>

  <?php // The bar is the point: it is the score, not a decoration beside it. ?>
  <span class="sr-meter" title="MonsterScore <?= $srScore ?> out of 100">
    <span class="sr-bar"><i style="width:<?= (int)$srScore ?>%"></i></span>
    <b><?= $srScore ?></b>
  </span>
</a>
