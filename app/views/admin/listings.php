<?php require __DIR__ . '/_top.php'; ?>
<div class="section-head"><h1>Listings</h1></div>
<p>
  <?php foreach (['pending','live','rejected','all'] as $s): ?>
    <a class="chip" style="<?= $s === $status ? 'border-color:var(--accent);color:var(--accent)' : '' ?>" href="/superadmin/listings?status=<?= $s ?>"><?= ucfirst($s) ?></a>
  <?php endforeach; ?>
</p>
<div class="card card-pad">
  <?php if (!$list): ?><p class="mute">No listings with status “<?= e($status) ?>”.</p>
  <?php else: ?>
  <table class="table">
    <tr><th>Business</th><th>Category</th><th>City</th><th>Owner</th><th>Tier</th><th>Status</th><th></th></tr>
    <?php foreach ($list as $b): ?>
      <tr>
        <td><strong><?= e($b['name']) ?></strong><?php if ($b['verified']): ?> <span class="badge badge-verified">✓</span><?php endif; ?></td>
        <td><?= e($b['category_label'] ?? '—') ?></td>
        <td><?= e($b['city_name'] ?? '—') ?></td>
        <td><?= e($b['owner_email'] ?? 'unclaimed') ?></td>
        <td><?= e(ucfirst($b['tier'])) ?></td>
        <td><span class="badge badge-<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
        <td style="white-space:nowrap">
          <a class="btn btn-sm btn-ghost" href="/superadmin/listings/edit?id=<?= (int)$b['id'] ?>&back=<?= e($status) ?>">Edit</a>
          <?php if ($b['status'] !== 'live'): ?>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="back" value="<?= e($status) ?>"><button class="btn btn-sm btn-primary">Approve</button></form>
          <?php endif; ?>
          <?php if ($b['status'] !== 'rejected'): ?>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="reject"><input type="hidden" name="back" value="<?= e($status) ?>"><button class="btn btn-sm btn-ghost">Reject</button></form>
          <?php endif; ?>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="verify"><input type="hidden" name="back" value="<?= e($status) ?>"><button class="btn btn-sm btn-ghost">✓ Verify</button></form>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="back" value="<?= e($status) ?>"><button class="btn btn-sm btn-danger" data-confirm="Delete this listing permanently?">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php if (count($list) === 30): ?><nav class="pager"><a href="/superadmin/listings?status=<?= e($status) ?>&page=<?= $page + 1 ?>">Next →</a></nav><?php endif; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
