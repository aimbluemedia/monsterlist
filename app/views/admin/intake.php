<?php require __DIR__ . '/_top.php'; ?>
<div class="section-head">
  <h1>Member intake</h1>
</div>

<div class="card card-pad ed-card">
  <?php // A queue holds work outstanding and nothing else. An approved member
        // is finished with, and is found on Listings and Members from then on. ?>
  <h3>Queue <span class="mute" style="font-weight:400">(<?= count($queue) ?>)</span></h3>
  <?php if (!$queue): ?>
    <p class="mute" style="margin:0">Nothing waiting. Domains added below — against a new member or an existing one —
      appear here until their listing is live.</p>
  <?php else: ?>
    <div class="table-wrap table-narrow">
      <table class="table">
        <tr><th>Domain</th><th>Member</th><th>Listing</th><th>Added</th><th>Manage</th></tr>
        <?php foreach ($queue as $r): ?>
          <tr>
            <?php // The domain leads: it is the unit of work now, and one member
                  // can own several rows in this table. ?>
            <td class="intake-domain"><strong><?= e((string)$r['domain']) ?></strong></td>
            <td>
              <a href="/superadmin/members/edit?id=<?= (int)$r['user_id'] ?>" style="color:var(--accent);font-weight:700"><?= e($r['email']) ?></a>
              <br><small class="mute"><?= e(plan_public_label((string)$r['plan'])) ?></small>
            </td>
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
                <?php if (!empty($r['note'])): ?>
                  <br><small class="intake-err"><?= e((string)$r['note']) ?></small>
                <?php endif; ?>
              <?php endif; ?>
            </td>
            <td class="mute"><?= e(date('M j, H:i', strtotime((string)$r['created_at']))) ?></td>
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
                            data-confirm="Read <?= e((string)$r['domain']) ?> with AI and build the listing?">Build listing</button>
                  </form>
                <?php endif; ?>
                <a class="btn btn-sm btn-ghost" href="/superadmin/members/edit?id=<?= (int)$r['user_id'] ?>">Member</a>
                <?php // The site itself, before deciding whether to spend an AI
                      // call on it — and the first thing worth looking at when a
                      // build came back with an error. New tab, because losing
                      // the queue to a parked domain would be irritating.
                      //
                      // rel="noopener" is the point of the rel here: without it
                      // the opened page gets a handle on this one through
                      // window.opener and can navigate it somewhere else.
                      $siteUrl = clean_url((string)$r['domain']); ?>
                <?php if ($siteUrl): ?>
                  <a class="btn btn-sm btn-ghost" href="<?= e($siteUrl) ?>"
                     target="_blank" rel="noopener noreferrer">View website</a>
                <?php endif; ?>
                <?php if (!$r['business_id']): ?>
                  <form method="post"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm intake-del" data-confirm="Remove <?= e((string)$r['domain']) ?> from the queue?">Remove</button>
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

