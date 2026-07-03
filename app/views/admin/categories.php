<?php require __DIR__ . '/_top.php'; ?>
<h1>Categories</h1>
<div class="two-col">
  <div class="card card-pad">
    <table class="table">
      <tr><th>Icon</th><th>Label</th><th>ID</th><th>In use</th><th></th></tr>
      <?php foreach ($list as $c): ?>
        <tr>
          <td><?= e($c['icon']) ?></td>
          <td><strong><?= e($c['label']) ?></strong></td>
          <td class="mute"><?= e($c['id']) ?></td>
          <td><?= (int)$c['in_use'] ?></td>
          <td>
            <form method="post"><?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= e($c['id']) ?>"><input type="hidden" name="action" value="delete">
              <button class="btn btn-sm btn-danger" data-confirm="Delete this category?" <?= $c['in_use'] > 0 ? 'disabled' : '' ?>>Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <div class="card card-pad">
    <h3>Add category</h3>
    <form method="post"><?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <label>Label *</label><input type="text" name="label" required maxlength="120" placeholder="e.g. Landscaping">
      <label>Icon (emoji)</label><input type="text" name="icon" maxlength="8" placeholder="🌿">
      <label>ID (optional — defaults to slug of label)</label><input type="text" name="id" maxlength="40" placeholder="landscaping">
      <button class="btn btn-primary" style="margin-top:14px">Add category</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
