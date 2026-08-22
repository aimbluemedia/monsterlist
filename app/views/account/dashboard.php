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
        <a href="<?= $listings ? '/account/listings/upgrade?id=' . (int)$listings[0]['id'] : '/pricing' ?>"
           style="color:var(--accent);font-weight:700">Upgrade →</a>
      <?php endif; ?>
    </p>
    <div class="stat-row">
      <div class="card stat"><div class="n"><?= count($listings) ?></div><div class="l">Listings</div></div>
      <div class="card stat"><div class="n"><?= count(array_filter($listings, fn($l) => $l['status'] === 'live')) ?></div><div class="l">Live</div></div>
      <div class="card stat"><div class="n"><?= $plan['analytics'] ? (int)$stats['views'] : '—' ?></div><div class="l">Views (30d)</div></div>
      <div class="card stat"><div class="n"><?= $plan['analytics'] ? (int)$stats['clicks'] : '—' ?></div><div class="l">Website clicks (30d)</div></div>
    </div>
    <div class="card card-pad">
      <h3>Recent listings</h3>
      <?php if (!$listings): ?>
        <p class="mute">No listings yet.</p>
        <a class="btn btn-primary" href="/account/listings/new">Create your first listing</a>
      <?php else: ?>
        <table class="table">
          <tr><th>Business</th><th>City</th><th>Status</th><th></th></tr>
          <?php foreach (array_slice($listings, 0, 5) as $l): ?>
            <tr>
              <td><strong><?= e($l['name']) ?></strong></td>
              <td><?= e($l['city_name'] ?? '—') ?></td>
              <td><span class="badge badge-<?= e($l['status']) ?>"><?= e($l['status']) ?></span></td>
              <td><a href="/account/listings/edit?id=<?= (int)$l['id'] ?>" style="color:var(--accent);font-weight:700">Edit</a></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