<div class="card card-pad ed-card" id="adddomain">
  <h3>Add a domain to a member</h3>
  <p class="mute ed-note">For a member who already has an account — a second website, or one they
    told you about after signing up. Search by email or by any domain they already hold, or pick
    them from the list. This adds the domain to the queue above; nothing is read from it until you
    press <strong>Build listing</strong>.</p>

  <?php // Two ways to the same member. The search reaches domains as well as
        // emails, because "which account owns acme.com?" is the question staff
        // actually arrive with. ?>
  <form method="get" class="ad-find">
    <div>
      <label>Search</label>
      <input type="text" name="mq" value="<?= e($mq) ?>" placeholder="email, name, or a domain they hold">
    </div>
    <div>
      <label>…or choose</label>
      <select name="member" onchange="this.form.submit()">
        <option value="">Choose a member…</option>
        <?php foreach ($members as $mem): ?>
          <option value="<?= (int)$mem['id'] ?>" <?= $picked && (int)$picked['id'] === (int)$mem['id'] ? 'selected' : '' ?>>
            <?= e($mem['email']) ?>
            — <?= e(plan_public_label((string)$mem['plan'])) ?>,
            <?= (int)$mem['listing_count'] ?> listing<?= (int)$mem['listing_count'] === 1 ? '' : 's' ?><?php
              if ((int)$mem['queued']) echo ', ' . (int)$mem['queued'] . ' queued'; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;align-items:flex-end;gap:8px">
      <button class="btn btn-primary">Find</button>
      <?php if ($picked || $mq !== ''): ?>
        <a class="btn btn-ghost" href="/superadmin/intake">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($mq !== '' && !$matches): ?>
    <p class="flash flash-error" style="margin:14px 0 0">Nothing matched "<?= e($mq) ?>" — no member
      with that email or name, and no account holding that domain.</p>
  <?php endif; ?>

  <?php // Several hits: choose. One hit selected itself further up. ?>
  <?php if (count($matches) > 1 && !$picked): ?>
    <p class="mute ed-note" style="margin-top:14px"><?= count($matches) ?> members matched. Pick one:</p>
    <div class="table-wrap table-narrow">
      <table class="table">
        <tr><th>Member</th><th>Plan</th><th>Listings</th><th>Queued</th><th></th></tr>
        <?php foreach ($matches as $mm): ?>
          <tr>
            <td><strong><?= e($mm['email']) ?></strong></td>
            <td><?= e(plan_public_label((string)$mm['plan'])) ?></td>
            <td><?= (int)$mm['listing_count'] ?></td>
            <td><?= (int)$mm['queued'] ?></td>
            <td><a class="btn btn-sm btn-primary" href="/superadmin/intake?member=<?= (int)$mm['id'] ?>#adddomain">Select</a></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($picked): ?>
    <?php $owned = user_listing_count((int)$picked['id']); $room = plan_has_room($pickedPlan, $owned); ?>
    <div class="ad-member">
      <div class="ad-member-head">
        <div>
          <b><?= e($picked['email']) ?></b>
          <div class="mute" style="font-size:.82rem">
            <?= e(plan_public_label((string)$picked['plan'])) ?> plan ·
            <?= e(plan_listings_label($pickedPlan)) ?> · <?= (int)$owned ?> in use
          </div>
        </div>
        <a class="btn btn-sm btn-ghost" href="/superadmin/members/edit?id=<?= (int)$picked['id'] ?>">Member page</a>
      </div>

      <?php // Everything they already hold, so the same website is not queued
            // twice under two spellings of the same idea. ?>
      <p class="ad-sub">Domains on this account (<?= count($pickedDomains) ?>)</p>
      <?php if (!$pickedDomains): ?>
        <p class="mute" style="margin:0 0 12px">None yet.</p>
      <?php else: ?>
        <div class="table-wrap table-narrow">
          <table class="table">
            <tr><th>Domain</th><th>State</th><th>Listing</th><th></th></tr>
            <?php foreach ($pickedDomains as $d): ?>
              <tr>
                <td class="intake-domain"><strong><?= e($d['domain']) ?></strong></td>
                <td><span class="badge badge-<?= $d['state'] === 'queued' ? 'pending' : e($d['state']) ?>"><?= e($d['state']) ?></span></td>
                <td>
                  <?= $d['listing'] ? e($d['listing']) : '<span class="mute">—</span>' ?>
                  <?php if (!empty($d['note'])): ?><br><small class="intake-err"><?= e((string)$d['note']) ?></small><?php endif; ?>
                </td>
                <td style="white-space:nowrap">
                  <?php if ($d['biz_id']): ?>
                    <a class="btn btn-sm btn-ghost" href="/superadmin/listings/edit?id=<?= (int)$d['biz_id'] ?>&back=all">Edit listing</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>

      <?php if (!$room): ?>
        <p class="flash flash-error" style="margin:0 0 12px">Their <?= e($pickedPlan['label']) ?> plan
          covers <?= e(strtolower(plan_listings_label($pickedPlan))) ?> and <?= (int)$owned ?> is in use.
          You can still queue a domain, but move them to Pro or Premium before it goes live.</p>
      <?php endif; ?>

      <form method="post" class="ad-add"><?= csrf_field() ?>
        <input type="hidden" name="action" value="adddomain">
        <input type="hidden" name="member_id" value="<?= (int)$picked['id'] ?>">
        <div>
          <label>New domain for <?= e($picked['email']) ?></label>
          <input type="text" name="domain" placeholder="theirsecondsite.com" required autofocus>
        </div>
        <div style="display:flex;align-items:flex-end">
          <button class="btn btn-primary btn-block">Add to queue</button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <p class="form-note">Refused if the domain is already queued, already a listing, or registered to a
    different account — all three would end in two storefronts for one business.</p>
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
