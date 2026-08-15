<?php
$statusLabel = ['requested' => 'With our writers', 'writing' => 'Being written', 'published' => 'Published'];
?>
<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <h1>Monthly article</h1>
    <?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>

    <div class="card card-pad" style="margin-bottom:18px;background:var(--accent-soft);border-color:var(--accent)">
      <h3 style="margin-top:0">What your Featured plan does for you each month</h3>
      <p class="mute" style="margin:0 0 8px">You tell us the topic. We write the article, publish it, and post it out
        across our own channels — <?= e(implode(', ', article_channels())) ?> — then promote it to each of yours.</p>
      <p class="mute" style="margin:0">One article a month, included. Tell us what you want written for
        <strong><?= e(date('F Y', strtotime($month . '-01'))) ?></strong>.</p>
    </div>

    <?php if ($current && $current['status'] === 'published' && $current['url']): ?>
      <div class="card card-pad" style="margin-bottom:18px">
        <h3>This month's article is live</h3>
        <p style="margin:0 0 10px"><strong><?= e($current['topic']) ?></strong></p>
        <a class="btn btn-primary btn-sm" href="<?= e($current['url']) ?>" target="_blank" rel="noopener">Read it ↗</a>
      </div>
    <?php endif; ?>

    <form method="post" class="card card-pad" style="margin-bottom:18px"><?= csrf_field() ?>
      <h3><?= $current ? 'Your brief for this month' : 'Brief this month\'s article' ?></h3>
      <?php if ($current): ?>
        <p class="form-note" style="margin-top:0">Status: <strong><?= e($statusLabel[$current['status']] ?? $current['status']) ?></strong>.
          You can keep editing this until we start writing.</p>
      <?php endif; ?>

      <?php if ($mine): ?>
        <label>Which listing is it for?</label>
        <select name="business_id">
          <?php foreach ($mine as $m): ?>
            <option value="<?= (int)$m['id'] ?>" <?= $current && (int)$current['business_id'] === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>

      <label>Topic *</label>
      <input type="text" name="topic" maxlength="200" required
             value="<?= e($_SERVER['REQUEST_METHOD'] === 'POST' ? post('topic') : (string)($current['topic'] ?? '')) ?>"
             placeholder="e.g. How to choose a stone countertop that lasts">

      <label>Anything we should know?</label>
      <textarea name="brief" rows="7" maxlength="4000"
                placeholder="Who it is for, what you want it to say, anything we should avoid, links worth citing."><?= e($_SERVER['REQUEST_METHOD'] === 'POST' ? post('brief') : (string)($current['brief'] ?? '')) ?></textarea>

      <button class="btn btn-primary" style="margin-top:16px"><?= $current ? 'Update the brief' : 'Send the brief' ?></button>
    </form>

    <?php if ($past): ?>
      <div class="card card-pad">
        <h3>Earlier months</h3>
        <div class="table-wrap">
          <table class="table">
            <tr><th>Month</th><th>Topic</th><th>Status</th><th></th></tr>
            <?php foreach ($past as $a): ?>
              <tr>
                <td class="mute" style="white-space:nowrap"><?= e(date('M Y', strtotime($a['month'] . '-01'))) ?></td>
                <td><?= e($a['topic']) ?></td>
                <td><?= e($statusLabel[$a['status']] ?? $a['status']) ?></td>
                <td><?php if ($a['url']): ?><a href="<?= e($a['url']) ?>" target="_blank" rel="noopener" style="color:var(--accent);font-weight:700">Read ↗</a><?php endif; ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
