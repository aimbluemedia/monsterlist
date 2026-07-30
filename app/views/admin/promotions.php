<?php require __DIR__ . '/_top.php'; ?>
<div class="section-head"><h1>Promotions</h1><a class="mute" href="/promotions" target="_blank">View live feed →</a></div>
<p>
  <?php foreach (['pending','live','rejected','all'] as $s): ?>
    <a class="chip" style="<?= $s === $status ? 'border-color:var(--accent);color:var(--accent)' : '' ?>" href="/superadmin/promotions?status=<?= $s ?>"><?= ucfirst($s) ?></a>
  <?php endforeach; ?>
</p>
<div class="card card-pad">
  <?php if (!$list): ?><p class="mute">No promotions with status “<?= e($status) ?>”.</p>
  <?php else: ?>
  <table class="table">
    <tr><th>Promotion</th><th>Channel</th><th>Business</th><th>Submitted by</th><th>Clicks</th><th>Status</th><th></th></tr>
    <?php foreach ($list as $p): ?>
      <tr>
        <td>
          <strong><?= e($p['title']) ?></strong><br>
          <a href="<?= e($p['url']) ?>" target="_blank" rel="noopener nofollow" class="faint" style="font-size:.78rem">
            <?= e(mb_strimwidth($p['url'], 0, 62, '…')) ?> ↗
          </a>
          <?php if ($p['blurb']): ?><br><span class="mute" style="font-size:.8rem"><?= e($p['blurb']) ?></span><?php endif; ?>
        </td>
        <td><?= e(promo_channel_label($p['channel'])) ?></td>
        <td><?= e($p['business_name']) ?></td>
        <td><?= e($p['owner_email']) ?></td>
        <td><?= (int)$p['clicks'] ?></td>
        <td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
        <td style="white-space:nowrap">
          <?php if ($p['status'] !== 'live'): ?>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="back" value="<?= e($status) ?>"><button class="btn btn-sm btn-primary">Approve</button></form>
          <?php endif; ?>
          <?php if ($p['status'] !== 'rejected'): ?>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="reject"><input type="hidden" name="back" value="<?= e($status) ?>"><button class="btn btn-sm btn-ghost">Reject</button></form>
          <?php endif; ?>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="back" value="<?= e($status) ?>"><button class="btn btn-sm btn-danger" data-confirm="Delete this promotion permanently?">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php if (count($list) === 30): ?><nav class="pager"><a href="/superadmin/promotions?status=<?= e($status) ?>&page=<?= $page + 1 ?>">Next →</a></nav><?php endif; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
