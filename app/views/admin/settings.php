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

  <h3 style="margin-top:24px">Tokens</h3>
  <p class="mute" style="font-size:.85rem">Members spend tokens to put a link in the member feed, and earn them by
    opening other members' promotions. Allowances land on each member's first visit of the month.</p>
  <div class="form-grid">
    <div><label>Cost to promote</label><input type="number" name="tokens_cost_promo" min="0" value="<?= e(setting('tokens_cost_promo', '10')) ?>"></div>
    <div><label>Earned per view</label><input type="number" name="tokens_earn_view" min="0" value="<?= e(setting('tokens_earn_view', '2')) ?>"></div>
  </div>
  <label>Most a member can earn in a day</label>
  <input type="number" name="tokens_daily_earn_cap" min="0" value="<?= e(setting('tokens_daily_earn_cap', '20')) ?>">
  <p class="form-note">0 means no daily ceiling.</p>
  <label style="margin-top:14px">Monthly allowance by plan</label>
  <div class="form-grid">
    <div><label class="mute" style="font-weight:500">Free</label><input type="number" name="tokens_grant_free" min="0" value="<?= e(setting('tokens_grant_free', '20')) ?>"></div>
    <div><label class="mute" style="font-weight:500">Pro</label><input type="number" name="tokens_grant_pro" min="0" value="<?= e(setting('tokens_grant_pro', '120')) ?>"></div>
  </div>
  <div class="form-grid">
    <div><label class="mute" style="font-weight:500">Featured</label><input type="number" name="tokens_grant_featured" min="0" value="<?= e(setting('tokens_grant_featured', '400')) ?>"></div>
    <div></div>
  </div>

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
