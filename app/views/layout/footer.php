</main>

<?php require APP_ROOT . '/views/layout/_membership.php'; ?>

<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-brand">
      <?php require APP_ROOT . '/views/layout/_logo.php'; ?>
      <p>A human-moderated directory of small businesses, worldwide. Every listing is
        checked by a person before it goes live — free to join, free to be found.</p>
    </div>
    <div>
      <h4><?= e(setting('site_name')) ?></h4>
      <a href="/browse">Browse the directory</a>
      <a href="/search">Search businesses</a>
      <a href="/pricing">Pricing &amp; plans</a>
      <a href="/about">About</a>
      <a href="/contact">Contact</a>
    </div>
    <div>
      <h4>Browse by country</h4>
      <a href="/us">United States</a>
      <a href="/gb">United Kingdom</a>
      <a href="/ca">Canada</a>
      <a href="/au">Australia</a>
    </div>
    <div>
      <h4>Your account</h4>
      <a href="/add-listing">Add your business FREE</a>
      <a href="/login">Log in</a>
      <a href="/account">Members dashboard</a>
    </div>
    <div>
      <h4>Legal</h4>
      <a href="/privacy">Privacy</a>
      <a href="/terms">Terms</a>
    </div>
  </div>
  <div class="wrap footer-bottom">
    <span>© <?= date('Y') ?> <?= e(setting('site_name')) ?><?php
      $ftBy = trim(setting('site_byline', 'Toutify'));
      echo $ftBy !== '' ? ' by ' . e($ftBy) : ''; ?>. All rights reserved.</span>
    <span class="footer-promise">Every listing is read by a person before it goes live.</span>
  </div>
</footer>
<script src="<?= e(asset('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
