<?php require __DIR__ . '/_top.php'; ?>
<div class="section-head">
  <h1>Diagnostics</h1>
</div>

<div class="card card-pad ed-card">
  <h3>This build</h3>
  <p class="mute ed-note">If a change you uploaded isn't showing, check this first — an old
    number here means the upload didn't land where the site is reading from.</p>
  <div class="info-row"><span class="mute">Build</span><span><strong><?= e(defined('ML_BUILD') ? ML_BUILD : 'unknown') ?></strong></span></div>
  <div class="info-row"><span class="mute">PHP</span><span><?= e(PHP_VERSION) ?></span></div>
  <div class="info-row"><span class="mute">Site URL</span><span><?= e(site_url()) ?></span></div>
</div>

<div class="card card-pad ed-card">
  <h3>Database upgrades</h3>
  <p class="mute ed-note">Uploading a release does not change the database. If anything below is
    missing, open phpMyAdmin → your database → SQL tab and run
    <code>database/upgrade-all.sql</code> from the release zip. It skips whatever is already
    there, so it is safe to run at any time, and it never drops anything.</p>

  <?php if ($schemaOk): ?>
    <p class="flash flash-success" style="margin:0">Every upgrade has been applied. The database matches this build.</p>
  <?php else: ?>
    <p class="flash flash-error" style="margin:0 0 12px">Something is missing below. Until it is imported,
      the feature it belongs to cannot work no matter what the files say.</p>
  <?php endif; ?>

  <div class="table-wrap">
    <table class="table">
      <tr><th></th><th>Needs</th><th>What it is for</th><th>Fix</th></tr>
      <?php foreach ($checks as $c): ?>
        <tr>
          <td style="font-size:1.1rem"><?= $c['ok'] ? '✅' : '❌' ?></td>
          <td><code><?= e($c['label']) ?></code></td>
          <td class="mute"><?= e($c['detail']) ?></td>
          <td><?= $c['ok'] ? '<span class="mute">—</span>' : '<strong>' . e($c['fix']) . '</strong>' ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<div class="card card-pad ed-card">
  <h3>Stripe payments</h3>
  <p class="mute ed-note">Nobody reports a broken checkout — they close the tab. Everything the
    Upgrade buttons need is checked here, including the two price IDs, which are looked up in
    Stripe rather than just read back from Settings.</p>

  <?php if ($stripe['ready']): ?>
    <p class="flash flash-success" style="margin:0 0 12px">Stripe is set up and
      <?= $stripe['mode'] === 'live' ? 'taking real payments' : 'in test mode — cards are not charged' ?>.</p>
  <?php elseif ($stripe['mode'] === ''): ?>
    <p class="flash flash-error" style="margin:0 0 12px">No secret key, so every Upgrade button
      shows “Payments are not configured yet”. Add your keys to <code>app/config.php</code>.</p>
  <?php else: ?>
    <p class="flash flash-error" style="margin:0 0 12px">Something below is not finished. Until it is,
      a member who presses Upgrade will not be able to pay.</p>
  <?php endif; ?>

  <div class="table-wrap table-narrow">
    <table class="table">
      <tr><th></th><th>Needs</th><th>What is there</th><th>Fix</th></tr>
      <tr>
        <td style="font-size:1.1rem"><?= $stripe['mode'] !== '' ? '✅' : '❌' ?></td>
        <td><code>secret_key</code></td>
        <td class="mute"><?= $stripe['mode'] === '' ? 'not set'
              : ($stripe['mode'] === 'live' ? 'live key — real money' : 'test key — no money moves') ?></td>
        <td><?= $stripe['mode'] !== '' ? '<span class="mute">—</span>'
              : '<strong>Paste sk_… into app/config.php</strong>' ?></td>
      </tr>
      <?php foreach (['pro' => 'Pro', 'featured' => 'Featured'] as $k => $label): ?>
        <?php $p = $stripe['prices'][$k]; ?>
        <tr>
          <td style="font-size:1.1rem"><?= $p['ok'] && $p['recurring'] && $p['active'] ? '✅' : '❌' ?></td>
          <td><code><?= e($label) ?> price</code></td>
          <td class="mute">
            <?php if ($p['id'] === ''): ?>not set
            <?php elseif (!$p['ok']): ?>
              <code><?= e($p['id']) ?></code> — Stripe does not recognise this
            <?php else: ?>
              <?= e($p['label']) ?>
              <?php if (!$p['recurring']): ?> — one-off, not a subscription<?php endif; ?>
              <?php if (!$p['active']): ?> — archived in Stripe<?php endif; ?>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($p['id'] === ''): ?><strong>Settings → <?= e($label) ?> price ID</strong>
            <?php elseif (!$p['ok']): ?><strong>Copy the ID that starts <code>price_</code>, not <code>prod_</code></strong>
            <?php elseif (!$p['recurring']): ?><strong>Create it as a recurring monthly price</strong>
            <?php else: ?><span class="mute">—</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <tr>
        <td style="font-size:1.1rem"><?= $stripe['webhook'] ? '✅' : '❌' ?></td>
        <td><code>webhook_secret</code></td>
        <td class="mute"><?= $stripe['webhook'] ? 'set — renewals and cancellations will be recorded'
              : 'not set — upgrades still work, but a cancellation will never reach the site' ?></td>
        <td><?= $stripe['webhook'] ? '<span class="mute">—</span>'
              : '<strong>Add whsec_… to app/config.php</strong>' ?></td>
      </tr>
      <?php if ($stripe['mismatch']): ?>
        <tr>
          <td style="font-size:1.1rem">❌</td>
          <td><code>key vs price</code></td>
          <td class="mute">A <?= $stripe['mode'] === 'live' ? 'test' : 'live' ?>-mode price is configured
            under a <?= e($stripe['mode']) ?> key. Both halves are real; they are from different Stripes.</td>
          <td><strong>Copy the price IDs again with the same Test/Live toggle as your key</strong></td>
        </tr>
      <?php endif; ?>
    </table>
  </div>

  <p class="mute ed-note" style="margin-top:12px">
    Webhook endpoint to paste into Stripe → Developers → Webhooks:<br>
    <code><?= e($stripe['url']) ?></code><br>
    Send it <code>checkout.session.completed</code>, <code>customer.subscription.updated</code>
    and <code>customer.subscription.deleted</code>.
  </p>
