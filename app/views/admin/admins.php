<?php require __DIR__ . '/_top.php'; ?>
<h1>Admin accounts <span class="badge badge-featured">Superadmin</span></h1>
<div class="two-col">
  <div class="card card-pad">
    <?php // Five columns is more than a phone has room for. table-narrow so it
          // does not force the 960px scroller a genuinely wide table gets, but
          // still scrolls inside the card rather than taking the page with it. ?>
    <div class="table-wrap table-narrow">
    <table class="table">
      <tr><th>Name</th><th>Email</th><th>Role</th><th>Last login</th><th></th></tr>
      <?php foreach ($list as $a): ?>
        <?php $self = (int)$a['id'] === (int)$u['id']; ?>
        <tr<?= !empty($edit) && (int)$edit['id'] === (int)$a['id'] ? ' class="row-active"' : '' ?>>
          <td><strong><?= e($a['name']) ?></strong><?php if ($self): ?> <span class="mute" style="font-size:.8rem">(you)</span><?php endif; ?></td>
          <td><?= e($a['email']) ?></td>
          <td><span class="badge <?= $a['role'] === 'superadmin' ? 'badge-featured' : 'badge-pro' ?>"><?= e($a['role']) ?></span></td>
          <td class="mute"><?= $a['last_login_at'] ? e(date('M j, Y', strtotime($a['last_login_at']))) : 'never' ?></td>
          <td style="white-space:nowrap">
            <a class="btn btn-sm btn-ghost" href="/superadmin/admins?edit=<?= (int)$a['id'] ?>">Edit</a>
            <?php if (!$self): ?>
              <form method="post" style="display:inline"><?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="delete">
                <button class="btn btn-sm btn-ghost" data-confirm="Revoke staff access and suspend this account?">Remove</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    </div>
  </div>

  <?php if (!empty($edit)): ?>
    <div class="card card-pad">
      <h3>Edit <?= e($edit['name']) ?></h3>
      <p class="mute" style="font-size:.85rem">Leave the password blank to keep the current one.</p>
      <form method="post"><?= csrf_field() ?>
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
        <label>Name *</label><input type="text" name="name" value="<?= e($edit['name']) ?>" required>
        <label>Email *</label><input type="email" name="email" value="<?= e($edit['email']) ?>" required>
        <label>New password (8+ chars, optional)</label>
        <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Unchanged">
        <label>Role</label>
        <select name="role">
          <option value="admin"<?= $edit['role'] === 'admin' ? ' selected' : '' ?>>Admin — moderation only</option>
          <option value="superadmin"<?= $edit['role'] === 'superadmin' ? ' selected' : '' ?>>Superadmin — full control</option>
        </select>
        <button class="btn btn-primary" style="margin-top:14px">Save changes</button>
        <a class="btn btn-ghost" style="margin-top:14px" href="/superadmin/admins">Cancel</a>
      </form>
    </div>
  <?php else: ?>
    <div class="card card-pad">
      <h3>Create admin</h3>
      <p class="mute" style="font-size:.85rem">Admins can moderate listings, members and reviews. Only superadmins manage admins and site settings.</p>
      <form method="post"><?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <label>Name *</label><input type="text" name="name" required>
        <label>Email *</label><input type="email" name="email" required>
        <label>Password * (8+ chars)</label><input type="password" name="password" minlength="8" required>
        <button class="btn btn-primary" style="margin-top:14px">Create admin</button>
      </form>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
