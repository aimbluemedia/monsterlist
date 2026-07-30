<?php
// ---------------------------------------------------------------------------
// Homepage promotion-engine graphic. HTML/CSS/SVG only — no images.
//
//   ml-stage
//     ml-canvas   the member dashboard on a monitor + floating reach card
//     ml-arc      dotted sweep carrying every channel the profile feeds
//
// The channels ride a cubic bezier that starts at the top-right corner, curves
// down past the right of the screen and along under it, and finishes at the
// bottom-left corner. Node positions are spaced by arc length rather than by
// curve parameter, so they stay evenly apart through the bends, and the dotted
// trail is sampled from the same curve — adding or removing a channel
// redistributes everything automatically.
//
// Social channels carry a view count that counts up when the graphic scrolls
// into view (assets/js/app.js). Search and AI engines carry a check instead:
// being cited there is a yes/no, not a number. Without JS — or with reduced
// motion — the final numbers are simply there from the start.
//
// On narrow screens the arc collapses into a plain grid of the same ovals.
// ---------------------------------------------------------------------------

// [icon id, channel, metric label, view count]  — null metric renders a check
$mlNodes = [
    ['fb', 'Facebook',   'Views', 74],
    ['ig', 'Instagram',  'Views', 68],
    ['tt', 'TikTok',     'Views', 51],
    ['yt', 'YouTube',    'Views', 63],
    ['rd', 'Reddit',     'Views', 23],
    ['pt', 'Pinterest',  'Views', 36],
    ['sr', 'Google',     null,    null],
    ['sp', 'ChatGPT',    null,    null],
    ['pp', 'Perplexity', null,    null],
    ['cl', 'Claude',     null,    null],
    ['gm', 'Gemini',     null,    null],
];

// Curve control points, in the SVG's user space. Percentages of this box drive
// the layout, so the whole arc scales with the stage.
$mlW = 1120; $mlH = 700;
$mlP = [[860, 66], [920, 620], [820, 720], [120, 508]];   // top-right → bottom-left

$mlBez = function (float $t) use ($mlP) {
    $u = 1 - $t;
    $a = $u * $u * $u; $b = 3 * $u * $u * $t; $c = 3 * $u * $t * $t; $d = $t * $t * $t;
    return [
        $a * $mlP[0][0] + $b * $mlP[1][0] + $c * $mlP[2][0] + $d * $mlP[3][0],
        $a * $mlP[0][1] + $b * $mlP[1][1] + $c * $mlP[2][1] + $d * $mlP[3][1],
    ];
};

// Walk the curve once, recording points and cumulative length.
$mlSteps = 400;
$mlPts = []; $mlLen = [0.0];
for ($i = 0; $i <= $mlSteps; $i++) {
    $mlPts[$i] = $mlBez($i / $mlSteps);
    if ($i > 0) {
        $dx = $mlPts[$i][0] - $mlPts[$i - 1][0];
        $dy = $mlPts[$i][1] - $mlPts[$i - 1][1];
        $mlLen[$i] = $mlLen[$i - 1] + sqrt($dx * $dx + $dy * $dy);
    }
}
$mlTotal = end($mlLen);

/** Point at a given fraction of the curve's LENGTH, so nodes space evenly. */
$mlAt = function (float $frac) use ($mlPts, $mlLen, $mlTotal, $mlSteps) {
    $want = $mlTotal * $frac;
    for ($i = 1; $i <= $mlSteps; $i++) {
        if ($mlLen[$i] < $want) continue;
        $span = $mlLen[$i] - $mlLen[$i - 1];
        $k = $span > 0 ? ($want - $mlLen[$i - 1]) / $span : 0;
        return [
            $mlPts[$i - 1][0] + ($mlPts[$i][0] - $mlPts[$i - 1][0]) * $k,
            $mlPts[$i - 1][1] + ($mlPts[$i][1] - $mlPts[$i - 1][1]) * $k,
        ];
    }
    return $mlPts[$mlSteps];
};

// Dotted trail — every 5th sample is plenty for a smooth polyline.
$mlTrail = [];
for ($i = 0; $i <= $mlSteps; $i += 5) {
    $mlTrail[] = round($mlPts[$i][0], 1) . ',' . round($mlPts[$i][1], 1);
}

$mlCount = count($mlNodes);
?>
<?php require __DIR__ . '/_icons.php'; ?>

