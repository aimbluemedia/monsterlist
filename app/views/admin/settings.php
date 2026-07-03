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

  <h3 style="margin-top:24px">Stripe price IDs</h3>
  <p class="mute" style="font-size:.85rem">Create two recurring monthly Prices in your Stripe dashboard and paste their IDs (price_…). API keys live in <code>app/config.php</code>.</p>
  <div class="form-grid">
    <div><label>Pro price ID</label><input type="text" name="stripe_price_pro" value="<?= e(setting('stripe_price_pro')) ?>" placeholder="price_…"></div>
    <div><label>Featured price ID</label><input type="text" name="stripe_price_featured" value="<?= e(setting('stripe_price_featured')) ?>" placeholder="price_…"></div>
  </div>
  <button class="btn btn-primary" style="margin-top:18px">Save settings</button>
</form>
<?php require __DIR__ . '/_bottom.php'; ?>
