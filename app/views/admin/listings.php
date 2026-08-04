<?php require __DIR__ . '/_top.php'; ?>
<div class="section-head"><h1>Listings</h1></div>

<div class="admin-filters">
  <p class="admin-tabs">
    <?php foreach (['pending','live','rejected','all'] as $s): ?>
      <a class="chip" style="<?= $s === $status ? 'border-color:var(--accent);color:var(--accent)' : '' ?>" href="<?= e(listings_url($s, $term)) ?>"><?= ucfirst($s) ?></a>
    <?php endforeach; ?>
  </p>
  <form class="admin-search" method="get" action="/superadmin/listings">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <input type="text" name="q" value="<?= e($term) ?>" placeholder="Search email or domain…"
           aria-label="Search listings by email or domain" autocomplete="off">
    <button class="btn btn-sm btn-primary">Search</button>
    <?php if ($term !== ''): ?><a class="btn btn-sm btn-ghost" href="<?= e(listings_url($status)) ?>">Clear</a><?php endif; ?>
  </form>
</div>

<?php if ($term !== ''): ?>
  <p class="mute" style="margin:-4px 0 14px">
    <?= (int)$total ?> listing<?= $total === 1 ? '' : 's' ?> matching “<?= e($term) ?>”
    in <?= e($status) ?> — searches the owner’s email, the listing’s contact email and its website.
  </p>
<?php endif; ?>

<div class="card card-pad">
  <?php if (!$list): ?>
    <?php if ($term !== ''): ?>
      <p class="mute">Nothing in “<?= e($status) ?>” matches “<?= e($term) ?>”.
        Try the <a href="<?= e(listings_url('all', $term)) ?>">All</a> tab, or <a href="<?= e(listings_url($status)) ?>">clear the search</a>.</p>
    <?php else: ?>
      <p class="mute">No listings with status “<?= e($status) ?>”.</p>
    <?php endif; ?>
  <?php else: ?>
  <div class="table-wrap">
  <table class="table">
    <tr><th>Business</th><th>Category</th><th>City</th><th>Owner</th><th>Website</th><th>Tier</th><th>Status</th><th></th></tr>
    <?php foreach ($list as $b): ?>
      <tr>
        <td><strong><?= e($b['name']) ?></strong><?php if ($b['verified']): ?> <span class="badge badge-verified">✓</span><?php endif; ?></td>
        <td><?= e($b['category_label'] ?? '—') ?></td>
        <td><?= e($b['city_name'] ?? '—') ?></td>
        <td>
          <?= e($b['owner_email'] ?? 'unclaimed') ?>
          <?php // The listing's own contact email is a separate field, and often the one being searched for.
                if (!empty($b['email']) && strcasecmp((string)$b['email'], (string)($b['owner_email'] ?? '')) !== 0): ?>
            <br><small class="faint"><?= e($b['email']) ?></small>
          <?php endif; ?>
        </td>
        <td><?php if (!empty($b['website'])): ?>
          <?php $host = preg_replace('#^(?:[a-z][a-z0-9+.-]*://)?(?:www\.)?#i', '', rtrim((string)$b['website'], '/')); ?>
          <small class="trunc" title="<?= e($b['website']) ?>"><?= e($host) ?></small>
        <?php else: ?>—<?php endif; ?></td>
        <td><?= e(ucfirst($b['tier'])) ?></td>
        <td><span class="badge badge-<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
        <td style="white-space:nowrap">
          <a class="btn btn-sm btn-ghost" href="/superadmin/listings/edit?id=<?= (int)$b['id'] ?>&back=<?= e($status) ?>&q=<?= e($term) ?>">Edit</a>
          <?php if ($b['status'] !== 'live'): ?>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="back" value="<?= e($status) ?>"><input type="hidden" name="q" value="<?= e($term) ?>"><button class="btn btn-sm btn-primary">Approve</button></form>
          <?php endif; ?>
          <?php if ($b['status'] !== 'rejected'): ?>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="reject"><input type="hidden" name="back" value="<?= e($status) ?>"><input type="hidden" name="q" value="<?= e($term) ?>"><button class="btn btn-sm btn-ghost">Reject</button></form>
          <?php endif; ?>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="verify"><input type="hidden" name="back" value="<?= e($status) ?>"><input type="hidden" name="q" value="<?= e($term) ?>"><button class="btn btn-sm btn-ghost">✓ Verify</button></form>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="back" value="<?= e($status) ?>"><input type="hidden" name="q" value="<?= e($term) ?>"><button class="btn btn-sm btn-danger" data-confirm="Delete this listing permanently?">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <?php if (count($list) === 30): ?><nav class="pager"><a href="<?= e(listings_url($status, $term, $page + 1)) ?>">Next →</a></nav><?php endif; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
