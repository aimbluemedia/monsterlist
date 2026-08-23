<?php require __DIR__ . '/_top.php'; ?>
<div class="section-head">
  <h1><?= e($m['name'] ?: $m['email']) ?></h1>
  <a class="btn btn-ghost" href="/superadmin/members">← All members</a>
</div>

<div class="card card-pad ed-card">
  <h3>Account</h3>
  <div class="info-row"><span class="mute">Email</span><span><?= e($m['email']) ?></span></div>
  <?php if (!empty($m['website'])): ?>
    <div class="info-row"><span class="mute">Domain at sign-up</span><span><?= e((string)$m['website']) ?></span></div>
  <?php endif; ?>
  <div class="info-row"><span class="mute">Joined</span><span><?= e(date('j M Y', strtotime((string)$m['created_at']))) ?></span></div>
  <div class="info-row"><span class="mute">Status</span>
    <span><span class="badge badge-<?= $m['status'] === 'active' ? 'live' : 'rejected' ?>"><?= e($m['status']) ?></span></span></div>
  <?php if (tokens_ready()): ?>
    <div class="info-row"><span class="mute">Tokens</span><span><strong><?= number_format((int)$m['token_balance']) ?></strong></span></div>
  <?php endif; ?>
</div>

<div class="card card-pad ed-card">
  <h3>Plan</h3>
  <p class="mute ed-note">Setting a plan here marks it comped: it renews itself monthly and Stripe
    cannot take it away, which is what makes it safe for test and gifted accounts. Pro and Premium
    carry as many listings as the member likes; Free carries one.</p>

  <div class="info-row"><span class="mute">Now on</span>
    <span>
      <strong><?= e(plan_public_label((string)$m['plan'])) ?></strong>
      <?php if (!empty($m['plan_comped'])): ?><span class="badge badge-pro">Comped</span><?php endif; ?>
    </span></div>
  <div class="info-row"><span class="mute">Listings allowed</span><span><?= e(plan_listings_label($mPlan)) ?></span></div>
  <?php if (!empty($m['plan_renews_on'])): ?>
    <div class="info-row"><span class="mute">Renews</span><span><?= e(date('j M Y', strtotime((string)$m['plan_renews_on']))) ?></span></div>
  <?php endif; ?>

  <form method="post" action="/superadmin/members" style="margin-top:12px"><?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
    <input type="hidden" name="action" value="setplan">
    <input type="hidden" name="back_to" value="1">
    <div class="me-plan">
      <?php foreach (['free' => 'Free', 'pro' => 'Pro', 'featured' => 'Premium'] as $k => $label): ?>
        <button class="btn btn-sm <?= $m['plan'] === $k ? 'btn-primary' : 'btn-ghost' ?>"
                name="plan" value="<?= e($k) ?>"
                <?= $m['plan'] === $k ? 'disabled' : '' ?>
                data-confirm="Move <?= e($m['email']) ?> to <?= e($label) ?>?"><?= e($label) ?></button>
      <?php endforeach; ?>
    </div>
  </form>
</div>

<div class="card card-pad ed-card">
  <h3>Listings <span class="mute" style="font-weight:400">(<?= count($listings) ?>)</span></h3>
  <?php if (!$listings): ?>
    <p class="mute" style="margin:0">This member has no listings yet.</p>
  <?php else: ?>
    <div class="table-wrap table-narrow">
      <table class="table">
        <tr><th>Business</th><th>Website</th><th>Status</th><th>Tier</th><th></th></tr>
        <?php foreach ($listings as $l): ?>
          <tr>
            <td>
              <strong><?= e($l['name']) ?></strong>
              <br><small class="mute"><?= e($l['category_label'] ?? '—') ?><?= $l['city_name'] ? ' · ' . e($l['city_name']) : '' ?></small>
            </td>
            <td class="intake-domain"><?= e((string)$l['website']) ?></td>
            <td><span class="badge badge-<?= e($l['status']) ?>"><?= e($l['status']) ?></span></td>
            <td><?= e(ucfirst((string)$l['tier'])) ?></td>
            <td style="white-space:nowrap">
              <?php if ($l['status'] === 'live' && $l['city_id']): ?>
                <a class="btn btn-sm btn-ghost" href="<?= e(business_path($l)) ?>">View</a>
              <?php endif; ?>
              <a class="btn btn-sm btn-ghost" href="/superadmin/listings/edit?id=<?= (int)$l['id'] ?>&back=all">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if (tokens_ready()): ?>
  <div class="card card-pad ed-card">
    <h3>Adjust tokens</h3>
    <form method="post" action="/superadmin/members"><?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
      <input type="hidden" name="action" value="tokens">
      <input type="hidden" name="back_to" value="1">
      <div class="form-grid">
        <div><label>Amount (negative to take away)</label><input type="number" name="delta" value="0"></div>
        <div><label>Note</label><input type="text" name="note" maxlength="200" placeholder="Why"></div>
      </div>
      <button class="btn btn-sm btn-primary">Apply</button>
    </form>
  </div>
<?php endif; ?>

<?php if ($history): ?>
  <div class="card card-pad ed-card">
    <h3>Monthly service</h3>
    <div class="table-wrap table-narrow">
      <table class="table">
        <tr><th>Month</th><th>Plan</th><th>Due</th><th>Status</th></tr>
        <?php foreach ($history as $h): ?>
          <tr>
            <td><?= e(date('F Y', strtotime($h['month'] . '-01'))) ?></td>
            <td><?= e(plan_public_label((string)$h['plan'])) ?></td>
            <td><?= e(date('j M', strtotime((string)$h['due_on']))) ?></td>
            <td><?= $h['done_at']
                  ? '<span class="badge badge-live">done ' . e(date('j M', strtotime((string)$h['done_at']))) . '</span>'
                  : '<span class="badge badge-pending">open</span>' ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
<?php endif; ?>

<div class="card card-pad ed-card">
  <h3>Danger</h3>
  <form method="post" action="/superadmin/members" style="display:inline"><?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
    <input type="hidden" name="action" value="suspend">
    <input type="hidden" name="back_to" value="1">
    <button class="btn btn-sm btn-ghost" data-confirm="<?= $m['status'] === 'active' ? 'Suspend' : 'Reactivate' ?> <?= e($m['email']) ?>?">
      <?= $m['status'] === 'active' ? 'Suspend account' : 'Reactivate account' ?>
    </button>
  </form>
  <form method="post" action="/superadmin/members" style="display:inline"><?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
    <input type="hidden" name="action" value="delete">
    <button class="btn btn-sm ls-del" data-confirm="Delete <?= e($m['email']) ?>? Their listings stay, unclaimed.">Delete account</button>
  </form>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
