<?php
// Shared staff pager. Expects, from the including view:
//   $pgPage   current page, 1-based
//   $pgTotal  how many rows there are in total
//   $pgPer    rows per page
//   $pgUrl    fn(int $page): string — the href for a page, filters and all
//
// Drawn even on a single page, because "1–17 of 17" is worth knowing and its
// absence reads as a page that has not finished loading.
$pgPages = max(1, (int)ceil($pgTotal / max(1, $pgPer)));
$pgFrom  = $pgTotal ? (($pgPage - 1) * $pgPer) + 1 : 0;
$pgTo    = min($pgTotal, $pgPage * $pgPer);
?>
<nav class="pgr">
  <div class="pgr-count">
    <?php if (!$pgTotal): ?>
      Nothing to show
    <?php else: ?>
      <?= number_format($pgFrom) ?>–<?= number_format($pgTo) ?> of <?= number_format($pgTotal) ?>
    <?php endif; ?>
  </div>

  <?php if ($pgPages > 1): ?>
    <div class="pgr-links">
      <?php // Previous and Next are always drawn, disabled at the ends, so the
            // control does not change shape as you walk through it. ?>
      <?php if ($pgPage > 1): ?>
        <a class="pgr-step" href="<?= e($pgUrl($pgPage - 1)) ?>" rel="prev">← Prev</a>
      <?php else: ?>
        <span class="pgr-step pgr-off">← Prev</span>
      <?php endif; ?>

      <?php foreach (pager_pages($pgPage, $pgPages) as $n): ?>
        <?php if ($n === null): ?>
          <span class="pgr-gap">…</span>
        <?php elseif ($n === $pgPage): ?>
          <span class="pgr-num pgr-here" aria-current="page"><?= (int)$n ?></span>
        <?php else: ?>
          <a class="pgr-num" href="<?= e($pgUrl($n)) ?>"><?= (int)$n ?></a>
        <?php endif; ?>
      <?php endforeach; ?>

      <?php if ($pgPage < $pgPages): ?>
        <a class="pgr-step" href="<?= e($pgUrl($pgPage + 1)) ?>" rel="next">Next →</a>
      <?php else: ?>
        <span class="pgr-step pgr-off">Next →</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</nav>
