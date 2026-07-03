<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <h1>Analytics <span class="mute" style="font-size:.9rem;font-weight:600">last 30 days</span></h1>
    <?php if (!$byBiz): ?>
      <div class="card card-pad">No listings yet — analytics appear once a listing is live.</div>
    <?php else: ?>
      <div class="card card-pad">
        <table class="table">
          <tr><th>Business</th><th>Views</th><th>Website clicks</th><th>Calls</th></tr>
          <?php foreach ($byBiz as $b): ?>
            <tr>
              <td><strong><?= e($b['name']) ?></strong></td>
              <td><?= (int)($b['view'] ?? 0) ?></td>
              <td><?= (int)($b['click'] ?? 0) ?></td>
              <td><?= (int)($b['call'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
