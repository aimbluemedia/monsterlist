<?php
// The Toutify membership band, directly above the footer on every page.
// Data and the note about filling in the product URLs: app/lib/toutify.php.
$tfMe   = current_user();
$tfName = setting('site_name');
$tfBy   = trim(setting('site_byline', 'Toutify')) ?: 'Toutify';
?>
<section class="tf" aria-labelledby="tf-h">
  <div class="wrap">
    <p class="tf-eyebrow"><?= e($tfBy) ?> membership</p>
    <h2 class="tf-h" id="tf-h">One free account. <span>Your whole growth toolkit.</span></h2>
    <p class="tf-sub">
      Join <?= e($tfBy) ?> once and <?= e($tfName) ?> comes with it — plus the rest of the tools built for
      local businesses. No credit card, no trial clock, and the free plan stays free.
    </p>

    <div class="tf-cta">
      <?php // Members are already in, so the panel stops selling and points at
            // the thing they actually came back for. ?>
      <?php if ($tfMe): ?>
        <a class="tf-btn" href="/account">Go to your dashboard</a>
        <p class="tf-alt">Signed in as <?= e($tfMe['email']) ?>.</p>
      <?php else: ?>
        <a class="tf-btn" href="/add-listing">Start free — takes a minute</a>
        <p class="tf-alt">Already a member? <a href="/login">Log in</a></p>
      <?php endif; ?>
    </div>

    <ul class="tf-ticks">
      <li>Free forever</li>
      <li>No credit card</li>
      <li>One login, every tool</li>
      <li>Cancel any time</li>
    </ul>

    <p class="tf-eyebrow tf-eyebrow-2">Included with your free membership</p>
    <div class="tf-grid">
      <?php foreach (toutify_products() as $tfP): ?>
        <?php
          $tfHere = $tfP['key'] === TOUTIFY_HERE;
          // The card is a link only when there is somewhere to send people:
          // the site you are standing on, and any product whose address has
          // not been filled in yet, stay as plain cards.
          $tfTag  = (!$tfHere && $tfP['url'] !== '') ? 'a' : 'div';
        ?>
        <<?= $tfTag ?> class="tf-card<?= $tfHere ? ' tf-card-here' : '' ?>"
          <?= $tfTag === 'a' ? 'href="' . e($tfP['url']) . '" rel="noopener"' : '' ?>>
          <span class="tf-ico"><?= toutify_icon($tfP['icon']) ?></span>
          <div class="tf-card-t">
            <h3><?= e($tfP['name']) ?><?php if ($tfHere): ?> <span class="tf-here">You are here</span><?php endif; ?></h3>
            <p class="tf-card-by">by <?= e($tfBy) ?></p>
            <p class="tf-card-b"><?= e($tfP['blurb']) ?></p>
          </div>
        </<?= $tfTag ?>>
      <?php endforeach; ?>
    </div>
  </div>
</section>
