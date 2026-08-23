<?php require __DIR__ . '/_top.php'; ?>
<div class="section-head"><h1>Members</h1></div>
<form method="get" style="max-width:360px;margin-bottom:14px">
  <input type="text" name="q" value="<?= e($qstr) ?>" placeholder="Search by name or email…">
</form>
<div class="card card-pad table-wrap">
  <?php if (!$list): ?><p class="mute">No members found.</p>
  <?php else: ?>
  <table class="table">
    <tr><th>Member</th><th>Plan</th><th>Listings</th><th>Tokens</th><th>Status</th><th>Joined</th><th></th></tr>
    <?php foreach ($list as $m): ?>
      <tr>
        <?php // The email identifies the account, so it leads. The websites
              // under it are what the account is FOR, and there can be several
              // now — more than one collapses, because a members list is read
              // by scanning down the emails, not by reading everybody's
              // domains on the way past. ?>
        <?php $doms = $domains[(int)$m['id']] ?? []; ?>
        <td>
          <a href="/superadmin/members/edit?id=<?= (int)$m['id'] ?>" class="mb-email"><?= e($m['email']) ?></a>
          <?php if (!$doms): ?>
            <div class="mute mb-none">no domains yet</div>
          <?php elseif (count($doms) === 1): ?>
            <div class="mb-dom"><?= e($doms[0]['domain']) ?>
              <span class="badge badge-<?= $doms[0]['state'] === 'queued' ? 'pending' : e($doms[0]['state']) ?>"><?= e($doms[0]['state']) ?></span>
            </div>
          <?php else: ?>
            <details class="mb-more">
              <summary><?= count($doms) ?> domains</summary>
              <?php foreach ($doms as $d): ?>
                <div class="mb-dom"><?= e($d['domain']) ?>
                  <span class="badge badge-<?= $d['state'] === 'queued' ? 'pending' : e($d['state']) ?>"><?= e($d['state']) ?></span>
                </div>
              <?php endforeach; ?>
            </details>
          <?php endif; ?>
        </td>
        <?php // Shown, not set. The dropdown that used to live in this table
              // changed what somebody pays from a scrolling list; changing a
              // plan is a deliberate act and belongs on the member's page. ?>
        <td><span class="badge badge-plan-<?= e((string)$m['plan']) ?>"><?= e(plan_public_label((string)$m['plan'])) ?></span></td>
        <?php // The count is the way in: "3 listings" is the thing a staff
              // member is looking at when they want to open the account. ?>
        <td><a href="/superadmin/members/edit?id=<?= (int)$m['id'] ?>" style="color:var(--accent);font-weight:700"><?= (int)$m['listing_count'] ?></a></td>
        <td><strong><?= number_format((int)($m['token_balance'] ?? 0)) ?></strong></td>
        <td><span class="badge <?= $m['status'] === 'active' ? 'badge-live' : 'badge-rejected' ?>"><?= e($m['status']) ?></span></td>
        <td><?= e(date('M j, Y', strtotime($m['created_at']))) ?></td>
        <?php // One way in, rather than a row of consequential buttons sitting
              // a stray click apart in a scrolling table. Plan, listings,
              // suspend and delete are all on the member's own page. ?>
        <td style="white-space:nowrap">
          <a class="btn btn-sm btn-primary" href="/superadmin/members/edit?id=<?= (int)$m['id'] ?>">Edit</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php if (count($list) === 30): ?><nav class="pager"><a href="/superadmin/members?q=<?= e(urlencode($qstr)) ?>&page=<?= $page + 1 ?>">Next →</a></nav><?php endif; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
