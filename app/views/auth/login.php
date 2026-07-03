<div class="wrap"><div class="card auth-box card-pad">
  <h1>Log in</h1>
  <?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post"><?= csrf_field() ?>
    <label>Email</label>
    <input type="email" name="email" value="<?= e(post('email')) ?>" required>
    <label>Password</label>
    <input type="password" name="password" required>
    <button class="btn btn-primary btn-block" style="margin-top:14px">Log in</button>
  </form>
  <p class="mute" style="margin-top:16px">
    <a href="/forgot" style="color:var(--accent);font-weight:700">Forgot password?</a> ·
    New here? <a href="/signup" style="color:var(--accent);font-weight:700">Sign up free</a>
  </p>
</div></div>
