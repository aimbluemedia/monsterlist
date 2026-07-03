<div class="wrap"><div class="card auth-box card-pad" style="max-width:520px">
  <h1>Claim “<?= e($biz['name']) ?>”</h1>
  <p class="mute"><?= e($biz['city_name'] ?? '') ?> · Once approved, this listing moves into your account and you can edit every detail, add photos and reply to reviews.</p>

  <?php if ($existing): ?>
    <div class="flash flash-info">
      You submitted a claim for this business on <?= e(date('M j, Y', strtotime($existing['created_at']))) ?> —
      status: <strong><?= e($existing['status']) ?></strong>.
      <?= $existing['status'] === 'pending' ? 'Our team will email you once it\'s reviewed.' : '' ?>
    </div>
  <?php else: ?>
    <form method="post"><?= csrf_field() ?>
      <label>How are you connected to this business? *</label>
      <textarea name="message" rows="5" maxlength="1000" required
        placeholder="e.g. I'm the owner — you can verify via the email on our website (name@business.com) or by calling the number on the listing."></textarea>
      <p class="form-note">Tip: mentioning an email address at the business's own domain speeds up verification.</p>
      <button class="btn btn-primary btn-block" style="margin-top:14px">Submit claim</button>
    </form>
  <?php endif; ?>
</div></div>