</div>

<div class="card card-pad ed-card">
  <h3>Listing inspector</h3>
  <p class="mute ed-note">Type a business name or domain. This shows, card by card, whether the
    storefront will render it — and when it won't, whether that is because nothing is stored or
    because a rule hides it.</p>
  <form method="get" class="admin-search" style="margin-bottom:6px">
    <input type="text" name="listing" value="<?= e($probe) ?>" placeholder="e.g. ibzzz or ibzzz.com">
    <button class="btn btn-primary">Inspect</button>
  </form>

  <?php if ($probe !== '' && !$found && !$matches): ?>
    <p class="flash flash-error" style="margin:10px 0 0">No listing matches “<?= e($probe) ?>”.</p>
  <?php elseif ($matches): ?>
    <p class="mute" style="margin:10px 0 6px"><?= count($matches) ?> listings match “<?= e($probe) ?>”. Pick the one you mean —
      several can share a name, and inspecting the wrong one tells you nothing.</p>
    <div class="table-wrap">
      <table class="table">
        <tr><th>id</th><th>Name</th><th>Website</th><th>Tier</th><th>Status</th><th></th></tr>
        <?php foreach ($matches as $m): ?>
          <tr>
            <td class="faint"><?= (int)$m['id'] ?></td>
            <td><strong><?= e($m['name']) ?></strong></td>
            <td class="mute" style="word-break:break-all"><?= e($m['website']) ?></td>
            <td><?= e($m['tier']) ?></td>
            <td><?= e($m['status']) ?></td>
            <td><a class="btn btn-ghost btn-sm" href="/superadmin/diagnostics?listing=<?= (int)$m['id'] ?>">Inspect</a></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php elseif ($found): ?>
    <div class="info-row"><span class="mute">Listing</span><span><strong><?= e($found['name']) ?></strong> (id <?= (int)$found['id'] ?>)</span></div>
    <div class="info-row"><span class="mute">Tier / status</span><span><?= e($found['tier']) ?> · <?= e($found['status']) ?></span></div>
    <div class="table-wrap" style="margin-top:10px">
      <table class="table">
        <tr><th></th><th>Storefront card</th><th>Why</th><th>Stored value</th></tr>
        <?php foreach ($cards as $c): ?>
          <tr>
            <td style="font-size:1.1rem"><?= $c['shown'] ? '✅' : '—' ?></td>
            <td><strong><?= e($c['name']) ?></strong></td>
            <td class="mute"><?= e($c['why']) ?></td>
            <td class="faint" style="word-break:break-all;font-size:.8rem"><?= $c['value'] === '' ? '' : e(mb_substr($c['value'], 0, 300)) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="card card-pad ed-card">
  <h3>What listings actually hold</h3>
  <p class="mute ed-note">A storefront card only appears when there is something to put in it.
    A zero here means no listing has ever saved that, which is a data question, not a layout one.</p>
  <div class="info-row"><span class="mute">Live listings</span><span><?= (int)$stats['live'] ?></span></div>
  <div class="info-row"><span class="mute">With social profiles</span><span><?= (int)$stats['social'] ?></span></div>
  <div class="info-row"><span class="mute">With review-site links</span><span><?= $stats['reviews'] === null ? '<em class="mute">column missing — see above</em>' : (int)$stats['reviews'] ?></span></div>
  <div class="info-row"><span class="mute">With a logo</span><span><?= (int)$stats['logos'] ?></span></div>
  <div class="info-row"><span class="mute">With services</span><span><?= (int)$stats['services'] ?></span></div>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
