<div class="wrap"><div class="card auth-box card-pad">
  <h1>Create your free account</h1>
  <p class="mute">List your business in minutes. No credit card required.</p>
  <?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post"><?= csrf_field() ?>
    <label>Your name</label>
    <input type="text" name="name" value="<?= e(post('name')) ?>" required>
    <label>Email</label>
    <input type="email" name="email" value="<?= e(post('email')) ?>" required>
    <label>Password</label>
    <input type="password" name="password" minlength="8" required>
    <p class="form-note">At least 8 characters.</p>
    <button class="btn btn-primary btn-block" style="margin-top:14px">Sign up free</button>
  </form>
  <p class="mute" style="margin-top:16px">Already a member? <a href="/login" style="color:var(--accent);font-weight:700">Log in</a></p>
</div></div>
