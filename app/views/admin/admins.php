<?php require __DIR__ . '/_top.php'; ?>
<h1>Admin accounts <span class="badge badge-featured">Superadmin</span></h1>
<div class="two-col">
  <div class="card card-pad">
    <table class="table">
      <tr><th>Name</th><th>Email</th><th>Role</th><th>Last login</th><th></th></tr>
      <?php foreach ($list as $a): ?>
        <tr>
          <td><strong><?= e($a['name']) ?></strong></td>
          <td><?= e($a['email']) ?></td>
          <td><span class="badge <?= $a['role'] === 'superadmin' ? 'badge-featured' : 'badge-pro' ?>"><?= e($a['role']) ?></span></td>
          <td class="mute"><?= $a['last_login_at'] ? e(date('M j, Y', strtotime($a['last_login_at']))) : 'never' ?></td>
          <td>
            <?php if ($a['role'] === 'admin'): ?>
              <form method="post"><?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="demote">
                <button class="btn btn-sm btn-ghost" data-confirm="Demote this admin to a regular member?">Demote</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
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
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
