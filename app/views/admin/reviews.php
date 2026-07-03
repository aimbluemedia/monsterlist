<?php require __DIR__ . '/_top.php'; ?>
<h1>Reviews</h1>
<div class="card card-pad">
  <?php if (!$list): ?><p class="mute">No reviews yet.</p>
  <?php else: ?>
  <table class="table">
    <tr><th>Business</th><th>Author</th><th>Rating</th><th>Review</th><th>Status</th><th>Date</th><th></th></tr>
    <?php foreach ($list as $r): ?>
      <tr>
        <td><strong><?= e($r['business_name']) ?></strong></td>
        <td><?= e($r['author_name']) ?></td>
        <td><span class="stars"><?= str_repeat('★', (int)$r['rating']) ?></span></td>
        <td style="max-width:320px"><?= e(mb_substr((string)$r['body'], 0, 140)) ?><?= mb_strlen((string)$r['body']) > 140 ? '…' : '' ?></td>
        <td><span class="badge <?= $r['status'] === 'live' ? 'badge-live' : 'badge-pending' ?>"><?= e($r['status']) ?></span></td>
        <td><?= e(date('M j', strtotime($r['created_at']))) ?></td>
        <td style="white-space:nowrap">
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="btn btn-sm btn-ghost"><?= $r['status'] === 'live' ? 'Hide' : 'Show' ?></button></form>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-danger" data-confirm="Delete this review?">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php if (count($list) === 30): ?><nav class="pager"><a href="/admin/reviews?page=<?= $page + 1 ?>">Next →</a></nav><?php endif; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
