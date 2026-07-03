<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <div class="section-head">
      <h1>My listings</h1>
      <a class="btn btn-primary" href="/account/listings/new">+ New listing</a>
    </div>
    <p class="mute"><?= count($listings) ?>/<?= (int)$plan['max_listings'] ?> listings used on the <?= e($plan['label']) ?> plan.</p>
    <?php if (!$listings): ?>
      <div class="card card-pad">No listings yet — <a href="/account/listings/new" style="color:var(--accent);font-weight:700">create one now</a>.</div>
    <?php else: ?>
      <div class="card card-pad">
        <table class="table">
          <tr><th>Business</th><th>Category</th><th>City</th><th>Status</th><th>Tier</th><th></th></tr>
          <?php foreach ($listings as $l): ?>
            <tr>
              <td><strong><?= e($l['name']) ?></strong></td>
              <td><?= e($l['category_label'] ?? '—') ?></td>
              <td><?= e($l['city_name'] ?? '—') ?></td>
              <td><span class="badge badge-<?= e($l['status']) ?>"><?= e($l['status']) ?></span></td>
              <td><?= e(ucfirst($l['tier'])) ?></td>
              <td style="white-space:nowrap">
                <?php if ($l['status'] === 'live' && $l['city_id']): ?>
                  <a href="<?= e(business_path($l)) ?>" style="color:var(--accent);font-weight:700">View</a> ·
                <?php endif; ?>
                <a href="/account/listings/edit?id=<?= (int)$l['id'] ?>" style="color:var(--accent);font-weight:700">Edit</a> ·
                <form method="post" action="/account/listings/delete" style="display:inline"><?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                  <button class="btn btn-sm" style="background:none;border:none;color:var(--red);font-weight:700;cursor:pointer;padding:0" data-confirm="Delete this listing permanently?">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
