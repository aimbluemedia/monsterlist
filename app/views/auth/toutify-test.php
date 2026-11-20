<?php
// The hidden page for checking Toutify sign-in before it is switched on for
// everybody. Not linked from anywhere and noindexed — reached by typing the
// address. Its whole job is to answer "did that work?" without having to read
// a log.
$tfMode = toutify_mode();
?>
<div class="wrap" style="max-width:620px;padding:40px 20px 60px">
  <h1>Toutify sign-in test</h1>
  <p class="mute">A hidden page. Nothing links here, and search engines are told to
    leave it alone. The normal login is untouched and still at
    <a href="/login" style="color:var(--accent)">/login</a>.</p>

  <div class="card card-pad" style="margin-top:22px">
    <h3>Right now</h3>
    <div class="info-row"><span class="mute">Mode</span><span><strong><?= e($tfMode) ?></strong><?php
      if ($tfMode === 'test'): ?> <span class="mute">— only allow-listed emails can sign in</span><?php
      endif; ?></span></div>
    <div class="info-row"><span class="mute">Toutify</span><span><?= e(toutify_issuer()) ?></span></div>
    <div class="info-row"><span class="mute">Redirect URI</span><span><code style="font-size:.8rem"><?= e(site_url('/auth/toutify/callback')) ?></code></span></div>
    <?php // The one thing that is worth saying out loud: this must match what
          // Toutify has registered, character for character, or the sign-in
          // fails with an error that does not say why. ?>
    <p class="form-note">That redirect URI has to match the one registered on Toutify exactly.</p>
  </div>

  <div class="card card-pad" style="margin-top:16px">
    <h3>Signed in?</h3>
    <?php if ($me): ?>
      <div class="info-row"><span class="mute">Member</span><span><?= e($me['email']) ?> <span class="mute">(#<?= (int)$me['id'] ?>)</span></span></div>
      <div class="info-row"><span class="mute">Name</span><span><?= e($me['name']) ?></span></div>
      <?php if (!empty($me['business_name'])): ?>
        <div class="info-row"><span class="mute">Business</span><span><?= e($me['business_name']) ?></span></div>
      <?php endif; ?>
      <div class="info-row"><span class="mute">Toutify id</span><span><?= $me['toutify_sub'] ? '<code style="font-size:.8rem">' . e($me['toutify_sub']) . '</code>' : '<span class="mute">not linked</span>' ?></span></div>
      <div class="info-row"><span class="mute">Toutify status</span><span><?= e($me['toutify_status'] ?: '—') ?></span></div>
      <div class="info-row"><span class="mute">Signed in via</span><span><?= !empty($_SESSION['tf_sub']) ? 'Toutify' : 'MonsterList password' ?></span></div>
      <p style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn btn-primary" href="/members">My account</a>
        <?php if (!empty($_SESSION['tf_sub'])): ?>
          <a class="btn btn-ghost" href="/auth/toutify/logout">Log out of both</a>
        <?php else: ?>
          <a class="btn btn-ghost" href="/logout">Log out</a>
        <?php endif; ?>
      </p>
    <?php else: ?>
      <p class="mute">Nobody is signed in.</p>
      <p style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn btn-primary" href="/auth/toutify/login">Sign in with Toutify</a>
        <a class="btn btn-ghost" href="/auth/toutify/login?signup=1">Create a Toutify account</a>
      </p>
    <?php endif; ?>
  </div>
</div>
