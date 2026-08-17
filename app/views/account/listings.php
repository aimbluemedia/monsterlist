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
      <?php // City is on the listing itself and on its storefront; repeating it
            // here bought a whole column for a fact nobody comes to this page
            // to look up. Dropping it lets the table fit a phone unscrolled. ?>
      <div class="card card-pad table-wrap table-narrow">
        <table class="table ls-table">
          <tr><th>Business</th><th>Category</th><th>Status</th><th>Tier</th><th>Manage</th></tr>
          <?php foreach ($listings as $l): ?>
            <tr>
              <td><strong><?= e($l['name']) ?></strong></td>
              <td><?= e($l['category_label'] ?? '—') ?></td>
              <td><span class="badge badge-<?= e($l['status']) ?>"><?= e($l['status']) ?></span></td>
              <td><?= e(ucfirst($l['tier'])) ?></td>
              <td>
                <?php // Six actions on one line was a run of dot-separated links
                      // that read as a sentence and clipped on a narrow screen.
                      // Three to a row, two rows, each one a button you can hit. ?>
                <div class="ls-actions">
                  <?php if ($l['status'] === 'live' && $l['city_id']): ?>
                    <a class="btn btn-sm btn-ghost" href="<?= e(business_path($l)) ?>">View</a>
                  <?php endif; ?>
                  <a class="btn btn-sm btn-ghost" href="/account/listings/edit?id=<?= (int)$l['id'] ?>">Edit</a>
                  <a class="btn btn-sm btn-ghost" href="/account/listings/services?id=<?= (int)$l['id'] ?>"
                     title="Services, social links and review profiles">Profile</a>
                  <?php if (tokens_ready()): ?>
                    <a class="btn btn-sm btn-ghost" href="/account/tokens">Tokens</a>
                  <?php endif; ?>
                  <?php // Offered per row, so the upgrade a member starts is tied to
                        // the listing they were looking at when they decided to. ?>
                  <?php if ($u['plan'] !== 'featured'): ?>
                    <a class="btn btn-sm ls-up" href="/account/listings/upgrade?id=<?= (int)$l['id'] ?>">Upgrade</a>
                  <?php endif; ?>
                  <form method="post" action="/account/listings/delete"><?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                    <button class="btn btn-sm ls-del" data-confirm="Delete this listing permanently?">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
