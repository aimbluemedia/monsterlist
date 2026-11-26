<?php // How It Works.
      //
      // Built as a stack of full-bleed bands that alternate dark and light,
      // rather than one tinted section with six near-identical card rows in
      // it. The old version could not alternate: everything lived inside a
      // single <section class="pe"> that owned the background, so every
      // section arrived with the same weight and the eye had nowhere to land.
      //
      // The demo's markup is untouched — app.js binds to #pe-run, #pe-url,
      // #pe-card and the ids inside it. Only its surroundings changed. ?>
<?php require APP_ROOT . '/views/_icons.php'; ?>

<?php // Live figures. A number that is really zero is left out rather than
      // printed as "0 businesses listed", which would be the single most
      // discouraging thing on a page asking somebody to join.
$hiwStats = [];
$hiwLive  = (int)scalar('SELECT COUNT(*) FROM businesses WHERE status = "live"');
if ($hiwLive > 0) $hiwStats[] = [number_format($hiwLive), 'businesses listed'];
$hiwStats[] = [number_format((int)scalar('SELECT COUNT(*) FROM cities')),     'cities covered'];
$hiwStats[] = [number_format((int)scalar('SELECT COUNT(*) FROM countries')),  'countries'];
$hiwStats[] = [number_format((int)scalar('SELECT COUNT(*) FROM categories')), 'categories and trades'];
$hiwStats[] = ['$0', 'to be in all of it'];
?>

<?php // ---- 1. the hook, on the photo -------------------------------- ?>
<section class="hiw-hero">
  <div class="wrap">
    <nav class="crumbs crumbs-light"><a href="/">Home</a><span>&rsaquo;</span>How it Works</nav>
    <div class="pe-head">
      <span class="pe-eyebrow">Why a listing matters</span>
      <h1 class="pe-title">People stopped searching.<br><span>They started asking.</span></h1>
      <p class="pe-lead">Your customers used to type a search and scan ten links. Now they ask ChatGPT,
        Perplexity, Gemini or Claude who to hire — and get one answer, or three. A listing is how those
        answers find you, and how Google and Bing keep finding you too. It is free to join.</p>
    </div>
    <div class="hiw-engines">
      <?php foreach ([
          ['sr', 'Google'], ['find', 'Bing'], ['sp', 'ChatGPT'],
          ['pp', 'Perplexity'], ['gm', 'Gemini'], ['cl', 'Claude'],
      ] as [$hiwI, $hiwL]): ?>
        <span class="hiw-engine"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-<?= $hiwI ?>"/></svg><?= e($hiwL) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php // ---- 2. the argument, on white ------------------------------- ?>
<section class="hiw-b hiw-b-white">
  <div class="wrap">
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
  </div>
</section>

<?php // ---- 3. the proof, dark and numeric --------------------------- ?>
<section class="hiw-stats">
  <div class="wrap">
    <ul>
      <?php foreach ($hiwStats as [$hiwN, $hiwL]): ?>
        <li><b><?= e($hiwN) ?></b><span><?= e($hiwL) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<?php // ---- 4. what the listing is worth ----------------------------- ?>
