<?php require __DIR__ . '/_icons.php'; ?>
<div class="wrap">
  <section class="section">
    <div class="pf-head">
      <span class="pe-eyebrow">AI Promotion Engine</span>
      <h1>Live member promotions</h1>
      <p class="mute">Everything below was published by a <?= e($site) ?> member somewhere else —
        their blog, their store, their channel. Open one, give it a genuine look, and help another
        small business get seen. <a href="/signup" class="pf-link">Add yours free →</a></p>
    </div>

    <div class="pf-filters">
      <a class="chip<?= $channel === '' ? ' on' : '' ?>" href="/promotions">All<?= $total ? ' (' . $total . ')' : '' ?></a>
      <?php foreach (promo_channels() as $key => [$chLabel, $chIcon]): ?>
        <?php if (!$counts[$key]) continue; ?>
        <a class="chip<?= $channel === $key ? ' on' : '' ?>" href="/promotions?channel=<?= e($key) ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-<?= e($chIcon) ?>"/></svg>
          <?= e($chLabel) ?> (<?= (int)$counts[$key] ?>)
        </a>
      <?php endforeach; ?>
    </div>

    <?php if (!$list): ?>
      <div class="card card-pad pf-empty">
        <h3><?= $channel === '' ? 'The feed is just getting started.' : 'Nothing here yet.' ?></h3>
        <p class="mute">Member promotions appear here the moment our team approves them.
          Be one of the first — add your business free, drop in a link you've already published,
          and the network sees it.</p>
        <a class="btn btn-primary" href="/signup">Promote my business — free</a>
        <?php if ($channel !== ''): ?>
          <a class="btn btn-ghost" href="/promotions">See every channel</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="pf-grid">
        <?php foreach ($list as $p): ?>
          <article class="card pf-card">
            <div class="pf-card-head">
              <span class="pf-chan">
                <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#ml-<?= e(promo_channel_icon($p['channel'])) ?>"/></svg>
                <?= e(promo_channel_label($p['channel'])) ?>
              </span>
              <span class="faint pf-when"><?= e(date('M j', strtotime($p['created_at']))) ?></span>
            </div>

            <h3 class="pf-title">
              <a href="/promotions/go/<?= (int)$p['id'] ?>" target="_blank" rel="noopener nofollow"><?= e($p['title']) ?></a>
            </h3>
            <?php if ($p['blurb']): ?><p class="pf-blurb mute"><?= e($p['blurb']) ?></p><?php endif; ?>

            <div class="pf-by">
              <span class="avatar pf-avatar"><?php if ($logo = listing_logo($p)): ?><img src="<?= e($logo) ?>" alt=""><?php else: ?><?= e(mb_substr($p['business_name'], 0, 1)) ?><?php endif; ?></span>
              <span class="pf-by-text">
                <b><?= e($p['business_name']) ?><?php if ($p['verified']): ?> <span class="badge badge-verified">Verified</span><?php endif; ?></b>
                <small><?= e($p['category_label'] ?? 'Local business') ?><?= $p['city_name'] ? ' · ' . e($p['city_name']) : '' ?></small>
              </span>
            </div>

            <a class="btn btn-primary btn-block pf-open" href="/promotions/go/<?= (int)$p['id'] ?>"
               target="_blank" rel="noopener nofollow">Open &amp; support</a>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if ($pages > 1): ?>
        <nav class="pager">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?php $q = '/promotions?' . http_build_query(array_filter(['channel' => $channel, 'page' => $i > 1 ? $i : null])); ?>
            <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
            <?php else: ?><a href="<?= e($q) ?>"><?= $i ?></a><?php endif; ?>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>
