</main>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div>
      <a class="logo" href="/"><span class="logo-mark">M</span> <?= e(setting('site_name')) ?></a>
      <p class="mute"><?= e(setting('site_tagline')) ?>. Free listings for every small business, everywhere.</p>
    </div>
    <div>
      <h4>Explore</h4>
      <a href="/browse">Browse the directory</a>
      <a href="/search">Search businesses</a>
      <a href="/us">United States</a>
      <a href="/gb">United Kingdom</a>
      <a href="/ca">Canada</a>
      <a href="/au">Australia</a>
    </div>
    <div>
      <h4>For businesses</h4>
      <a href="/add-listing">Add your business FREE</a>
      <a href="/pricing">Pricing &amp; plans</a>
      <a href="/login">Member login</a>
    </div>
    <div>
      <h4>Company</h4>
      <a href="/about">About</a>
      <a href="/contact">Contact</a>
      <a href="/terms">Terms of service</a>
      <a href="/privacy">Privacy policy</a>
    </div>
  </div>
  <div class="wrap footer-bottom">
    <span>© <?= date('Y') ?> <?= e(setting('site_name')) ?>. All rights reserved.</span>
  </div>
</footer>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
