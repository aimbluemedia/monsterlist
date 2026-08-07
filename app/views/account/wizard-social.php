<?php
require __DIR__ . '/_wizard-head.php';
$nets = wizard_socials();
?>
    <h1>Where can people follow you?</h1>

    <form method="post" class="card card-pad wz-card">
      <?= csrf_field() ?>
      <p class="mute" style="margin-top:0">
        Paste the address of each profile you have. Leave the rest blank — you can
        add them any time from “Edit listing”.
      </p>

      <div class="wz-links">
        <?php foreach ($nets as $key => [$label, $placeholder]): ?>
          <div>
            <label for="soc-<?= e($key) ?>"><?= e($label) ?></label>
            <input type="text" id="soc-<?= e($key) ?>" name="links[<?= e($key) ?>]"
                   value="<?= e($existing[$key] ?? '') ?>" placeholder="<?= e($placeholder) ?>">
          </div>
        <?php endforeach; ?>
      </div>

      <div class="wz-actions">
        <button class="btn btn-primary btn-xl" name="action" value="save">Save and continue</button>
        <button class="btn btn-ghost" name="action" value="skip" formnovalidate>Skip for now</button>
      </div>
    </form>
<?php require __DIR__ . '/_wizard-foot.php'; ?>
