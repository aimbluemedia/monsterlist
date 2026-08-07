<?php
require __DIR__ . '/_wizard-head.php';
$sites = wizard_reviews();
?>
    <h1>Where are you reviewed?</h1>

    <form method="post" class="card card-pad wz-card">
      <?= csrf_field() ?>
      <p class="mute" style="margin-top:0">
        Link the review profiles you already have. We show them on your listing so
        visitors can check you out — we don't copy or re-post the reviews themselves.
      </p>

      <div class="wz-links">
        <?php foreach ($sites as $key => [$label, $placeholder]): ?>
          <div>
            <label for="rev-<?= e($key) ?>"><?= e($label) ?></label>
            <input type="text" id="rev-<?= e($key) ?>" name="links[<?= e($key) ?>]"
                   value="<?= e($existing[$key] ?? '') ?>" placeholder="<?= e($placeholder) ?>">
          </div>
        <?php endforeach; ?>
      </div>

      <div class="wz-actions">
        <button class="btn btn-primary btn-xl" name="action" value="save">Finish</button>
        <button class="btn btn-ghost" name="action" value="skip" formnovalidate>Skip and finish</button>
      </div>
    </form>
<?php require __DIR__ . '/_wizard-foot.php'; ?>
