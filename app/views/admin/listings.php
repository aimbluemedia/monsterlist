<?php require __DIR__ . '/_top.php'; ?>
<div class="section-head"><h1>Listings</h1></div>

<div class="admin-filters">
  <p class="admin-tabs">
    <?php foreach (['pending','live','rejected','all'] as $s): ?>
      <?php // The tier stays put when the tab changes: "Premium, but rejected
            // this time" is a question people ask, and losing the tier on every
            // tab press would mean re-picking it each time. ?>
      <a class="chip" style="<?= $s === $status ? 'border-color:var(--accent);color:var(--accent)' : '' ?>" href="<?= e(listings_url($s, $term, 1, $tier)) ?>"><?= ucfirst($s) ?></a>
    <?php endforeach; ?>
  </p>
  <form class="admin-search" method="get" action="/superadmin/listings">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <input type="hidden" name="tier" value="<?= e($tier) ?>">
    <input type="text" name="q" value="<?= e($term) ?>" placeholder="Search email or domain…"
           aria-label="Search listings by email or domain" autocomplete="off">
    <button class="btn btn-sm btn-primary">Search</button>
    <?php if ($term !== ''): ?><a class="btn btn-sm btn-ghost" href="<?= e(listings_url($status, '', 1, $tier)) ?>">Clear</a><?php endif; ?>
  </form>
</div>

<?php if ($term !== ''): ?>
  <p class="mute" style="margin:-4px 0 14px">
    <?= (int)$total ?> listing<?= $total === 1 ? '' : 's' ?> matching “<?= e($term) ?>”
    in <?= e($status) ?><?= $tier !== 'all' ? ' · ' . e(plan_public_label($tier)) : '' ?>
    — searches the owner’s email, the listing’s contact email and its website.
  </p>
<?php endif; ?>

<div class="card card-pad">
  <?php
    // Tier bubbles, above the table so the split is the first thing read.
    // "All" carries the same number as the tabs and the search have already
    // narrowed to, and each bubble says exactly how many rows pressing it
    // shows. Every tier gets a bubble even at zero: a bubble that disappeared
    // when its count hit zero would take away the way back from it, and
    // "Premium 0" is worth knowing.
    $bubbles = ['all' => 'All'];
    foreach (plan_ladder() as $tk) $bubbles[$tk] = plan_public_label($tk);
  ?>
  <div class="tierbar" role="group" aria-label="Filter listings by tier">
    <?php foreach ($bubbles as $tk => $tlabel): ?>
      <?php $n = $tk === 'all' ? $tierAll : ($tierCounts[$tk] ?? 0); ?>
      <a class="tierbub<?= $tk === $tier ? ' tierbub-on' : '' ?>"
         href="<?= e(listings_url($status, $term, 1, $tk)) ?>"
         <?= $tk === $tier ? 'aria-current="true"' : '' ?>><?= e($tlabel) ?> <b><?= number_format($n) ?></b></a>
    <?php endforeach; ?>
  </div>

  <?php if (!$list): ?>
    <?php // Every dead end says which of the three filters emptied it and
          // offers the one that would put something back on screen. ?>
    <?php $tierWord = $tier !== 'all' ? ' on ' . plan_public_label($tier) : ''; ?>
    <?php if ($term !== ''): ?>
      <p class="mute">Nothing in “<?= e($status) ?>”<?= e($tierWord) ?> matches “<?= e($term) ?>”.
        Try the <a href="<?= e(listings_url('all', $term, 1, $tier)) ?>">All</a> tab<?php if ($tier !== 'all'): ?>,
        <a href="<?= e(listings_url($status, $term)) ?>">all tiers</a><?php endif; ?>,
        or <a href="<?= e(listings_url($status, '', 1, $tier)) ?>">clear the search</a>.</p>
    <?php elseif ($tier !== 'all'): ?>
      <p class="mute">No <?= e(plan_public_label($tier)) ?> listings with status “<?= e($status) ?>”.
        Try <a href="<?= e(listings_url($status)) ?>">all tiers</a>, or the
        <a href="<?= e(listings_url('all', '', 1, $tier)) ?>">All</a> tab.</p>
    <?php else: ?>
      <p class="mute">No listings with status “<?= e($status) ?>”.</p>
    <?php endif; ?>
  <?php else: ?>
  <div class="table-wrap">
  <table class="table">
    <tr><th>Business</th><th>Category</th><th>City</th><th>Owner</th><th>Website</th><th>Tier</th><th>Status</th><th></th></tr>
    <?php foreach ($list as $b): ?>
      <tr>
        <td><strong><?= e($b['name']) ?></strong><?php if ($b['verified']): ?> <span class="badge badge-verified">✓</span><?php endif; ?></td>
        <td><?= e($b['category_label'] ?? '—') ?></td>
        <td><?= e($b['city_name'] ?? '—') ?></td>
        <td>
          <?= e($b['owner_email'] ?? 'unclaimed') ?>
          <?php // The listing's own contact email is a separate field, and often the one being searched for.
                if (!empty($b['email']) && strcasecmp((string)$b['email'], (string)($b['owner_email'] ?? '')) !== 0): ?>
            <br><small class="faint"><?= e($b['email']) ?></small>
          <?php endif; ?>
        </td>
        <td><?php $host = display_host($b['website']); ?>
          <?php if ($host !== ''): ?>
            <small class="trunc" title="<?= e($b['website']) ?>"><?= e($host) ?></small>
          <?php else: ?>—<?php endif; ?></td>
        <?php // plan_public_label, not ucfirst: the bubble directly above this
              // column says "Premium", and the rows under it said "Featured". ?>
        <td><?= e(plan_public_label((string)$b['tier'])) ?></td>
        <td><span class="badge badge-<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
        <td style="white-space:nowrap">
          <?php // One button. Approve, Reject, Verify and Delete moved to the
                // top of the edit page: they are decisions about a listing, and
                // taking one from a table row means taking it without having
                // looked at the listing. ?>
          <a class="btn btn-sm btn-ghost" href="/superadmin/listings/edit?id=<?= (int)$b['id'] ?>&back=<?= e($status) ?>&tier=<?= e($tier) ?>&q=<?= e(rawurlencode($term)) ?>">Edit</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <?php
  $pgPage = $page; $pgTotal = $total; $pgPer = 30;
  $pgUrl  = fn(int $n) => listings_url($status, $term, $n, $tier);
  require __DIR__ . '/_pager.php';
  ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
