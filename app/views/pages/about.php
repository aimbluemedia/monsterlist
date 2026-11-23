<?php // The all-in-one section, moved here from the homepage. It leads the
      // page, so its heading is the <h1> and the former "About MonsterList"
      // heading below it steps down to an <h2> — one h1, in document order.
      // The page <title> still says "About", so this is a change of emphasis
      // on the page, not of what the page is about.
$abSite = setting('site_name'); ?>
<section class="lp-section">
  <div class="wrap">
    <h1 class="lp-h2">The All-In-One Platform That <span class="lp-accent">Does It For You</span></h1>
    <p class="lp-center-sub">You run your business. <?= e($abSite) ?> runs your presence — one profile that
      feeds search engines, AI assistants and your customers everything they need to choose you.</p>

    <?php // APP_ROOT, not __DIR__: this view sits a directory deeper than the
          // homepage, and the relative path that worked there resolves to
          // views/pages/_promo-graphic.php from here, which does not exist. ?>
    <?php require APP_ROOT . '/views/_promo-graphic.php'; ?>
  </div>
</section>

<div class="wrap"><section class="section" style="max-width:720px">
  <h2>About <?= e($abSite) ?></h2>
  <p><?= e($abSite) ?> is a worldwide small-business directory. Our mission is simple:
  make every great local business easy to find — whether someone is searching on Google,
  asking an AI assistant, or browsing by city.</p>
  <p>Every listing is reviewed by a human before it goes live, so the directory stays
  clean, current and trustworthy. Business owners can claim a free listing in minutes,
  and upgrade to a full storefront with photos, video, social feeds and analytics.</p>
  <p><a class="btn btn-primary" href="/add-listing">Add your business</a></p>
</section></div>
