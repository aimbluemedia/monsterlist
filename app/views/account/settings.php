<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <h1>Account settings</h1>
    <?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" class="card card-pad" style="max-width:480px"><?= csrf_field() ?>
      <label>Name</label>
      <input type="text" name="name" value="<?= e($u['name']) ?>">
      <label>Email</label>
      <input type="email" value="<?= e($u['email']) ?>" disabled>
      <p class="form-note">Contact support to change your email.</p>
      <h3 style="margin-top:22px">Change password</h3>
      <label>Current password</label>
      <input type="password" name="current_password" autocomplete="current-password">
      <label>New password</label>
      <input type="password" name="new_password" minlength="8" autocomplete="new-password">
      <button class="btn btn-primary" style="margin-top:16px">Save changes</button>
    </form>
  </div>
</div>
