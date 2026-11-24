<?php // How It Works — the AI Promotion Engine, moved off the homepage so it
      // has room to be read rather than scrolled past. The markup is the
      // homepage's, unchanged apart from the include path below; its styles
      // live under "promotion engine" in assets/css/style.css and the staged
      // run is in assets/js/app.js, both already loaded site-wide. ?>
<div class="wrap hiw-crumbs">
  <nav class="crumbs"><a href="/">Home</a><span>&rsaquo;</span>How it Works</nav>
</div>

<section class="pe" id="engine">
  <?php // APP_ROOT, not __DIR__: this view lives in views/pages/, where the
        // homepage's relative path resolved to a file that does not exist. ?>
  <?php require APP_ROOT . '/views/_icons.php'; ?>
  <div class="pe-glow" aria-hidden="true"></div>
  <div class="wrap pe-inner">

    <div class="pe-head">
      <span class="pe-eyebrow">Why a listing matters</span>
      <h1 class="pe-title">People stopped searching.<br><span>They started asking.</span></h1>
      <p class="pe-lead">Your customers used to type a search and scan ten links. Now they ask ChatGPT,
        Perplexity, Gemini or Claude who to hire — and get one answer, or three. A listing is how those
        answers find you, and how Google and Bing keep finding you too. It is free to join.</p>
    </div>

    <?php // The named engines, as the one row that carries the argument: these
          // are the places an answer gets assembled, and a business that is not
          // in the source material is not in the answer. ?>
    <div class="hiw-engines">
      <?php foreach ([
          ['sr', 'Google'], ['find', 'Bing'], ['sp', 'ChatGPT'],
          ['pp', 'Perplexity'], ['gm', 'Gemini'], ['cl', 'Claude'],
      ] as [$hiwI, $hiwL]): ?>
        <span class="hiw-engine"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-<?= $hiwI ?>"/></svg><?= e($hiwL) ?></span>
      <?php endforeach; ?>
    </div>

    <?php // Three reasons, each one a thing this directory actually does —
          // the markup, the moderation and the price are all checkable. ?>
    <ol class="pe-steps hiw-why">
      <li>
        <span class="pe-step-n" aria-hidden="true"></span>
        <h2>An answer names a few businesses</h2>
        <p>Ten blue links gave everyone a chance to be clicked. An assistant gives one answer.
          Being in the source it reads from is the whole game now.</p>
      </li>
      <li>
        <span class="pe-step-n" aria-hidden="true"></span>
        <h2>Written so a machine can quote you</h2>
        <p>Every listing is published with schema.org markup — LocalBusiness, reviews, services,
          opening hours — and the directory ships an llms.txt. Assistants read structured facts, not
          a brochure.</p>
      </li>
      <li>
        <span class="pe-step-n" aria-hidden="true"></span>
        <h2>Checked by a person, so it is trusted</h2>
        <p>Nothing goes live unread. That is why the data here is worth quoting, and it costs
          nothing — the free plan stays free, with no card and no trial clock.</p>
      </li>
    </ol>

    <div class="pe-demo">
      <div class="pe-try">
        <span class="pe-kicker">See it work</span>
        <h2>Watch it write one</h2>
        <p>Pick something you might publish and run it. Every stage below is a step the
          engine really performs — reading, writing, filing, publishing.</p>
        <div class="pe-picks" role="group" aria-label="Choose an example">
          <button type="button" class="on" data-demo="yt" aria-pressed="true">YouTube</button>
          <button type="button" data-demo="blog" aria-pressed="false">Blog post</button>
          <button type="button" data-demo="ig" aria-pressed="false">Instagram</button>
          <button type="button" data-demo="prod" aria-pressed="false">Product</button>
        </div>
        <?php // A real input, because this one earns it: what you paste comes
              // back in the card below. It still never calls the server, and
              // the note under the button says so. ?>
        <label class="pe-field" for="pe-url">
          <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-link"/></svg>
          <input type="text" id="pe-url" spellcheck="false" autocomplete="off"
                 value="https://youtube.com/watch?v=your-latest-video"
                 aria-label="A link to promote">
        </label>
        <button type="button" class="btn pe-boost" id="pe-run">
          <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-spark"/></svg>
          <span id="pe-run-text">Write my promotion</span>
        </button>

        <?php // The stages, listed rather than hidden inside an animation, so
              // the mechanism is readable even before anybody presses the
              // button and still readable afterwards. ?>
        <ol class="pe-stages" id="pe-stages">
          <li data-stage="read"><i></i>Reads your page</li>
          <li data-stage="write"><i></i>Writes the pitch in your voice</li>
          <li data-stage="file"><i></i>Files it under a category and city</li>
          <li data-stage="send"><i></i>Publishes it to the network</li>
        </ol>
        <small class="pe-demo-note">Paste one of your own links and run it again. Nothing is sent
          anywhere — the real pitch is written from the page itself once you join.</small>
      </div>

      <div class="pe-card" id="pe-card">
        <div class="pe-card-head">
          <span class="pe-badge"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-yt" id="pe-badge-icon"/></svg><span id="pe-badge-text">YouTube</span></span>
          <span class="pe-status" id="pe-status">Ready</span>
        </div>

        <div class="pe-card-body" aria-live="polite">
          <b class="pe-card-title" id="pe-title">How we doubled our bookings in 60 days</b>
          <p class="pe-card-pitch" id="pe-pitch">A short walkthrough of the two changes that filled our
            calendar — what we stopped doing, and the one question we started asking every caller.</p>
          <div class="pe-tags">
            <span class="pe-tag" id="pe-cat">Home services</span>
            <span class="pe-tag pe-tag-city" id="pe-city">Phoenix, AZ</span>
          </div>
        </div>

        <?php // Where it lands. These light up as the run finishes — the same
              // seven destinations the chip row above promises, so the card
              // cannot claim a reach the section has not already named. ?>
        <div class="pe-dest">
          <?php // Two labels, swapped by the card's state in CSS: before a run
                // nothing has been published, so it must not say it has. ?>
          <span class="pe-dest-h"><em class="pe-dest-idle">Goes out to</em><em class="pe-dest-live">Published to</em></span>
          <ul>
            <?php foreach ([
                ['people', 'Member network'], ['blog', 'Your storefront'], ['sr', 'Google Search'],
                ['sp', 'ChatGPT'], ['cl', 'Claude'], ['pp', 'Perplexity'], ['gm', 'Gemini'],
            ] as [$peD, $peDL]): ?>
              <li title="<?= e($peDL) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-<?= $peD ?>"/></svg></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>


    <?php // The other free tools. Name, icon and address come from
          // toutify_products(), the list that already feeds the homepage strip
          // and the band above the footer, so an address corrected there is
          // corrected here. The wording is local: these three are framed by
          // what they cost, which is the point of this section and not of the
          // others. A product with no address is a plain card, not a dead link.
    $hiwTools = [];
    foreach (toutify_products() as $hiwP) $hiwTools[$hiwP['key']] = $hiwP;
    $hiwPick = [
      ['key' => 'contentvendor', 'line' => 'Content and social media tools. Posts and pages written for you, on a schedule.'],
      ['key' => 'promomonster',  'line' => 'Free customer reviews. Ask every customer, reply to what comes back, show it off.'],
      ['key' => 'leadcrazy',     'line' => 'The Million Dollar Lead Form, free. Turn visitors into quote requests.'],
    ];
    ?>
    <section class="hiw-tools" aria-labelledby="hiw-tools-h">
      <h2 class="hiw-tools-h" id="hiw-tools-h">Your free account covers these too</h2>
      <p class="hiw-tools-sub">One <?= e(trim(setting('site_byline', 'Toutify')) ?: 'Toutify') ?> account, every tool.
        No credit card on any of them.</p>
      <div class="hiw-tools-grid">
        <?php foreach ($hiwPick as $hiwT): ?>
          <?php
            $hiwD   = $hiwTools[$hiwT['key']] ?? null;
            if (!$hiwD) continue;
            $hiwTag = $hiwD['url'] !== '' ? 'a' : 'div';
          ?>
          <<?= $hiwTag ?> class="hiw-tool"<?= $hiwTag === 'a' ? ' href="' . e($hiwD['url']) . '" rel="noopener"' : '' ?>>
            <span class="hiw-tool-ico"><?= toutify_icon($hiwD['icon']) ?></span>
            <h3><?= e($hiwD['name']) ?></h3>
            <p><?= e($hiwT['line']) ?></p>
            <?php if ($hiwTag === 'a'): ?><span class="hiw-tool-go">Open <?= e($hiwD['name']) ?> &rarr;</span><?php endif; ?>
          </<?= $hiwTag ?>>
        <?php endforeach; ?>
      </div>
    </section>

    <div class="pe-close">
      <a class="btn btn-primary pe-btn" href="/signup">Add my business free</a>
      <a class="pe-see" href="/promotions">See live member promotions →</a>
      <p class="pe-note">Free forever. No card, no contract. Add your business, drop in your first link,
        and the engine starts working today.</p>
    </div>

  </div>
</section>
