<?php
// The site logo: the mark, the site name, and the byline under it.
//
// Shared by the public header and the footer, which drew the same three
// elements separately before the byline made them four lines each. The byline
// is a setting rather than a constant — it names the company behind the site,
// and a company name is exactly the kind of thing that gets rewritten once.
// The default here, not only in the seed: a site that has not run the latest
// upgrade SQL has no row for this yet, and setting() falls back only when the
// row is ABSENT. So a fresh upload shows the byline straight away, while a row
// saved as empty still hides it — which is what clearing the field must do.
$lgBy = trim(setting('site_byline', 'Toutify'));
?>
<a class="logo" href="/">
  <span class="logo-mark">M</span>
  <span class="logo-text">
    <span class="logo-name"><?= e(setting('site_name')) ?></span>
    <?php if ($lgBy !== ''): ?><span class="logo-by">by <?= e($lgBy) ?></span><?php endif; ?>
  </span>
</a>
