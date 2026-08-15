<?php require __DIR__ . '/../_icons.php'; ?>
<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <h1>Promotion engine</h1>
    <p class="mute" style="max-width:640px">Already published something? Drop the link in and the member
      network sees it. Real people open it on the platform you posted to.
      <a href="/promotions" style="color:var(--accent);font-weight:700">See the live feed →</a></p>

    <?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>

    <?php $tkRules = token_rules((string)$u['plan']); $tkBal = (int)$u['token_balance']; ?>
    <div class="card card-pad tk-bar">
      <span>Balance: <b><?= number_format($tkBal) ?></b> token<?= $tkBal === 1 ? '' : 's' ?></span>
      <span class="mute">Each costs <?= (int)$tkRules['cost_promo'] ?>; you earn <?= (int)$tkRules['earn_view'] ?> per member promotion you open.</span>
      <span><b><?= max(0, $promoMax - $promoUsed) ?></b> of <?= (int)$promoMax ?> left this month</span>
      <a class="btn btn-ghost btn-sm" style="margin-left:auto" href="/account/tokens">Tokens &amp; history</a>
    </div>

    <?php if (!$mine): ?>
      <div class="card card-pad" style="margin-top:16px">
        <strong>Add a listing first.</strong>
        <p class="mute">A promotion is attached to one of your businesses, so members know who it's from.</p>
        <a class="btn btn-primary" href="/account/listings/new">Add my business</a>
      </div>
    <?php else: ?>
      <form method="post" class="card card-pad" style="max-width:640px;margin-top:16px"><?= csrf_field() ?>
        <h3>Submit a promotion</h3>
        <label>Which listing? *</label>
        <select name="business_id" required>
          <?php foreach ($mine as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= post('business_id') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <label>Link *</label>
        <input type="text" name="url" value="<?= e(post('url')) ?>" placeholder="https://youtube.com/watch?v=… or your blog post" required>
        <p class="form-note">The page you already published — we send members straight to it.</p>

        <label>Title *</label>
        <input type="text" name="title" value="<?= e(post('title')) ?>" maxlength="200" placeholder="What is it? e.g. How we doubled our bookings in 60 days" required>

        <label>One-line description</label>
        <input type="text" name="blurb" value="<?= e(post('blurb')) ?>" maxlength="400" placeholder="Why another business owner should give it two minutes">

        <label>Channel</label>
        <select name="channel">
          <option value="">Detect from the link</option>
          <?php foreach (promo_channels() as $key => [$chLabel, $chIcon]): ?>
            <option value="<?= e($key) ?>" <?= post('channel') === $key ? 'selected' : '' ?>><?= e($chLabel) ?></option>
          <?php endforeach; ?>
        </select>

        <button class="btn btn-primary" style="margin-top:16px">Submit for review</button>
        <p class="form-note">Reviewed by our team before it reaches the feed — usually within 24 hours.</p>
      </form>

      <h2 style="margin-top:30px">My promotions</h2>
      <?php if (!$list): ?>
        <p class="mute">Nothing submitted yet.</p>
      <?php else: ?>
        <div class="card card-pad table-wrap">
          <table class="table">
            <tr><th>Promotion</th><th>Channel</th><th>Listing</th><th>Clicks</th><th>Status</th><th></th></tr>
            <?php foreach ($list as $p): ?>
              <tr>
                <td>
                  <strong><?= e($p['title']) ?></strong><br>
                  <span class="faint" style="font-size:.78rem"><?= e(mb_strimwidth($p['url'], 0, 54, '…')) ?></span>
                </td>
                <td><?= e(promo_channel_label($p['channel'])) ?></td>
                <td><?= e($p['business_name']) ?></td>
                <td><?= (int)$p['clicks'] ?></td>
                <td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                <td>
                  <form method="post" style="display:inline"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn-sm btn-ghost" data-confirm="Remove this promotion?">Remove</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
