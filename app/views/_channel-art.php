<?php
// ---------------------------------------------------------------------------
// Miniature illustration for one promotion channel, built from CSS primitives
// only — no images, so it stays sharp and weighs nothing.
//
// Expects $caKey (blog|product|service|review|youtube|facebook|instagram|
// tiktok|reddit|pinterest). The parent card sets --c to the channel colour;
// every tinted shape here keys off it.
//
// Shared primitives: .ca-frame (white panel), .ca-l (text line, .s short,
// .m medium), .ca-img (tinted block), .ca-sq (square tile), .ca-dot.
// ---------------------------------------------------------------------------
?>
<div class="ca ca-<?= e($caKey) ?>">
<?php switch ($caKey):

case 'blog': ?>
  <div class="ca-frame ca-doc">
    <span class="ca-h"></span>
    <span class="ca-l"></span><span class="ca-l m"></span><span class="ca-l"></span><span class="ca-l s"></span>
  </div>
  <div class="ca-frame ca-doc ca-back">
    <span class="ca-h"></span><span class="ca-l"></span><span class="ca-l s"></span>
  </div>
<?php break;

case 'product': ?>
  <div class="ca-frame ca-prod-card">
    <span class="ca-img"></span>
    <span class="ca-l m"></span>
    <span class="ca-price"></span>
  </div>
  <div class="ca-frame ca-prod-card ca-back">
    <span class="ca-img"></span>
    <span class="ca-l m"></span>
    <span class="ca-price"></span>
  </div>
<?php break;

case 'service': ?>
  <div class="ca-frame ca-list">
    <?php for ($i = 0; $i < 3; $i++): ?>
      <span class="ca-row"><i class="ca-tick"></i><i class="ca-l"></i><i class="ca-price"></i></span>
    <?php endfor; ?>
  </div>
<?php break;

case 'review': ?>
  <div class="ca-frame ca-review">
    <span class="ca-stars"><i></i><i></i><i></i><i></i><i></i></span>
    <span class="ca-l"></span><span class="ca-l m"></span>
    <span class="ca-row ca-who"><i class="ca-dot"></i><i class="ca-l s"></i></span>
  </div>
<?php break;

case 'youtube': ?>
  <div class="ca-frame ca-video">
    <span class="ca-screen"><i class="ca-play"></i></span>
    <span class="ca-l m"></span><span class="ca-l s"></span>
  </div>
<?php break;

case 'facebook': ?>
  <div class="ca-frame ca-post">
    <span class="ca-row"><i class="ca-dot"></i><i class="ca-l s"></i></span>
    <span class="ca-img"></span>
    <span class="ca-row ca-react"><i class="ca-pill"></i><i class="ca-pill"></i></span>
  </div>
<?php break;

case 'instagram': ?>
  <div class="ca-frame ca-tiles">
    <?php for ($i = 0; $i < 6; $i++): ?><span class="ca-sq"></span><?php endfor; ?>
  </div>
<?php break;

case 'tiktok': ?>
  <div class="ca-frame ca-phone">
    <span class="ca-screen tall"><i class="ca-play"></i></span>
  </div>
  <div class="ca-frame ca-phone ca-back">
    <span class="ca-screen tall"></span>
  </div>
<?php break;

case 'reddit': ?>
  <div class="ca-frame ca-list">
    <?php for ($i = 0; $i < 3; $i++): ?>
      <span class="ca-row"><i class="ca-vote"></i><span class="ca-stack"><i class="ca-l"></i><i class="ca-l s"></i></span></span>
    <?php endfor; ?>
  </div>
<?php break;

case 'pinterest': ?>
  <div class="ca-masonry">
    <span class="ca-pin a"></span><span class="ca-pin b"></span>
    <span class="ca-pin c"></span><span class="ca-pin d"></span>
    <span class="ca-pin e"></span><span class="ca-pin f"></span>
  </div>
<?php break;

endswitch; ?>
</div>
