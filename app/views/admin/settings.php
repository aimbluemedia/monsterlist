<?php require __DIR__ . '/_top.php'; ?>
<h1>Site settings <span class="badge badge-featured">Superadmin</span></h1>
<form method="post" class="card card-pad" style="max-width:560px"><?= csrf_field() ?>
  <h3>Branding</h3>
  <label>Site name</label>
  <input type="text" name="site_name" value="<?= e(setting('site_name')) ?>">
  <label>Tagline</label>
  <input type="text" name="site_tagline" value="<?= e(setting('site_tagline')) ?>">

  <h3 style="margin-top:24px">Membership pricing (USD/month)</h3>
  <div class="form-grid">
    <div><label>Pro price</label><input type="number" name="price_pro_monthly" value="<?= e(setting('price_pro_monthly')) ?>" min="1"></div>
    <div><label>Featured price</label><input type="number" name="price_featured_monthly" value="<?= e(setting('price_featured_monthly')) ?>" min="1"></div>
  </div>

  <h3 style="margin-top:24px">Tokens &amp; the promotion engine</h3>
  <p class="mute" style="font-size:.85rem">Members spend tokens to put a link in the member feed and earn them by
    opening other members' promotions. Everything except the cost varies by plan — that is what makes a paid
    membership worth paying for when effort alone can earn tokens.</p>

  <label>Cost to run one promotion</label>
  <input type="number" name="tokens_cost_promo" min="0" value="<?= e(setting('tokens_cost_promo', '10')) ?>">

  <div class="table-wrap" style="margin-top:14px">
    <table class="table table-narrow">
      <tr>
        <th>Plan</th>
        <th title="Tokens added on the member's first visit each month">Monthly tokens</th>
        <th title="Tokens earned for opening another member's promotion">Earn / view</th>
        <th title="Most a member can earn in one day">Daily cap</th>
        <th title="Promotions this plan may submit per calendar month">Promos / month</th>
        <th title="Extra days of freshness in the feed">Feed boost</th>
      </tr>
      <?php foreach (['free' => 'Free', 'pro' => 'Pro', 'featured' => 'Featured'] as $pk => $pl):
            $pr = token_rules($pk); ?>
        <tr>
          <td><strong><?= $pl ?></strong><br><span class="faint" style="font-size:.78rem">
            <?= token_views_per_promo($pk) ? token_views_per_promo($pk) . ' views = 1 promo' : 'earning off' ?></span></td>
          <td><input type="number" min="0" name="tokens_grant_<?= $pk ?>" value="<?= (int)$pr['grant'] ?>" style="width:90px;padding:6px 8px"></td>
          <td><input type="number" min="0" name="tokens_earn_<?= $pk ?>" value="<?= (int)$pr['earn_view'] ?>" style="width:80px;padding:6px 8px"></td>
          <td><input type="number" min="0" name="tokens_daily_<?= $pk ?>" value="<?= (int)$pr['daily_earn_cap'] ?>" style="width:80px;padding:6px 8px"></td>
          <td><input type="number" min="0" name="promos_max_<?= $pk ?>" value="<?= (int)$pr['promos_max'] ?>" style="width:90px;padding:6px 8px"></td>
          <td><?php if ($pk === 'free'): ?><span class="faint">none</span>
              <?php else: ?><input type="number" min="0" name="feed_boost_<?= $pk ?>" value="<?= e(setting('feed_boost_' . $pk, $pk === 'pro' ? '7' : '14')) ?>" style="width:70px;padding:6px 8px"> days<?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <p class="form-note">
    <strong>Promos / month</strong> is the ceiling effort cannot lift — tokens buy a promotion, the plan decides
    how many. <strong>Feed boost</strong> makes a paid promotion sort as though it were published that many days
    later, so it sits higher for longer without burying free members permanently. 0 in any daily cap means no ceiling.
  </p>

  <h3 style="margin-top:24px">AI fill (Anthropic API)</h3>
  <p class="mute" style="font-size:.85rem">Lets members auto-fill the New Listing form from their website.
  Get an API key at <a href="https://platform.claude.com" target="_blank" rel="noopener" style="color:var(--accent)">platform.claude.com</a> → API keys.
  Each fill costs a few cents; members are limited to 10 per hour.</p>
  <label>Anthropic API key <?= setting('anthropic_api_key') !== '' ? '<span class="badge badge-live">configured</span>' : '<span class="badge badge-pending">not set</span>' ?></label>
  <input type="password" name="anthropic_api_key" value="<?= e(setting('anthropic_api_key')) ?>" placeholder="sk-ant-..." autocomplete="off">
  <p class="form-note">Leave empty to hide the AI fill feature from members.</p>

  <h3 style="margin-top:24px">Stripe price IDs</h3>
  <p class="mute" style="font-size:.85rem">Create two recurring monthly Prices in your Stripe dashboard and paste their IDs (price_…). API keys live in <code>app/config.php</code>.</p>
  <div class="form-grid">
    <div><label>Pro price ID</label><input type="text" name="stripe_price_pro" value="<?= e(setting('stripe_price_pro')) ?>" placeholder="price_…"></div>
    <div><label>Featured price ID</label><input type="text" name="stripe_price_featured" value="<?= e(setting('stripe_price_featured')) ?>" placeholder="price_…"></div>
  </div>
  <button class="btn btn-primary" style="margin-top:18px">Save settings</button>
</form>
<?php require __DIR__ . '/_bottom.php'; ?>
