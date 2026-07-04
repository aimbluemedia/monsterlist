<?php require __DIR__ . '/_top.php'; ?>
<h1>Dashboard</h1>
<div class="stat-row">
  <div class="card stat"><div class="n"><?= $stats['pending'] ?></div><div class="l">Pending review</div></div>
  <div class="card stat"><div class="n"><?= $stats['live'] ?></div><div class="l">Live listings</div></div>
  <div class="card stat"><div class="n"><?= $stats['members'] ?></div><div class="l">Members</div></div>
  <div class="card stat"><div class="n"><?= $stats['paid'] ?></div><div class="l">Paying members</div></div>
</div>
<div class="stat-row" style="grid-template-columns:repeat(3,1fr)">
  <div class="card stat"><div class="n"><?= $stats['views7'] ?></div><div class="l">Listing views (7d)</div></div>
  <div class="card stat"><div class="n"><?= $stats['reviews'] ?></div><div class="l">New reviews (7d)</div></div>
  <div class="card stat"><div class="n"><?= $stats['claims'] ?></div><div class="l">Pending claims</div></div>
</div>
<div class="card card-pad">
  <div class="section-head"><h3>Moderation queue</h3><a href="/superadmin/listings?status=pending" class="mute">All pending →</a></div>
  <?php if (!$pending): ?><p class="mute">Queue is empty — nothing waiting for review. 🎉</p>
  <?php else: ?>
  <table class="table">
    <tr><th>Business</th><th>Category</th><th>City</th><th>Owner</th><th>Submitted</th><th></th></tr>
    <?php foreach ($pending as $b): ?>
      <tr>
        <td><strong><?= e($b['name']) ?></strong><?php if ($b['tagline']): ?><br><span class="mute" style="font-size:.82rem"><?= e($b['tagline']) ?></span><?php endif; ?></td>
        <td><?= e($b['category_label'] ?? '—') ?></td>
        <td><?= e($b['city_name'] ?? '—') ?></td>
        <td><?= e($b['owner_email'] ?? 'unclaimed') ?></td>
        <td><?= e(date('M j', strtotime($b['created_at']))) ?></td>
        <td style="white-space:nowrap">
          <form method="post" action="/superadmin/listings" style="display:inline"><?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="approve">
            <button class="btn btn-sm btn-primary">Approve</button>
          </form>
          <form method="post" action="/superadmin/listings" style="display:inline"><?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="reject">
            <button class="btn btn-sm btn-ghost">Reject</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
