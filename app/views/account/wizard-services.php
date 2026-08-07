<?php
require __DIR__ . '/_wizard-head.php';
// Suggestions the AI pulled off the website; anything already saved is
// pre-selected so revisiting the step shows the current state rather than a
// blank slate. Saved services that were not suggested are shown as bubbles too.
$chosen  = array_map('mb_strtolower', $existing);
$bubbles = $suggestions;
foreach ($existing as $svc) {
    if (!in_array(mb_strtolower($svc), array_map('mb_strtolower', $bubbles), true)) $bubbles[] = $svc;
}
$max = WIZ_MAX_SERVICES;
?>
    <h1>What do you offer?</h1>

    <form method="post" class="card card-pad wz-card" id="wz-services">
      <?= csrf_field() ?>

      <?php if ($bubbles): ?>
        <p class="mute" style="margin-top:0">
          We read your website and found these. Pick up to <?= $max ?> —
          they show as tags on your listing.
        </p>
        <div class="wz-bubbles" data-max="<?= $max ?>">
          <?php foreach ($bubbles as $i => $svc): ?>
            <label class="wz-bubble">
              <input type="checkbox" name="services[]" value="<?= e($svc) ?>"
                     <?= in_array(mb_strtolower($svc), $chosen, true) ? 'checked' : '' ?>>
              <span><?= e($svc) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="form-note" id="wz-count" aria-live="polite"></p>

        <details class="wz-more">
          <summary>Add something that isn't listed</summary>
          <div class="wz-manual">
            <?php for ($i = 0; $i < 3; $i++): ?>
              <input type="text" name="custom[]" maxlength="80" placeholder="e.g. Emergency callouts">
            <?php endfor; ?>
          </div>
        </details>

      <?php else: ?>
        <p class="mute" style="margin-top:0">
          We couldn't work out your services from your website, so type them in —
          up to <?= $max ?>. They show as tags on your listing. Leave any blank.
        </p>
        <div class="wz-manual">
          <?php for ($i = 0; $i < $max; $i++): ?>
            <input type="text" name="custom[]" maxlength="80"
                   value="<?= e($existing[$i] ?? '') ?>"
                   placeholder="Service <?= $i + 1 ?>">
          <?php endfor; ?>
        </div>
      <?php endif; ?>

      <div class="wz-actions">
        <button class="btn btn-primary btn-xl" name="action" value="save">Save and continue</button>
        <button class="btn btn-ghost" name="action" value="skip" formnovalidate>Skip for now</button>
      </div>
    </form>
<?php require __DIR__ . '/_wizard-foot.php'; ?>
