<div class="wrap"><section class="section" style="max-width:560px">
  <h1>Contact us</h1>
  <?php if (!empty($sent)): ?>
    <div class="flash flash-success">Thanks — your message has been sent. We usually reply within one business day.</div>
  <?php else: ?>
    <p class="mute">Questions about listings, memberships or anything else? Send us a note.</p>
    <form method="post" class="card card-pad"><?= csrf_field() ?>
      <label>Your email</label>
      <input type="email" name="email" required>
      <label>Message</label>
      <textarea name="message" rows="6" required maxlength="4000"></textarea>
      <button class="btn btn-primary" style="margin-top:14px">Send message</button>
    </form>
  <?php endif; ?>
</section></div>