<section class="hiw-b">
  <div class="wrap">
    <?php // What the listing actually gets you, as opposed to why listings
          // matter — that argument is above. Token figures come from
          // token_rules() so this cannot promise an allowance the ledger does
          // not grant.
    $hiwTok = token_rules('free');
    ?>
    <section class="hiw-block" aria-labelledby="hiw-listing-h">
      <div class="hiw-block-head">
        <span class="hiw-free">Free forever</span>
        <h2 id="hiw-listing-h">Your listing is a page, not a line in a list</h2>
        <p>Most directories give you a row in a table. Yours is a page of its own, written to be read
          by people and quoted by machines — and it stays free.</p>
      </div>
    
      <div class="hiw-value">
        <div class="hiw-v">
          <span class="hiw-v-ico hiw-v-blue"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-blog"/></svg></span>
          <h3>A page with your own address</h3>
          <p>Your business, your city, your trade — at a URL you can hand out, with your description,
            services, category and a link straight to your website.</p>
        </div>
        <div class="hiw-v">
          <span class="hiw-v-ico hiw-v-green"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-find"/></svg></span>
          <h3>Where people are already looking</h3>
          <p>On the city page, the category page and the search results — the pages somebody lands on
            when they type "plumber in Phoenix" instead of a company name.</p>
        </div>
        <div class="hiw-v">
          <span class="hiw-v-ico hiw-v-violet"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-spark"/></svg></span>
          <h3><?= number_format((int)$hiwTok['grant']) ?> tokens a month to promote it</h3>
          <p>Spend them putting your links in front of the member network, up to
            <?= (int)$hiwTok['promos_max'] ?> promotions a month, and earn more back by opening other
            members' links.</p>
        </div>
        <div class="hiw-v">
          <span class="hiw-v-ico hiw-v-amber"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-ok"/></svg></span>
          <h3>Read by a person first</h3>
          <p>Nothing goes live unchecked. That is slower than the directories that publish anything,
            and it is the reason being in this one is worth something.</p>
        </div>
      </div>
    </section>
  </div>
</section>

<?php // ---- 5. the engine, dark so the demo reads as a product ------- ?>
<section class="pe hiw-ondark" id="engine">
  <div class="hiw-ondark-glow" aria-hidden="true"></div>
  <div class="wrap pe-inner">
    <section class="hiw-block-head hiw-engine-head" aria-hidden="false">
      <span class="hiw-free">Included free</span>
      <h2>Then the engine goes and promotes it</h2>
      <p>A listing that sits there is half the job. Paste a link to anything you publish and the
        engine writes the pitch, files it, and pushes it out — the same four steps every time.</p>
    </section>
    <div class="pe-demo">
      <div class="pe-try">
        <span class="pe-kicker">See it work</span>
        <h3>Watch it write one</h3>
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
  </div>
</section>

