<?php
// Shared header for the setup wizard steps. Expects $biz and $step.
$wzSteps = wizard_steps();
$wzAt    = array_search($step, array_column($wzSteps, 0), true);
?>
<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <p class="wz-crumb"><a href="/account/listings">← My listings</a> · <?= e($biz['name']) ?></p>

    <ol class="wz-steps">
      <?php foreach ($wzSteps as $i => [$slug, $label]): ?>
        <li class="<?= $i < $wzAt ? 'done' : ($i === $wzAt ? 'now' : '') ?>">
          <span class="wz-num"><?= $i < $wzAt ? '✓' : $i + 1 ?></span><?= e($label) ?>
        </li>
      <?php endforeach; ?>
    </ol>