<div class="ml-stage" id="ml-stage">
 <div class="ml-canvas">

  <!-- desktop dashboard -->
  <div class="ml-screen">
    <div class="ml-bar">
      <span class="ml-dots"><i></i><i></i><i></i></span>
      <em><?= e(strtolower($site)) ?>.org/members</em>
    </div>

    <div class="ml-app">
      <aside class="ml-side">
        <span class="ml-brand"><b>M</b><?= e($site) ?></span>
        <u class="on">Dashboard</u>
        <u>Storefront</u>
        <u>Content</u>
        <u>Video</u>
        <u>Products</u>
        <u>Services</u>
        <u>Reviews</u>
        <u>Social reach</u>
        <u>AI visibility</u>
      </aside>

      <div class="ml-main">
        <div class="ml-kpis">
          <div><small>Storefront views</small><b data-count="2048">2,048</b><span class="up">+24.6%</span></div>
          <div><small>Content views</small><b data-count="57">57</b><span class="up">+18.9%</span></div>
          <div><small>Video views</small><b data-count="62">62</b><span class="up">+31.4%</span></div>
          <div><small>Product views</small><b data-count="41">41</b><span class="up">+42.1%</span></div>
        </div>

        <div class="ml-panels">
          <div class="ml-panel">
            <div class="ml-panel-h"><b>Visibility overview</b><span class="ml-live">Live</span></div>
            <div class="ml-graph">
              <svg viewBox="0 0 320 120" preserveAspectRatio="none" aria-hidden="true">
                <defs>
                  <linearGradient id="ml-fill" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#2563eb" stop-opacity=".28"/>
                    <stop offset="100%" stop-color="#2563eb" stop-opacity="0"/>
                  </linearGradient>
                </defs>
                <path d="M0 96 40 88 80 92 120 70 160 74 200 50 240 42 280 24 320 12 320 120 0 120Z" fill="url(#ml-fill)"/>
                <path d="M0 96 40 88 80 92 120 70 160 74 200 50 240 42 280 24 320 12" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
              </svg>
              <span class="ml-graph-pin"></span>
            </div>
            <div class="ml-months"><span>Jan</span><span>Mar</span><span>May</span><span>Jul</span><span>Sep</span><span>Nov</span></div>
          </div>

          <div class="ml-panel">
            <div class="ml-panel-h"><b>Where you show up</b></div>
            <div class="ml-donut-row">
              <div class="ml-donut" role="img" aria-label="Traffic split across search, AI assistants and social"></div>
              <ul class="ml-legend">
                <li><i style="background:#2563eb"></i>Google</li>
                <li><i style="background:#60a5fa"></i>ChatGPT</li>
                <li><i style="background:#0f172a"></i>Perplexity</li>
                <li><i style="background:#f97316"></i>Claude</li>
                <li><i style="background:#22c55e"></i>Gemini</li>
                <li><i style="background:#cbd5e1"></i>Social</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="ml-feed">
          <div><span class="ml-tag g">Review</span>New 5-star review — “Best in town”<b>2m ago</b></div>
          <div><span class="ml-tag b">AI</span>Cited by ChatGPT for “best bakery near me”<b>14m ago</b></div>
          <div><span class="ml-tag o">Video</span>Storefront video published to YouTube<b>1h ago</b></div>
        </div>
      </div>
    </div>
  </div>

  <!-- floating phone -->
  <div class="ml-phone" aria-hidden="true">
    <div class="ml-phone-top"><span>9:41</span><i></i></div>
    <h4>Your reach<br>this month</h4>
    <div class="ml-phone-grid">
      <div><small>Storefront</small><b data-count="2048">2,048</b></div>
      <div><small>Content</small><b data-count="57">57</b></div>
      <div><small>Video</small><b data-count="62">62</b></div>
      <div><small>Services</small><b data-count="34">34</b></div>
    </div>
    <span class="ml-phone-btn">View full report</span>
  </div>

 </div>

 <!-- channel arc -->
 <div class="ml-arc">
   <svg class="ml-arc-line" viewBox="0 0 <?= $mlW ?> <?= $mlH ?>" preserveAspectRatio="none" aria-hidden="true">
     <polyline points="<?= implode(' ', $mlTrail) ?>" fill="none" stroke="#c9d2e3" stroke-width="2"
               stroke-linecap="round" stroke-dasharray="2 10"/>
   </svg>

   <?php foreach ($mlNodes as $i => [$id, $label, $metric, $target]): ?>
     <?php [$x, $y] = $mlAt($mlCount > 1 ? $i / ($mlCount - 1) : 0); ?>
     <div class="ml-node" style="left:<?= round($x / $mlW * 100, 2) ?>%;top:<?= round($y / $mlH * 100, 2) ?>%;--d:<?= round($i * .12, 2) ?>s">
       <?php if ($metric === null): ?>
         <span class="ml-oval ml-oval-ok" title="Listed and citable">
           <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-ok"/></svg>
           <em>Found</em>
         </span>
       <?php else: ?>
         <span class="ml-oval">
           <b data-count="<?= $target ?>"><?= number_format($target) ?></b>
           <em><?= e($metric) ?></em>
         </span>
       <?php endif; ?>
       <span class="ml-node-name">
         <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-<?= $id ?>"/></svg><?= e($label) ?>
       </span>
     </div>
   <?php endforeach; ?>
 </div>

</div>

<p class="ml-caption">One profile feeds all of it — your storefront, content, video, products, services
  and reviews, pushed to every network and written so search engines and AI assistants recommend you by name.</p>
