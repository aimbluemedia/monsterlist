<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <h1>Hi, <?= e($u['name']) ?></h1>
    <p class="mute">You're on the <strong><?= e($plan['label']) ?></strong> plan
      (<?= count($listings) ?> listing<?= count($listings) === 1 ? '' : 's' ?><?= plan_listing_limit($plan) ? ' of ' . plan_listing_limit($plan) : '' ?>).
      <?php // Straight to the upgrade page for the listing they own, when they
            // own one — a member with a business already has the thing the
            // upgrade applies to, and the price list would only ask them again. ?>
      <?php if ($u['plan'] !== 'featured'): ?>
        <a href="<?= $listings ? '/members/listings/upgrade?id=' . (int)$listings[0]['id'] : '/pricing' ?>"
           style="color:var(--accent);font-weight:700">Upgrade →</a>
      <?php endif; ?>
    </p>
    <div class="stat-row">
      <div class="card stat"><div class="n"><?= count($listings) ?></div><div class="l">Listings</div></div>
      <div class="card stat"><div class="n"><?= count(array_filter($listings, fn($l) => $l['status'] === 'live')) ?></div><div class="l">Live</div></div>
      <div class="card stat"><div class="n"><?= $plan['analytics'] ? (int)$stats['views'] : '—' ?></div><div class="l">Views (30d)</div></div>
      <div class="card stat"><div class="n"><?= $plan['analytics'] ? (int)$stats['clicks'] : '—' ?></div><div class="l">Website clicks (30d)</div></div>
    </div>
      <?php // The sister tools and whether this member has them active.
            //
            // The state is read here, not set: Toutify owns it. What Toutify
            // does not yet do is tell us — no claim and no webhook event
            // carries it — so until that changes the flags are set by staff,
            // and a card that is off links out to go and activate it.
            $dtTools = toutify_tools_for($u);
            $dtAll   = [];
            foreach (toutify_products() as $dtP) $dtAll[$dtP['key']] = $dtP;
            $dtBy    = trim(setting('site_byline', 'Toutify')) ?: 'Toutify';
      ?>
      <?php if (toutify_tools_ready()): ?>
      <div class="card card-pad dt-card">
        <div class="dt-head">
          <h3>Your <?= e($dtBy) ?> tools</h3>
          <span>One account covers all of them. Switching one on happens on <?= e($dtBy) ?>.</span>
        </div>
        <div class="dt-grid">
          <?php foreach (toutify_tool_keys() as $dtKey): ?>
            <?php
              $dtD  = $dtAll[$dtKey] ?? null;
              if (!$dtD) continue;
              $dtOn = !empty($dtTools[$dtKey]);
            ?>
            <div class="dt-tool<?= $dtOn ? ' dt-on' : '' ?>">
              <span class="dt-ico"><?= toutify_icon($dtD['icon']) ?></span>
              <b><?= e($dtD['name']) ?></b>
              <em><?= e($dtD['short'] ?? '') ?></em>
              <span class="dt-state"><i></i><?= $dtOn ? 'Active' : 'Not active' ?></span>
              <?php if (!$dtOn && $dtD['url'] !== ''): ?>
                <a class="dt-go" href="<?= e($dtD['url']) ?>" rel="noopener">Activate &rarr;</a>
              <?php elseif (!$dtOn): ?>
                <span class="dt-go dt-go-off">Ask us to switch it on</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    <div class="card card-pad">
      <h3>Recent listings</h3>
      <?php if (!$listings): ?>
        <p class="mute">No listings yet.</p>
        <a class="btn btn-primary" href="/members/listings/new">Create your first listing</a>
      <?php else: ?>
        <table class="table">
          <tr><th>Business</th><th>City</th><th>Status</th><th></th></tr>
          <?php foreach (array_slice($listings, 0, 5) as $l): ?>
            <tr>
              <td><strong><?= e($l['name']) ?></strong></td>
              <td><?= e($l['city_name'] ?? '—') ?></td>
              <td><span class="badge badge-<?= e($l['status']) ?>"><?= e($l['status']) ?></span></td>
              <td><a href="/members/listings/edit?id=<?= (int)$l['id'] ?>" style="color:var(--accent);font-weight:700">Edit</a></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
