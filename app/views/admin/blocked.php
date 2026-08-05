<?php require __DIR__ . '/_top.php'; ?>
<div class="section-head"><h1>Blocked</h1></div>
<p class="mute" style="margin:-8px 0 18px;max-width:640px">
  Rejecting a listing automatically blocks the submitter’s email, the listing’s contact
  email and its website domain. Blocked emails can’t create an account; blocked domains
  can’t be submitted as a listing website. Approving a previously rejected listing lifts
  its blocks again. Staff are never blocked by this list.
</p>

<div class="card card-pad" style="margin-bottom:18px">
  <h3>Add a block</h3>
  <form method="post" class="block-add">
    <?= csrf_field() ?>
    <select name="kind" aria-label="Block type">
      <option value="email">Email</option>
      <option value="domain">Domain</option>
    </select>
    <input type="text" name="value" placeholder="someone@example.com or acme.com" required aria-label="Value to block">
    <input type="text" name="reason" placeholder="Reason (optional)" aria-label="Reason">
    <button class="btn btn-sm btn-primary">Block</button>
  </form>
  <p class="form-note">Blocking a domain also blocks its subdomains — <code>acme.com</code> covers <code>shop.acme.com</code>.</p>
</div>

<div class="card card-pad">
  <?php if (!$list): ?>
    <p class="mute">Nothing is blocked. Rejecting a listing adds entries here automatically.</p>
  <?php else: ?>
  <div class="table-wrap">
  <table class="table">
    <tr><th>Type</th><th>Value</th><th>Reason</th><th>Added by</th><th>When</th><th></th></tr>
    <?php foreach ($list as $b): ?>
      <tr>
        <td><span class="badge badge-<?= $b['kind'] === 'email' ? 'pending' : 'rejected' ?>"><?= e($b['kind']) ?></span></td>
        <td><strong><?= e($b['value']) ?></strong></td>
        <td><?= e($b['reason'] ?? '—') ?></td>
        <td><?= e($b['added_by'] ?? 'system') ?></td>
        <td><?= e(substr((string)$b['created_at'], 0, 10)) ?></td>
        <td style="white-space:nowrap">
          <form method="post" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
            <button class="btn btn-sm btn-ghost">Unblock</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
