<?php
// The embedded Toutify sign-up form.
//
// Visitors create their Toutify account without leaving MonsterList. No
// password is ever typed here: Toutify emails a link to choose one and finish.
//
// The script renders itself into a shadow root, so none of this site's CSS
// reaches inside it — data-accent is the only styling hook there is. Keep the
// card around it doing the visual work.
//
// Contract (https://toutify.com/embed/signup.js):
//   required  data-client-id
//   optional  data-title, data-button, data-accent, data-login-url
//   prefill   data-name, data-business-name, data-email, data-website
//
// Deliberately no <h1> saying "Create your free account": the script renders
// that heading itself, and repeating it is what makes ContentVendor's page
// show the title twice.
$tfBy     = trim(setting('site_byline', 'Toutify')) ?: 'Toutify';
$tfIssuer = rtrim(toutify_issuer(), '/');
?>
<div class="wrap"><div class="card auth-box card-pad">
  <h1>Add your business to <?= e(setting('site_name')) ?></h1>
  <p class="mute">One free <?= e($tfBy) ?> account covers <?= e(setting('site_name')) ?> and every
    other <?= e($tfBy) ?> tool. No credit card, and the free plan stays free.</p>

  <?php // data-accent mirrors --accent in assets/css/style.css. It cannot be
        // read from the stylesheet at render time, so the two are kept in step
        // by hand; getting it wrong only changes the button colour.
        //
        // data-login-url is deliberately NOT set. Passing it makes the script
        // render its own "Already have an account? Log in" inside the shadow
        // root, where this site's CSS cannot reach it — so it arrives small and
        // grey, directly above the identical link below. One of the two had to
        // go, and the one outside the shadow root is the one that can be
        // styled to match the rest of the site. ?>
  <div data-toutify-signup
       data-client-id="<?= e(toutify_client_id()) ?>"
       data-accent="#2563eb"></div>

  <noscript>
    <div class="flash flash-error" style="margin-top:14px">
      This form needs JavaScript. You can
      <a href="<?= e($tfIssuer) ?>/members/join.php">create your account on <?= e($tfBy) ?></a> instead.
    </div>
  </noscript>

  <?php // The div above stays empty if the script is blocked or fails to load,
        // which would otherwise be a dead end with nothing on screen. ?>
  <p class="mute" style="margin-top:16px">Already a member?
    <a href="/auth/toutify/login" style="color:var(--accent);font-weight:700">Log in</a></p>

  <p class="mute" style="margin-top:6px;font-size:.9em">Form not loading?
    <a href="<?= e($tfIssuer) ?>/members/join.php" style="color:var(--accent);font-weight:600">Sign up on <?= e($tfBy) ?> →</a></p>
</div></div>

<?php // Loaded from the configured issuer, not a hard-coded toutify.com, so a
      // staging issuer serves its own copy. The script works out its own API
      // base from this src. ?>
<script src="<?= e($tfIssuer) ?>/embed/signup.js" async></script>
