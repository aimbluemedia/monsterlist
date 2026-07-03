<div class="wrap"><div class="card auth-box card-pad">
  <h1>Choose a new password</h1>
  <?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>
  <?php if ($valid): ?>
    <form method="post"><?= csrf_field() ?>
      <input type="hidden" name="email" value="<?= e($email) ?>">
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <label>New password</label>
      <input type="password" name="password" minlength="8" required>
      <button class="btn btn-primary btn-block" style="margin-top:14px">Update password</button>
    </form>
  <?php else: ?>
    <div class="flash flash-error">This reset link is invalid or has expired.</div>
    <p><a class="btn btn-ghost" href="/forgot">Request a new link</a></p>
  <?php endif; ?>
</div></div>
