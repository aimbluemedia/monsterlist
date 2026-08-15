<?php require __DIR__ . '/_top.php';
$statusLabel = ['requested' => 'Requested', 'writing' => 'Writing', 'published' => 'Published'];
?>
<div class="section-head">
  <h1>Monthly articles</h1>
  <span class="mute"><?= count($list) ?> brief<?= count($list) === 1 ? '' : 's' ?></span>
</div>

<p class="mute" style="margin-top:-8px;margin-bottom:18px">Featured members brief one article a month. We write it,
  publish it, post it to our own <?= e(implode(', ', article_channels())) ?> channels, and promote it to each of theirs.</p>

<?php if (!$list): ?>
  <div class="card card-pad"><p class="mute" style="margin:0">No articles briefed yet.</p></div>
<?php endif; ?>

<?php foreach ($list as $a): ?>
  <div class="card card-pad ed-card">
    <div class="section-head" style="margin-bottom:8px">
      <h3 style="margin:0"><?= e($a['topic']) ?></h3>
      <span class="badge badge-<?= $a['status'] === 'published' ? 'live' : ($a['status'] === 'writing' ? 'pending' : 'pro') ?>">
        <?= e($statusLabel[$a['status']] ?? $a['status']) ?>
      </span>
    </div>
    <p class="mute ed-note" style="margin-top:0">
      <?= e(date('F Y', strtotime($a['month'] . '-01'))) ?> ·
      <?= e($a['business_name'] ?: 'No listing chosen') ?> ·
      <?= e($a['owner_name']) ?> &lt;<?= e($a['owner_email']) ?>&gt;
    </p>
    <?php if ($a['brief']): ?>
      <p style="white-space:pre-line;margin:0 0 12px"><?= e($a['brief']) ?></p>
    <?php endif; ?>

    <form method="post"><?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
      <div class="form-grid">
        <div>
          <label>Status</label>
          <select name="status">
            <?php foreach ($statusLabel as $k => $lbl): ?>
              <option value="<?= $k ?>" <?= $a['status'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Published at</label>
          <input type="text" name="url" value="<?= e((string)$a['url']) ?>" placeholder="https://…">
        </div>
      </div>
      <label>Note for us</label>
      <input type="text" name="staff_note" maxlength="500" value="<?= e((string)$a['staff_note']) ?>"
             placeholder="Who is writing it, where it has been posted so far…">
      <button class="btn btn-primary btn-sm" style="margin-top:12px">Save</button>
    </form>
  </div>
<?php endforeach; ?>
<?php require __DIR__ . '/_bottom.php'; ?>
