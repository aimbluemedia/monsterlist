<?php // How It Works — the AI Promotion Engine, moved off the homepage so it
      // has room to be read rather than scrolled past. The markup is the
      // homepage's, unchanged apart from the include path below; its styles
      // live under "promotion engine" in assets/css/style.css and the staged
      // run is in assets/js/app.js, both already loaded site-wide. ?>
<div class="wrap hiw-crumbs">
  <nav class="crumbs"><a href="/">Home</a><span>&rsaquo;</span>How it works</nav>
</div>

<section class="pe" id="engine">
  <?php // APP_ROOT, not __DIR__: this view lives in views/pages/, where the
        // homepage's relative path resolved to a file that does not exist. ?>
  <?php require APP_ROOT . '/views/_icons.php'; ?>
  <div class="pe-glow" aria-hidden="true"></div>
  <div class="wrap pe-inner">

    <div class="pe-head">
      <span class="pe-eyebrow">AI Promotion Engine</span>
      <?php // An <h1>, not the <h2> it was on the homepage: there it sat under
            // the homepage's own heading, and here it is the page's heading.
            // Same class, so nothing about the look changes. ?>
      <h1 class="pe-title">You published it.<br><span>Now get it seen.</span></h1>
      <p class="pe-lead">Paste a link to anything you have already published and the engine does the rest —
        writing the pitch, filing it under the right category and city, and putting it in front of people
        who have never heard of you.</p>
    </div>

    <?php // Three numbered steps, because the question a visitor actually has
          // is "what do I have to do?" — and the honest answer is "paste one
          // link". That was previously buried in the middle of a paragraph. ?>
    <ol class="pe-steps">
      <li>
        <span class="pe-step-n" aria-hidden="true"></span>
        <h2>Paste a link</h2>
        <p>A blog post, a product, a service page, a new review, a video — anything with a URL.
          That is the whole of your side of it.</p>
      </li>
      <li>
        <span class="pe-step-n" aria-hidden="true"></span>
        <h2>The AI writes the pitch</h2>
        <p>It reads the page, writes a short pitch in your voice, and files it under the right
          category and city. Nothing to write, nothing to schedule.</p>
      </li>
      <li>
        <span class="pe-step-n" aria-hidden="true"></span>
        <h2>It goes out</h2>
        <p>To the member network, your storefront page, Google, and the AI assistants your
          customers now ask for recommendations.</p>
      </li>
    </ol>

    <?php // The two lists were 17 bulleted rows in side-by-side columns, which
          // reads as a specification. As chips they are scanned in a second,
          // which is all anybody does with a compatibility list. ?>
    <div class="pe-rows">
      <div class="pe-row">
        <h3>Works with anything you publish</h3>
        <ul>
          <?php foreach ([
              ['blog', 'Blog posts'], ['prod', 'Products'], ['serv', 'Services'], ['star', 'Reviews'],
              ['yt', 'YouTube'], ['fb', 'Facebook'], ['ig', 'Instagram'],
              ['tt', 'TikTok'], ['rd', 'Reddit'], ['pt', 'Pinterest'],
          ] as [$peI, $peL]): ?>
            <li><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-<?= $peI ?>"/></svg><?= e($peL) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="pe-row">
        <h3>Shows up where people look</h3>
        <ul>
          <?php foreach ([
              ['people', 'Member network'], ['blog', 'Your storefront'], ['sr', 'Google Search'],
              ['sp', 'ChatGPT'], ['cl', 'Claude'], ['pp', 'Perplexity'], ['gm', 'Gemini'],
          ] as [$peI, $peL]): ?>
            <li><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-<?= $peI ?>"/></svg><?= e($peL) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <?php // What the engine actually produces, shown rather than described.
          // The old demo animated invented view counts and then admitted in
          // small print that they were invented — which teaches a visitor that
          // the numbers on this page cannot be trusted. The real output is the
          // written pitch, the category and the city, so that is what this
          // shows, and nothing here claims to be a result. ?>
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

    <div class="pe-close">
      <a class="btn btn-primary pe-btn" href="/signup">Add my business free</a>
      <a class="pe-see" href="/promotions">See live member promotions →</a>
      <p class="pe-note">Free forever. No card, no contract. Add your business, drop in your first link,
        and the engine starts working today.</p>
    </div>

  </div>
</section>
