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
$mlP = [[1050, 58], [1024, 508], [430, 700], [88, 618]];   // top-right → bottom-left

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
<svg class="ml-sprite" aria-hidden="true" focusable="false">
  <symbol id="ml-fb" viewBox="0 0 24 24"><path d="M13.5 21v-7.5h2.5l.5-3h-3V8.8c0-.9.3-1.3 1.3-1.3H16.6V4.8A17 17 0 0 0 14.5 4.7c-2.2 0-3.6 1.3-3.6 3.8v2H8.4v3h2.5V21z"/></symbol>
  <symbol id="ml-ig" viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1.3"/></symbol>
  <symbol id="ml-tt" viewBox="0 0 24 24"><path d="M14 4v9.6a3 3 0 1 1-2.6-3v2.6a.6.6 0 1 0 .6.6V4z"/><path d="M14 4c.4 2.3 1.9 3.7 4.2 3.9v2.6C16.6 10.4 15.1 9.7 14 8.6z"/></symbol>
  <symbol id="ml-yt" viewBox="0 0 24 24"><rect x="2.5" y="5.5" width="19" height="13" rx="4.2"/><path d="m10.4 9.3 5 2.7-5 2.7z"/></symbol>
  <symbol id="ml-rd" viewBox="0 0 24 24"><circle cx="12" cy="13.6" r="7.2"/><circle cx="9.4" cy="13" r="1.05"/><circle cx="14.6" cy="13" r="1.05"/><path d="M9.2 16.4c1.8 1.3 3.8 1.3 5.6 0"/><path d="M16.3 5.6 14.4 12"/><circle cx="16.8" cy="5.1" r="1.5"/></symbol>
  <symbol id="ml-pt" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.4"/><path d="M10.2 20.4 12.6 11"/><path d="M9.4 13.4c-.9-2.7.5-5.3 3.1-5.6 2.3-.3 3.9 1.2 3.7 3.4-.2 2.3-1.8 3.7-3.4 3.3-.9-.2-1.3-1-1.1-1.9"/></symbol>
  <symbol id="ml-sr" viewBox="0 0 24 24"><path d="M20.5 12a8.5 8.5 0 1 1-2.5-6"/><path d="M20.5 12h-7.7"/></symbol>
  <symbol id="ml-sp" viewBox="0 0 24 24"><path d="m12 2.8 2.3 6.6 6.6 2.3-6.6 2.3-2.3 6.6-2.3-6.6L3.1 11.7l6.6-2.3z"/></symbol>
  <symbol id="ml-pp" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.6"/><path d="m16 16 4.6 4.6"/><path d="M11 7.6v6.8M7.6 11h6.8"/></symbol>
  <symbol id="ml-cl" viewBox="0 0 24 24"><path d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M18.4 5.6 5.6 18.4"/></symbol>
  <symbol id="ml-ok" viewBox="0 0 24 24"><path d="m5 12.5 4.6 4.6L19 7.6"/></symbol>
  <symbol id="ml-gm" viewBox="0 0 24 24"><path d="M12 2.6c.4 4.9 4.5 8.9 9.4 9.4-4.9.4-8.9 4.5-9.4 9.4-.4-4.9-4.5-8.9-9.4-9.4 4.9-.5 8.9-4.5 9.4-9.4z"/></symbol>
</svg>

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
