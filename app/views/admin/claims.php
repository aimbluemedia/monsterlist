<?php require __DIR__ . '/_top.php'; ?>
<h1>Listing claims</h1>
<div class="card card-pad">
  <?php if (!$list): ?><p class="mute">No claims yet. Claims appear when a member requests ownership of an unclaimed listing.</p>
  <?php else: ?>
  <div class="table-wrap">
  <table class="table">
    <tr><th>Business</th><th>Claimant</th><th>Message</th><th>Status</th><th>Date</th><th></th></tr>
    <?php foreach ($list as $c): ?>
      <tr>
        <td><strong><?= e($c['business_name']) ?></strong></td>
        <td><?= e($c['claimant_name']) ?><br><span class="mute" style="font-size:.82rem"><?= e($c['claimant_email']) ?> · <?= e($c['claimant_plan']) ?> plan</span></td>
        <td style="max-width:320px"><?= e(mb_substr((string)$c['message'], 0, 200)) ?><?= mb_strlen((string)$c['message']) > 200 ? '…' : '' ?></td>
        <td><span class="badge <?= $c['status'] === 'pending' ? 'badge-pending' : ($c['status'] === 'approved' ? 'badge-live' : 'badge-rejected') ?>"><?= e($c['status']) ?></span></td>
        <td><?= e(date('M j', strtotime($c['created_at']))) ?></td>
        <td style="white-space:nowrap">
          <?php if ($c['status'] === 'pending'): ?>
            <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="action" value="approve"><button class="btn btn-sm btn-primary" data-confirm="Transfer this listing to the claimant?">Approve</button></form>
            <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="action" value="reject"><button class="btn btn-sm btn-ghost">Reject</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
