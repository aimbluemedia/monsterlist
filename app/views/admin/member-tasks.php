<?php require __DIR__ . '/_top.php'; ?>
<div class="section-head">
  <h1><?= e($planLabel) ?> members</h1>
</div>

<div class="card card-pad ed-card">
  <h3>Due now <span class="mute" style="font-weight:400">(<?= count($due) ?>)</span></h3>
  <p class="mute ed-note">
    A <?= e($planLabel) ?> member appears here on their renewal date and stays until the month is
    ticked off — an overdue one does not quietly disappear because the date has passed.
    <?php if ($items): ?>Ticking every box finishes the month by itself.<?php endif; ?>
  </p>

  <?php if (!$due): ?>
    <p class="mute" style="margin:0">Nothing due. Everyone on <?= e($planLabel) ?> has been served
      this cycle — the roster below says when the next one falls.</p>
  <?php else: ?>
    <?php foreach ($due as $t): ?>
      <?php $checked = cycle_checked($t); $overdue = $t['due_on'] < date('Y-m-d'); ?>
      <div class="mt-task">
        <div class="mt-head">
          <div>
            <b><?= e($t['business_name'] ?: $t['name'] ?: $t['email']) ?></b>
            <?php if (!empty($t['plan_comped'])): ?><span class="badge badge-pro">Comped</span><?php endif; ?>
            <div class="mute mt-sub">
              <?= e($t['email']) ?> ·
              <?= e(date('F Y', strtotime($t['month'] . '-01'))) ?> ·
              due <?= e(date('j M', strtotime($t['due_on']))) ?>
              <?php if ($overdue): ?><b class="mt-late">overdue</b><?php endif; ?>
            </div>
          </div>
          <div class="mt-links">
            <?php if ($t['business_id']): ?>
              <a class="btn btn-sm btn-ghost" href="/superadmin/listings/edit?id=<?= (int)$t['business_id'] ?>&back=live">Listing</a>
            <?php endif; ?>
            <a class="btn btn-sm btn-ghost" href="/superadmin/members?q=<?= e(urlencode((string)$t['email'])) ?>">Member</a>
            <?php if ($planKey === 'featured'): ?>
              <a class="btn btn-sm btn-ghost" href="/superadmin/articles">Articles</a>
            <?php endif; ?>
          </div>
        </div>

        <form method="post"><?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <?php if ($items): ?>
            <div class="mt-items">
              <?php foreach ($items as $item): ?>
                <label class="mt-item">
                  <input type="checkbox" name="items[]" value="<?= e($item) ?>"
                         <?= in_array($item, $checked, true) ? 'checked' : '' ?>>
                  <span><?= e($item) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <label>Notes</label>
          <textarea name="note" rows="2" maxlength="500"
                    placeholder="<?= $items ? 'Anything worth remembering about this month' : 'What was done for this member this month' ?>"><?= e((string)$t['note']) ?></textarea>
          <div class="mt-actions">
            <button class="btn btn-sm btn-primary" name="action" value="save">Save</button>
            <button class="btn btn-sm mt-done" name="action" value="done"
                    data-confirm="Mark <?= e($t['business_name'] ?: $t['email']) ?> done for <?= e(date('F', strtotime($t['month'] . '-01'))) ?>?">Done this month</button>
          </div>
        </form>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="card card-pad ed-card">
  <h3>All <?= e($planLabel) ?> members <span class="mute" style="font-weight:400">(<?= count($roster) ?>)</span></h3>
  <p class="mute ed-note">Everyone on the plan, whether or not they are due. Change somebody's plan
    from <a href="/superadmin/members">Members</a> — setting one by hand marks it comped and starts
    the clock from that day.</p>

  <?php if (!$roster): ?>
    <p class="mute" style="margin:0">Nobody is on <?= e($planLabel) ?> yet.</p>
  <?php else: ?>
    <div class="table-wrap table-narrow">
      <table class="table">
        <tr><th>Member</th><th>Renews</th><th>Months served</th><th></th></tr>
        <?php foreach ($roster as $r): ?>
          <tr>
            <td>
              <strong><?= e($r['business_name'] ?: $r['name'] ?: $r['email']) ?></strong>
              <?php if (!empty($r['plan_comped'])): ?><span class="badge badge-pro">Comped</span><?php endif; ?>
              <br><small class="mute"><?= e($r['email']) ?></small>
            </td>
            <td><?= $r['plan_renews_on'] ? e(date('j M Y', strtotime($r['plan_renews_on']))) : '<span class="mute">—</span>' ?></td>
            <td><?= (int)$r['months_done'] ?></td>
            <td style="white-space:nowrap">
              <a class="btn btn-sm btn-ghost" href="/superadmin/members?q=<?= e(urlencode((string)$r['email'])) ?>">Manage</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
