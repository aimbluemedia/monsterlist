<?php require __DIR__ . '/_top.php'; ?>
<div class="section-head">
  <h1>Member intake</h1>
</div>

<div class="card card-pad ed-card">
  <div class="intake-head">
    <h3><?= $showAll ? 'Everyone' : 'Queue' ?>
      <span class="mute" style="font-weight:400">(<?= count($queue) ?>)</span></h3>
    <?php // A queue holds work outstanding, so approving takes a row off it.
          // The ones that have been through are still here to look at. ?>
    <?php if ($showAll): ?>
      <a class="btn btn-sm btn-ghost" href="/superadmin/intake">Show the queue only</a>
    <?php elseif ($done): ?>
      <a class="btn btn-sm btn-ghost" href="/superadmin/intake?show=all"><?= (int)$done ?> approved — show them</a>
    <?php endif; ?>
  </div>
  <?php if (!$queue): ?>
    <p class="mute" style="margin:0">
      <?php if ($done && !$showAll): ?>
        Queue clear — all <?= (int)$done ?> of them are live.
        <a href="/superadmin/intake?show=all" style="color:var(--accent);font-weight:700">Show them anyway</a>.
      <?php else: ?>
        Nobody yet. Members added below — by hand or through the API — appear here.
      <?php endif; ?>
    </p>
  <?php else: ?>
    <div class="table-wrap table-narrow">
      <table class="table">
        <tr><th>Member</th><th>Domain</th><th>Listing</th><th>Added</th><th>Manage</th></tr>
        <?php foreach ($queue as $r): ?>
          <tr>
            <td><strong><?= e($r['email']) ?></strong></td>
            <td class="intake-domain"><?= e((string)$r['website']) ?></td>
            <td>
              <?php if ($r['business_id']): ?>
                <span class="badge badge-<?= e((string)$r['business_status']) ?>"><?= e((string)$r['business_status']) ?></span>
                <?= e((string)$r['business_name']) ?>
                <?php // A listing built from a website AI could not fully place
                      // still needs a person. Say which part is missing rather
                      // than leaving it to be discovered on the public page. ?>
                <?php $gaps = []; ?>
                <?php if (!$r['city_id']) $gaps[] = 'no city'; ?>
                <?php if (!$r['category_id']) $gaps[] = 'no category'; ?>
                <?php if ($gaps): ?><br><small class="intake-gap"><?= e(implode(' · ', $gaps)) ?> — needs you</small><?php endif; ?>
              <?php else: ?>
                <span class="mute">not built</span>
                <?php if (!empty($r['intake_note'])): ?>
                  <br><small class="intake-err"><?= e((string)$r['intake_note']) ?></small>
                <?php endif; ?>
              <?php endif; ?>
            </td>
            <td class="mute"><?= e(date('M j, H:i', strtotime((string)$r['intake_at']))) ?></td>
            <td>
              <div class="intake-actions">
                <?php if ($r['business_id']): ?>
                  <?php // Approve straight from here when the built listing needs
                        // no correction. Anything else is a trip to the editor. ?>
                  <?php if ($r['business_status'] !== 'live'): ?>
                    <form method="post"><?= csrf_field() ?>
                      <input type="hidden" name="action" value="approve">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button class="btn btn-sm intake-approve"
                              data-confirm="Put &quot;<?= e((string)$r['business_name']) ?>&quot; live?">Approve</button>
                    </form>
                  <?php endif; ?>
                  <a class="btn btn-sm btn-ghost" href="/superadmin/listings/edit?id=<?= (int)$r['business_id'] ?>&back=pending">Edit listing</a>
                <?php else: ?>
                  <form method="post"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="build">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm intake-build"
                            data-confirm="Read <?= e((string)$r['website']) ?> with AI and build the listing?">Build listing</button>
                  </form>
                <?php endif; ?>
                <a class="btn btn-sm btn-ghost" href="/superadmin/members?q=<?= e(urlencode((string)$r['email'])) ?>">Member</a>
                <?php if (!$r['business_id']): ?>
                  <form method="post"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm intake-del" data-confirm="Delete this account? It has no listing yet.">Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <p class="form-note">Building reads the website, asks AI for the listing fields, and saves the
      result as <strong>pending</strong> — never live. You land in the staff editor with it open.
      Approving puts the listing live and takes the row off this queue; a rejected one stays,
      because the listing is decided but the account is not.</p>
  <?php endif; ?>
</div>

<div class="card card-pad ed-card">
  <h3>Add members</h3>
  <p class="mute ed-note">An email, a password and a domain make an account. Nothing is read from
    the domain yet — that is the <strong>Build listing</strong> button in the queue above, one
    member at a time, so you see what AI made of a website before the public does.</p>

  <form method="post" class="intake-add"><?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <div class="intake-fields">
      <div><label>Email</label><input type="email" name="email" placeholder="owner@example.com"></div>
      <div><label>Password</label><input type="text" name="password" placeholder="leave empty to generate one"></div>
      <div><label>Domain</label><input type="text" name="domain" placeholder="example.com"></div>
    </div>

    <details class="intake-bulk">
      <summary>Add several at once</summary>
      <p class="mute ed-note" style="margin-top:8px">One member per line: <code>email, password, domain</code>.
        Leave the password out — <code>email, domain</code> — and one gets generated. Tabs and
        semicolons work as separators too, so a column paste from a spreadsheet goes straight in.
        Lines starting with <code>#</code> are ignored. Anything typed in the boxes above is
        ignored while this has text in it.</p>
      <textarea name="bulk" rows="6" placeholder="owner@example.com, example.com
someone@shop.co.uk, Chosen-Pass-99, shop.co.uk"></textarea>
    </details>

    <button class="btn btn-primary">Add member(s)</button>
    <p class="form-note">Passwords are hashed on the way in. The one time they can be read is the
      green message after this form is submitted — copy them then, because no page here can show
      them again. A member who loses one resets it from the login page.</p>
  </form>
</div>

<div class="card card-pad ed-card">
  <h3>API</h3>
  <p class="mute ed-note">The same thing from somewhere else. It creates the account only; the
    listing is still built from the queue at the top of this page.</p>

  <div class="info-row intake-kv"><span class="mute">Endpoint</span><span><code>POST <?= e(site_url('/api/members')) ?></code></span></div>
  <div class="info-row intake-kv"><span class="mute">Header</span><span><code>X-Api-Key: <?= e($apiKey) ?></code></span></div>

  <pre class="intake-code">curl -X POST <?= e(site_url('/api/members')) ?> \
  -H "X-Api-Key: <?= e($apiKey) ?>" \
  -H "Content-Type: application/json" \
  -d '{"email":"owner@example.com","domain":"example.com"}'</pre>

  <p class="mute ed-note"><code>email</code> and <code>domain</code> are required. <code>password</code>
    is optional — leave it out and one is generated. The reply carries the password back, once:</p>
  <pre class="intake-code">{"ok":true,"id":42,"email":"owner@example.com","domain":"example.com",
 "password":"Kp7fRq2xMn4a","login":"<?= e(site_url('/login')) ?>"}</pre>

  <p class="mute ed-note">A duplicate email, a bad address or a domain that is not one comes back
    <code>422</code> with the reason. A wrong key is <code>401</code>. Anyone holding this key can
    create accounts on your site, so treat it as a password.</p>

  <form method="post" onsubmit="return confirm('Generate a new key? Anything using the old one stops working immediately.')"><?= csrf_field() ?>
    <input type="hidden" name="action" value="rotate_key">
    <button class="btn btn-sm btn-ghost">Generate a new key</button>
  </form>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
