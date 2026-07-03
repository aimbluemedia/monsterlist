<div class="wrap"><div class="card auth-box card-pad">
  <h1>Reset your password</h1>
  <?php if ($done): ?>
    <div class="flash flash-success">If that email has an account, a reset link is on its way. Check your inbox.</div>
  <?php else: ?>
    <p class="mute">Enter your account email and we'll send a reset link.</p>
    <form method="post"><?= csrf_field() ?>
      <label>Email</label>
      <input type="email" name="email" required>
      <button class="btn btn-primary btn-block" style="margin-top:14px">Send reset link</button>
    </form>
  <?php endif; ?>
  <p class="mute" style="margin-top:16px"><a href="/login" style="color:var(--accent);font-weight:700">Back to login</a></p>
</div></div>