<?php // ---- 6. the free tools, on white ------------------------------ ?>
<section class="hiw-b hiw-b-white">
  <div class="wrap">
    <?php // The three sister tools, one block each. Name, icon and address come
          // from toutify_products(), the list that also feeds the homepage
          // strip and the band above the footer, so a corrected address is
          // corrected everywhere. The value copy is local: these blocks argue
          // what each tool is worth, which the other two places do not.
    $hiwAll = [];
    foreach (toutify_products() as $hiwP) $hiwAll[$hiwP['key']] = $hiwP;
    $hiwBlocks = [
      [
        'key'   => 'contentvendor',
        'tone'  => 'green',
        'head'  => 'The posts you never get round to writing',
        'lead'  => 'Being findable is a habit, not a one-off. ContentVendor writes the posts, pages and
                    updates that keep your business turning up — on Google, in AI search, and on the
                    social accounts you have been meaning to feed since March.',
        'ticks' => ['Posts and pages written for you, on a schedule',
                    'Published to your social channels, not just drafted',
                    'Every piece is one more page an assistant can quote'],
        'demo'  => 'content',
      ],
      [
        'key'   => 'promomonster',
        'tone'  => 'amber',
        'head'  => 'The reviews nobody asks for',
        'lead'  => 'Most happy customers would leave a review if asked, and almost nobody asks.
                    PromoMonster asks every one of them, puts your replies where people can see them,
                    and shows the result off on your listing.',
        'ticks' => ['Asks every customer, automatically',
                    'Reply once, and the reply shows with the review',
                    'Ratings are what a buyer checks, and what an assistant cites'],
        'demo'  => 'review',
      ],
      [
        'key'   => 'leadcrazy',
        'tone'  => 'violet',
        'head'  => 'A visitor who leaves is gone',
        'lead'  => 'Most people who find you never call. The Million Dollar Lead Form asks the two or
                    three questions that turn a browser into a quote request, on your own site, and
                    drops the answer in your inbox.',
        'ticks' => ['Turns visitors into quote requests',
                    'Works on your site, not just your listing',
                    'Every enquiry lands in one place'],
        'demo'  => 'lead',
      ],
    ];
    ?>
    <section class="hiw-block" aria-labelledby="hiw-tools-h">
      <div class="hiw-block-head">
        <span class="hiw-free">All free</span>
        <h2 id="hiw-tools-h">One account. Three more tools, at no cost.</h2>
        <p>The same free <?= e(trim(setting('site_byline', 'Toutify')) ?: 'Toutify') ?> account that
          carries your listing carries these. No credit card on any of them.</p>
      </div>
    
      <?php foreach ($hiwBlocks as $hiwI => $hiwB): ?>
        <?php
          $hiwD = $hiwAll[$hiwB['key']] ?? null;
          if (!$hiwD) continue;
          $hiwHasUrl = $hiwD['url'] !== '';
        ?>
        <div class="hiw-tool-row hiw-<?= e($hiwB['tone']) ?><?= $hiwI % 2 ? ' hiw-flip' : '' ?>">
          <div class="hiw-tool-copy">
            <div class="hiw-tool-brand">
              <span class="hiw-tool-ico"><?= toutify_icon($hiwD['icon']) ?></span>
              <b><?= e($hiwD['name']) ?></b>
              <span class="hiw-pill">Free</span>
            </div>
            <h3><?= e($hiwB['head']) ?></h3>
            <p><?= e(preg_replace('/\s+/', ' ', $hiwB['lead'])) ?></p>
            <ul class="hiw-ticks">
              <?php foreach ($hiwB['ticks'] as $hiwT): ?><li><?= e($hiwT) ?></li><?php endforeach; ?>
            </ul>
            <?php if ($hiwHasUrl): ?>
              <a class="hiw-tool-link" href="<?= e($hiwD['url']) ?>" rel="noopener">Open <?= e($hiwD['name']) ?> &rarr;</a>
            <?php endif; ?>
          </div>
    
          <?php // A small picture of the thing, drawn in CSS. Not a screenshot:
                // these are other people's products and a mock-up that claimed
                // to be their interface would be a lie about software I have
                // never seen. These say what the tool produces, nothing more. ?>
          <div class="hiw-tool-art" aria-hidden="true">
            <?php if ($hiwB['demo'] === 'content'): ?>
              <div class="hiw-mini hiw-mini-post">
                <span class="hiw-mini-tag">Scheduled · Tuesday</span>
                <b>5 signs your boiler is about to go</b>
                <i></i><i></i><i class="short"></i>
                <div class="hiw-mini-chips"><span>Facebook</span><span>Instagram</span><span>Blog</span></div>
              </div>
            <?php elseif ($hiwB['demo'] === 'review'): ?>
              <div class="hiw-mini hiw-mini-review">
                <span class="hiw-stars">★★★★★</span>
                <b>“Turned up when they said they would.”</b>
                <span class="hiw-mini-by">Dana R. · verified customer</span>
                <div class="hiw-mini-reply"><span>Your reply</span><i></i><i class="short"></i></div>
              </div>
            <?php else: ?>
              <div class="hiw-mini hiw-mini-form">
                <span class="hiw-mini-tag">Million Dollar Lead Form</span>
                <label>What do you need?<i></i></label>
                <label>When?<i class="short"></i></label>
                <span class="hiw-mini-btn">Get my quote</span>
                <span class="hiw-mini-note">New enquiry → your inbox</span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </section>
  </div>
</section>

<?php // ---- 7. the ask ----------------------------------------------- ?>
<section class="hiw-close">
  <div class="wrap">
    <div class="pe-close">
      <a class="btn btn-primary pe-btn" href="/signup">Add my business free</a>
      <a class="pe-see" href="/promotions">See live member promotions →</a>
      <p class="pe-note">Free forever. No card, no contract. Add your business, drop in your first link,
        and the engine starts working today.</p>
    </div>
  </div>
</section>
