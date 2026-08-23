<?php
// Staff area: /superadmin[/listings|/members|/claims|/reviews|/categories|/admins|/settings]
// Roles: admin = moderation + members; superadmin = admins + site settings too.
$site = setting('site_name');
$sub  = $segments[1] ?? 'dashboard';
$meta = ['title' => "Admin — $site", 'robots' => 'noindex'];

/** Listings URL that keeps the active tab, search term and page together. */
function listings_url(string $status, ?string $term = '', int $page = 1): string
{
    $qs = ['status' => $status];
    if (($term = trim((string)$term)) !== '') $qs['q'] = $term;
    if ($page > 1) $qs['page'] = $page;
    return '/superadmin/listings?' . http_build_query($qs);
}

// Dedicated staff entrance — the only /superadmin route that doesn't require auth.
if ($sub === 'login') {
    if (is_admin()) redirect('/superadmin');
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (login_throttled()) {
            $errors[] = 'Too many failed attempts. Please wait 15 minutes and try again.';
        } else {
            $email = post('email');
            $pass  = (string)($_POST['password'] ?? '');
            $user  = row('SELECT * FROM users WHERE email = ?', [$email]);
            if ($user && $user['status'] === 'active'
                && in_array($user['role'], ['admin', 'superadmin'], true)
                && password_verify($pass, $user['password_hash'])) {
                login_user($user);
                $to = $_SESSION['after_login'] ?? '/superadmin';
                unset($_SESSION['after_login']);
                redirect($to);
            }
            record_failed_login($email);
            // Same message whether the account is wrong, not staff, or bad password.
            $errors[] = 'Invalid admin credentials.';
        }
    }
    $meta = ['title' => "Admin login — $site", 'robots' => 'noindex'];
    view_raw('admin/login', compact('meta', 'errors'));
    exit;
}

$u = require_admin();

if ($sub === 'dashboard') {
    $stats = [
        'pending'  => (int)scalar('SELECT COUNT(*) FROM businesses WHERE status = "pending"'),
        'live'     => (int)scalar('SELECT COUNT(*) FROM businesses WHERE status = "live"'),
        'members'  => (int)scalar('SELECT COUNT(*) FROM users WHERE role = "member"'),
        'paid'     => (int)scalar('SELECT COUNT(*) FROM users WHERE plan != "free" AND role = "member"'),
        'claims'   => (int)scalar('SELECT COUNT(*) FROM claims WHERE status = "pending"'),
        'promos'   => (int)scalar('SELECT COUNT(*) FROM promotions WHERE status = "pending"'),
        'reviews'  => (int)scalar('SELECT COUNT(*) FROM reviews WHERE created_at > (NOW() - INTERVAL 7 DAY)'),
        'views7'   => (int)scalar('SELECT COALESCE(SUM(count),0) FROM listing_events WHERE event = "view" AND day > (CURDATE() - INTERVAL 7 DAY)'),
    ];
    $pending = rows(
        'SELECT b.*, u.email AS owner_email, ci.name AS city_name, c.label AS category_label
         FROM businesses b
         LEFT JOIN users u ON u.id = b.owner_id
         LEFT JOIN cities ci ON ci.id = b.city_id
         LEFT JOIN categories c ON c.id = b.category_id
         WHERE b.status = "pending" ORDER BY b.created_at ASC LIMIT 10');
    view_raw('admin/dashboard', compact('meta', 'u', 'stats', 'pending'));

} elseif ($sub === 'listings' && ($segments[2] ?? '') === 'edit') {
    // Staff editing of ANY listing, claimed or not. Unlike the member form this
    // never bumps the listing back into moderation — staff edits are trusted —
    // and status, tier and owner are editable here too.
    $biz = row('SELECT * FROM businesses WHERE id = ?', [(int)($_GET['id'] ?? 0)]);
    if (!$biz) not_found();

    // Read the default first: the old form tested the ?? expression but then
    // returned $_GET['back'], which is null when the parameter is absent —
    // opening this page without &back= threw a TypeError in listings_url().
    $back = (string)($_GET['back'] ?? 'pending');
    if (!in_array($back, ['pending', 'live', 'rejected', 'all'], true)) $back = 'pending';
    $errors = [];

    // Staff get the full field set regardless of what the owner is paying for.
    $staffPlan = ['enhanced' => true, 'max_listings' => 0, 'label' => 'Staff',
                  'analytics' => true, 'profile' => true, 'concierge' => true];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        // Staff bypass the blocklist and duplicate check — they curate both.
        [$data, $errors] = listing_form_data($u, $staffPlan, (int)$biz['id'], false);

        $newStatus = in_array(post('status'), ['pending','live','rejected'], true) ? post('status') : $biz['status'];
        $newTier   = in_array(post('tier'), ['free','pro','featured'], true) ? post('tier') : $biz['tier'];
        $verified  = !empty($_POST['verified']) ? 1 : 0;

        // Reassign owner by email, or clear it to make the listing claimable.
        $ownerEmail = trim((string)post('owner_email'));
        $ownerId    = $biz['owner_id'];
        if ($ownerEmail === '') {
            $ownerId = null;
        } else {
            $owner = row('SELECT id FROM users WHERE email = ?', [$ownerEmail]);
            if ($owner) $ownerId = (int)$owner['id'];
            else $errors[] = 'No account found for "' . $ownerEmail . '". Leave the field empty to make the listing unclaimed.';
        }

        if (!$errors) {
            $slug = unique_business_slug($data['name'], (int)$data['city_id'], (int)$biz['id']);
            q('UPDATE businesses SET name=?, slug=?, category_id=?, business_type=?, city_id=?, tagline=?, description=?, profile=?, phone=?,
                      website=?, email=?, address=?, postcode=?, hours=?, founded=?, video_url=?, social=?, review_links=?,
                      status=?, tier=?, verified=?, owner_id=?
               WHERE id=?',
              [$data['name'], $slug, $data['category_id'], $data['business_type'], $data['city_id'], $data['tagline'], $data['description'],
               $data['profile'] ?? $biz['profile'],
               // $staffPlan is enhanced, so these are always present — the
               // fallback keeps this honest if that ever changes.
               $data['phone'] ?? $biz['phone'], $data['website'], $data['email'] ?? $biz['email'],
               $data['address'], $data['postcode'] ?? $biz['postcode'], $data['hours'] ?? $biz['hours'],
               $data['founded'],
               $data['video_url'] ?? $biz['video_url'], $data['social'] ?? $biz['social'],
               array_key_exists('review_links', $data) ? $data['review_links'] : $biz['review_links'],
               $newStatus, $newTier, $verified, $ownerId, $biz['id']]);

            $imgErrors = [];
            handle_listing_images((int)$biz['id'], $staffPlan, $imgErrors);
            foreach ($imgErrors as $ie) flash_set('error', $ie);

            if ($biz['city_id']) refresh_city_count((int)$biz['city_id']);
            if ((int)$data['city_id'] !== (int)$biz['city_id']) refresh_city_count((int)$data['city_id']);

            // Tell search engines about a listing that just became publicly visible.
            if ($newStatus === 'live' && $biz['status'] !== 'live') {
                $url = business_url_by_id((int)$biz['id']);
                if ($url) indexnow_ping([$url]);
            }
            flash_set('success', '"' . $data['name'] . '" updated.');
            redirect(listings_url($back, $_GET['q'] ?? ''));
        }
    }

    $gallery   = rows('SELECT id, url FROM gallery WHERE business_id = ? ORDER BY sort', [$biz['id']]);
    $countries = all_countries();
    $usStates  = regions_of('US');
    $cats      = categories_all();
    $cityRow   = $biz['city_id'] ? city_full((int)$biz['city_id']) : null;
    $owner     = $biz['owner_id'] ? row('SELECT email FROM users WHERE id = ?', [$biz['owner_id']]) : null;
    $meta      = ['title' => 'Edit listing — ' . $site, 'robots' => 'noindex'];
    view_raw('admin/listing-edit', compact('meta', 'u', 'biz', 'errors', 'countries', 'usStates', 'cats', 'gallery', 'cityRow', 'owner', 'back'));

} elseif ($sub === 'listings') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $biz = row('SELECT * FROM businesses WHERE id = ?', [(int)post('id')]);
        $action = post('action');
        if ($biz) {
            if ($action === 'approve') {
                q('UPDATE businesses SET status = "live", verified = IF(tier != "free", 1, verified) WHERE id = ?', [$biz['id']]);
                // Approving reverses an earlier rejection, so lift the blocks it
                // put in place — otherwise the owner is live but locked out.
                $lifted = listing_unblock($biz);
                notify_listing_decision($biz, true);
                $url = business_url_by_id((int)$biz['id']);
                if ($url) indexnow_ping([$url]);
                flash_set('success', '"' . $biz['name'] . '" is now live.'
                    . ($lifted ? ' Unblocked ' . implode(', ', $lifted) . '.' : ''));
            } elseif ($action === 'reject') {
                q('UPDATE businesses SET status = "rejected" WHERE id = ?', [$biz['id']]);
                // Rejecting bars the submitter from signing up again and bars
                // the domain from being resubmitted under a different address.
                $blocked = listing_block($biz, (int)$u['id']);
                notify_listing_decision($biz, false);
                flash_set('success', '"' . $biz['name'] . '" rejected.'
                    . ($blocked
                        ? ' Blocked ' . implode(', ', $blocked) . ' — manage at /superadmin/blocked.'
                        : ' Nothing to block: the listing has no owner, contact email or website.'));
            } elseif ($action === 'delete') {
                q('DELETE FROM businesses WHERE id = ?', [$biz['id']]);
                flash_set('success', 'Listing deleted.');
            } elseif ($action === 'verify') {
                q('UPDATE businesses SET verified = 1 - verified WHERE id = ?', [$biz['id']]);
            }
            if ($biz['city_id']) refresh_city_count((int)$biz['city_id']);
        }
        redirect(listings_url(post('back') ?: 'pending', post('q')));
    }
    $status = in_array($_GET['status'] ?? 'pending', ['pending','live','rejected','all'], true) ? ($_GET['status'] ?? 'pending') : 'pending';
    $term   = trim((string)($_GET['q'] ?? ''));

    $where  = [];
    $params = [];
    if ($status !== 'all') { $where[] = 'b.status = ?'; $params[] = $status; }
    if ($term !== '') {
        // Matches the owner's account email, the listing's own contact email and
        // its website. A pasted URL is reduced to its host first, so both
        // "https://acme.com/about" and "acme.com" find the same listing.
        $needle = preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $term);
        $needle = explode('/', $needle)[0];
        $needle = preg_replace('#^www\.#i', '', $needle);
        // % and _ are LIKE wildcards; a typed one should match itself.
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $needle) . '%';
        $where[]  = '(u.email LIKE ? OR b.email LIKE ? OR b.website LIKE ?)';
        $params[] = $like; $params[] = $like; $params[] = $like;
    }
    $sqlWhere = $where ? implode(' AND ', $where) : '1=1';

    $page   = page_param();
    $offset = ($page - 1) * 30;
    $joins  = 'FROM businesses b
               LEFT JOIN users u ON u.id = b.owner_id
               LEFT JOIN cities ci ON ci.id = b.city_id
               LEFT JOIN categories c ON c.id = b.category_id';
    $total = (int)scalar("SELECT COUNT(*) $joins WHERE $sqlWhere", $params);
    $list  = rows(
        "SELECT b.*, u.email AS owner_email, ci.name AS city_name, c.label AS category_label
         $joins WHERE $sqlWhere ORDER BY b.created_at DESC LIMIT 30 OFFSET $offset", $params);
    view_raw('admin/listings', compact('meta', 'u', 'list', 'status', 'page', 'term', 'total'));

} elseif ($sub === 'intake') {
    // Accounts created by us rather than by somebody filling in the sign-up
    // form: paste in an email, a password and a domain, and press a button to
    // have AI read that domain and build the listing.
    require_superadmin();
    if (!intake_ready()) {
        flash_set('error', 'Member intake needs a database upgrade — run database/upgrade-all.sql, then reload this page.');
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $action = post('action');

        if ($action === 'add') {
            // One typed-in member, or a pasted block of them. Both go through
            // the same create call, so both validate identically.
            $lines = trim((string)($_POST['bulk'] ?? '')) !== ''
                ? intake_parse_bulk((string)$_POST['bulk'])
                : [['email' => post('email'), 'password' => (string)($_POST['password'] ?? ''), 'domain' => post('domain')]];

            $made = [];
            foreach ($lines as $line) {
                if (isset($line['error'])) { flash_set('error', $line['error']); continue; }
                [$newUser, $errs] = intake_create_member($line['email'], $line['password'], $line['domain']);
                foreach ($errs as $e) flash_set('error', $e);
                if ($newUser) $made[] = $newUser['email'] . '  ' . $newUser['plain_password'];
            }
            if ($made) {
                // The only time these passwords are ever readable. They are
                // hashed in the database and no page can show them again, so
                // this message is the one chance to copy them out.
                flash_set('success', 'Added ' . count($made) . ' member' . (count($made) === 1 ? '' : 's')
                    . '. Copy the passwords now — they cannot be shown again: ' . implode(' · ', $made));
            }
            redirect('/superadmin/intake');
        }

        if ($action === 'adddomain') {
            // Superadmin only, and only onto a member who already exists — this
            // adds a website to an account, it does not create one.
            [$rowId, $err] = intake_add_domain((int)post('member_id'), post('domain'), (int)$u['id']);
            if ($rowId) {
                $who  = row('SELECT email, plan FROM users WHERE id = ?', [(int)post('member_id')]);
                $mPlan = plans()[$who['plan']] ?? plans()['free'];
                $owned = user_listing_count((int)post('member_id'));
                flash_set('success', 'Queued for ' . $who['email'] . '. Build it below.'
                    // Say it now rather than at the moment the build fails: the
                    // limit is on the plan, and staff can lift it or leave it.
                    . (!plan_has_room($mPlan, $owned)
                        ? ' Note: their ' . $mPlan['label'] . ' plan covers '
                          . strtolower(plan_listings_label($mPlan)) . ' and they already have '
                          . $owned . '. Move them to Pro or Premium first.'
                        : ''));
            } else {
                flash_set('error', $err);
            }
            // Back to the same member, so a second and third domain go in
            // without hunting for them again.
            redirect('/superadmin/intake?member=' . (int)post('member_id'));
        }

        if ($action === 'build') {
            $d = intake_domain((int)post('id'));
            if (!$d) {
                flash_set('error', 'No such queued domain.');
            } else {
                [$bizId, $err] = intake_build_listing($d);
                if ($bizId) {
                    flash_set('success', 'Listing built for ' . $d['domain']
                        . ' and left pending — check it before it goes live.');
                    redirect('/superadmin/listings/edit?id=' . $bizId . '&back=pending');
                }
                flash_set('error', 'Could not build ' . $d['domain'] . ': ' . $err);
            }
            redirect('/superadmin/intake');
        }

        if ($action === 'approve') {
            // The same approval the Listings page performs, offered here so a
            // built listing that needs no correction can be waved through
            // without a detour — and once it is live the row leaves the queue.
            $d   = intake_domain((int)post('id'));
            $biz = $d && $d['business_id'] ? row('SELECT * FROM businesses WHERE id = ?', [(int)$d['business_id']]) : null;
            if (!$biz) {
                flash_set('error', 'Nothing to approve — that domain has no listing yet.');
            } elseif ($biz['status'] === 'live') {
                flash_set('error', '"' . $biz['name'] . '" is already live.');
            } else {
                q('UPDATE businesses SET status = "live", verified = IF(tier != "free", 1, verified) WHERE id = ?', [$biz['id']]);
                $lifted = listing_unblock($biz);
                notify_listing_decision($biz, true);
                if ($biz['city_id']) refresh_city_count((int)$biz['city_id']);
                $url = business_url_by_id((int)$biz['id']);
                if ($url) indexnow_ping([$url]);
                flash_set('success', '"' . $biz['name'] . '" is now live and has left the queue.'
                    . ($lifted ? ' Unblocked ' . implode(', ', $lifted) . '.' : ''));
            }
            redirect('/superadmin/intake');
        }

        if ($action === 'delete') {
            // Drops the queued domain, not the account: a member can own
            // several, and the account belongs to the Members page, where
            // deleting one has the rest of its consequences to think about.
            $d = intake_domain((int)post('id'));
            if ($d && intake_remove_domain((int)$d['id'])) {
                flash_set('success', 'Removed ' . $d['domain'] . ' from the queue.');
            } else {
                flash_set('error', 'That domain already has a listing — edit or reject it instead.');
            }
            redirect('/superadmin/intake');
        }

        if ($action === 'rotate_key') {
            intake_api_key_rotate();
            flash_set('success', 'New API key generated. Anything using the old one stops working now.');
            redirect('/superadmin/intake');
        }
    }

    $queue   = intake_queue();
    $members = intake_members();
    $apiKey  = intake_ready() ? intake_api_key() : '';

    // Picking a member: by id from the dropdown, or by a search that can be an
    // email, a name or any domain they hold. A search matching exactly one
    // member selects them outright — making somebody click their own single
    // result is a step that exists only because the code could not decide.
    $mq      = trim((string)($_GET['mq'] ?? ''));
    $matches = $mq !== '' ? intake_find_members($mq) : [];
    $picked  = null;
    if (($pid = (int)($_GET['member'] ?? 0)) > 0) {
        $picked = row('SELECT * FROM users WHERE id = ? AND role = "member"', [$pid]);
    } elseif (count($matches) === 1) {
        $picked = row('SELECT * FROM users WHERE id = ?', [(int)$matches[0]['id']]);
    }
    $pickedDomains = $picked ? intake_member_domains((int)$picked['id']) : [];
    $pickedPlan    = $picked ? plan_for($picked) : null;
    $meta['title'] = "Member intake — $site";
    view_raw('admin/intake', compact('meta', 'u', 'queue', 'members', 'apiKey',
                                     'mq', 'matches', 'picked', 'pickedDomains', 'pickedPlan'));

} elseif ($sub === 'pro' || $sub === 'premium') {
    // The monthly service queues. "Premium" is the Featured plan wearing the
    // name we use out loud; nothing in the data is called premium.
    require_superadmin();
    $planKey   = $sub === 'premium' ? 'featured' : 'pro';
    $planLabel = $sub === 'premium' ? 'Premium' : 'Pro';

    if (!cycles_ready()) {
        flash_set('error', 'Monthly service queues need a database upgrade — run database/upgrade-all.sql.');
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $task = row('SELECT * FROM member_tasks WHERE id = ?', [(int)post('id')]);
        if (!$task) {
            flash_set('error', 'No such task.');
        } elseif (post('action') === 'save') {
            cycle_save((int)$task['id'], (array)($_POST['items'] ?? []), post('note'));
            $after = row('SELECT done_at FROM member_tasks WHERE id = ?', [(int)$task['id']]);
            flash_set('success', !empty($after['done_at'])
                ? 'Everything ticked — this member is done until next month.'
                : 'Saved.');
        } elseif (post('action') === 'done') {
            cycle_mark((int)$task['id'], true);
            flash_set('success', 'Marked done. They come back on their next renewal date.');
        } elseif (post('action') === 'reopen') {
            cycle_mark((int)$task['id'], false);
            flash_set('success', 'Reopened.');
        }
        redirect('/superadmin/' . $sub);
    }

    // Opening the page is what rolls everybody forward — there is no cron.
    cycle_roll_all($planKey);
    $due    = cycle_due($planKey);
    $roster = cycle_roster($planKey);
    $items  = cycle_items($planKey);
    $meta['title'] = "$planLabel members — $site";
    view_raw('admin/member-tasks', compact('meta', 'u', 'due', 'roster', 'items', 'planKey', 'planLabel'));

} elseif ($sub === 'diagnostics') {
    // Answers the two questions that follow every upload: is this build live,
    // and has the database caught up with it? Plus a count of what listings
    // actually hold, so "the storefront isn't showing X" can be traced to
    // missing data rather than guessed at from the outside.
    $checks = [];
    $checks[] = [
        'label'  => 'Blocklist table',
        'ok'     => table_exists('blocklist'),
        'fix'    => 'Import database/upgrade-all.sql',
        'detail' => 'Rejected emails and domains. Without it, Reject cannot block a re-signup.',
    ];
    $checks[] = [
        'label'  => 'users.website',
        'ok'     => column_exists('users', 'website'),
        'fix'    => 'Import database/upgrade-all.sql',
        'detail' => 'The domain given at sign-up, used to pre-fill the listing and drive AI fill.',
    ];
    $checks[] = [
        'label'  => 'businesses.review_links',
        'ok'     => column_exists('businesses', 'review_links'),
        'fix'    => 'Import database/upgrade-all.sql',
        'detail' => 'Review-site profiles from the setup wizard. Without it the “Our Reviews” card can never appear, and saving a listing fails.',
    ];

    $checks[] = [
        'label'  => 'token_events + articles',
        'ok'     => table_exists('token_events') && table_exists('articles')
                    && table_exists('promotion_views') && column_exists('users', 'token_balance')
                    && column_exists('businesses', 'profile'),
        'fix'    => 'Import database/upgrade-all.sql',
        'detail' => 'Tokens, the long-form Profile section and the monthly article queue. Without it the member area fails on load.',
    ];

    $checks[] = [
        'label'  => 'intake_domains + users.intake_at',
        'ok'     => intake_ready(),
        'fix'    => 'Import database/upgrade-all.sql',
        'detail' => 'Member intake — accounts and domains added by staff or through the API, and the queue their listings are built from.',
    ];

    $schemaOk = true;
    foreach ($checks as $c) { if (!$c['ok']) $schemaOk = false; }

    // ---- Stripe ----
    // Payments fail in ways nobody reports: a member reaches the card form,
    // something is wrong, and they close the tab. Every part of the setup is
    // checked here instead, and the two price ids are checked against Stripe
    // itself — a syntactically fine id for a price that does not exist, or a
    // product id pasted where a price id goes, looks perfect in Settings and
    // breaks only at the moment someone tries to pay.
    $stripe = [
        'mode'    => stripe_mode(),
        'webhook' => stripe_config('webhook_secret') !== '',
        'url'     => site_url('/stripe/webhook'),
        'prices'  => [],
    ];
    foreach (['pro' => 'stripe_price_pro', 'featured' => 'stripe_price_featured'] as $planKey => $settingKey) {
        $id   = setting($settingKey);
        $info = $id !== '' ? stripe_price_info($id) : null;
        $stripe['prices'][$planKey] = [
            'id'        => $id,
            'label'     => $info ? stripe_price_label($info) : '',
            'ok'        => $info !== null,
            'recurring' => $info !== null && !empty($info['recurring']),
            'live'      => $info !== null && !empty($info['livemode']),
            'active'    => $info === null || !empty($info['active']),
        ];
    }
    // A live-mode price under a test key (or the reverse) is the mistake that
    // survives every other check: both halves are real, they just are not from
    // the same Stripe.
    $stripe['mismatch'] = false;
    foreach ($stripe['prices'] as $p) {
        if ($p['ok'] && $stripe['mode'] !== '' && $p['live'] !== ($stripe['mode'] === 'live')) $stripe['mismatch'] = true;
    }
    $stripe['ready'] = stripe_configured() && $stripe['webhook'] && !$stripe['mismatch']
                    && $stripe['prices']['pro']['ok'] && $stripe['prices']['featured']['ok'];

    // What listings actually hold. A zero here explains an absent card far
    // faster than reading the storefront template does.
    $stats = ['live' => (int)scalar("SELECT COUNT(*) FROM businesses WHERE status = 'live'")];
    $stats['social'] = (int)scalar("SELECT COUNT(*) FROM businesses WHERE social IS NOT NULL AND social NOT IN ('', '[]', '{}')");
    $stats['reviews'] = column_exists('businesses', 'review_links')
        ? (int)scalar("SELECT COUNT(*) FROM businesses WHERE review_links IS NOT NULL AND review_links NOT IN ('', '[]', '{}')")
        : null;
    $stats['logos']    = (int)scalar("SELECT COUNT(*) FROM businesses WHERE logo_url IS NOT NULL AND logo_url != ''");
    $stats['services'] = (int)scalar('SELECT COUNT(DISTINCT business_id) FROM services');

    // Listing inspector. "The storefront isn't showing X" has two very
    // different causes — nothing is stored, or something is stored and a rule
    // hides it — and they look identical from the public page. This says which,
    // per card, for one listing, with the stored value in view.
    $probe   = trim((string)($_GET['listing'] ?? ''));
    $found   = null;
    $matches = [];
    $cards   = [];
    if ($probe !== '') {
        // "#12" or a bare number is an exact id. Otherwise every match is
        // listed rather than one silently chosen — demo data alone has four
        // listings called "Granite Works", and inspecting the wrong one while
        // believing it is the right one is the exact mistake this page exists
        // to prevent.
        $byId = ltrim($probe, '#');
        if (ctype_digit($byId)) {
            $found = row('SELECT * FROM businesses WHERE id = ?', [(int)$byId]);
        } else {
            $like    = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $probe) . '%';
            $matches = rows('SELECT * FROM businesses WHERE name LIKE ? OR website LIKE ? OR slug LIKE ?
                             ORDER BY id LIMIT 25', [$like, $like, $like]);
            if (count($matches) === 1) { $found = $matches[0]; $matches = []; }
        }
    }
    if ($found) {
        $enh      = tier_enhanced($found['tier']);
        $paid     = 'the tier is ' . $found['tier'] . ' — phone, email, logo, photos and video are paid features';
        $socialJs = json_decode((string)$found['social'], true);
        $socialJs = is_array($socialJs) ? array_filter($socialJs) : [];
        $revJs    = column_exists('businesses', 'review_links')
            ? array_filter(wizard_links($found['review_links'] ?? null)) : [];
        $svc      = (int)scalar('SELECT COUNT(*) FROM services WHERE business_id = ?', [$found['id']]);
        $pics     = (int)scalar('SELECT COUNT(*) FROM gallery WHERE business_id = ?', [$found['id']]);
        $revs     = (int)scalar("SELECT COUNT(*) FROM reviews WHERE business_id = ? AND status = 'live'", [$found['id']]);

        $card = function (string $name, bool $shown, string $why, string $value = '') use (&$cards) {
            $cards[] = ['name' => $name, 'shown' => $shown, 'why' => $why, 'value' => $value];
        };
        $card('Social Media', (bool)$socialJs,
            $socialJs ? count($socialJs) . ' link(s) stored' : 'nothing stored in the social column',
            (string)$found['social']);
        $card('Our Reviews', (bool)$revJs,
            !column_exists('businesses', 'review_links') ? 'the review_links column does not exist — import upgrade-v6.sql'
                : ($revJs ? count($revJs) . ' link(s) stored' : 'nothing stored in the review_links column'),
            (string)($found['review_links'] ?? ''));
        $card('Services', $svc > 0, $svc > 0 ? "$svc service(s) stored" : 'no services stored');
        $card('Reviews', $revs > 0, $revs > 0 ? "$revs live review(s)" : 'no live reviews — the card is left out');
        $card('Logo', $enh && $found['logo_url'], !$found['logo_url'] ? 'no logo uploaded' : ($enh ? 'uploaded' : $paid), (string)$found['logo_url']);
        $card('Contact · phone', $enh && $found['phone'], !$found['phone'] ? 'no phone stored' : ($enh ? 'stored' : $paid), (string)$found['phone']);
        $card('Contact · email', $enh && $found['email'], !$found['email'] ? 'no public email stored' : ($enh ? 'stored' : $paid), (string)$found['email']);
        $card('Photos', $enh && $pics > 0, $pics === 0 ? 'no photos uploaded' : ($enh ? "$pics photo(s)" : $paid));
        $card('Video', $enh && $found['video_url'], !$found['video_url'] ? 'no video URL stored' : ($enh ? 'stored' : $paid), (string)$found['video_url']);
    }

    $meta = ['title' => "Diagnostics — $site", 'robots' => 'noindex'];
    view_raw('admin/diagnostics', compact('meta', 'u', 'checks', 'schemaOk', 'stats', 'probe', 'found', 'matches', 'cards', 'stripe'));

} elseif ($sub === 'blocked') {
    // Emails barred from signing up and domains barred from being listed.
    // Rejecting a listing fills this automatically; this page is the undo.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (post('action') === 'remove') {
            blocklist_remove((int)post('id'));
            flash_set('success', 'Unblocked.');
        } else {
            $kind = post('kind') === 'domain' ? 'domain' : 'email';
            if (blocklist_add($kind, post('value'), post('reason') ?: 'Added by staff', (int)$u['id'])) {
                flash_set('success', 'Blocked ' . post('value') . '.');
            } else {
                flash_set('error', $kind === 'email'
                    ? 'That is not a valid email address.'
                    : 'That is not a valid domain — enter something like acme.com.');
            }
        }
        redirect('/superadmin/blocked');
    }
    $list = blocklist_all();
    $meta = ['title' => "Blocked — $site", 'robots' => 'noindex'];
    view_raw('admin/blocked', compact('meta', 'u', 'list'));

} elseif ($sub === 'promotions') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $promo  = row('SELECT * FROM promotions WHERE id = ?', [(int)post('id')]);
        $action = post('action');
        if ($promo) {
            if ($action === 'approve') {
                q('UPDATE promotions SET status = "live" WHERE id = ?', [$promo['id']]);
                flash_set('success', '"' . $promo['title'] . '" is live in the feed.');
            } elseif ($action === 'reject') {
                q('UPDATE promotions SET status = "rejected" WHERE id = ?', [$promo['id']]);
                flash_set('success', 'Promotion rejected.');
            } elseif ($action === 'delete') {
                q('DELETE FROM promotions WHERE id = ?', [$promo['id']]);
                flash_set('success', 'Promotion deleted.');
            }
        }
        redirect('/superadmin/promotions' . (post('back') ? '?status=' . post('back') : ''));
    }
    $status = in_array($_GET['status'] ?? 'pending', ['pending','live','rejected','all'], true) ? ($_GET['status'] ?? 'pending') : 'pending';
    $where  = $status === 'all' ? '1=1' : 'p.status = ' . db()->quote($status);
    $page   = page_param();
    $offset = ($page - 1) * 30;
    $list = rows(
        "SELECT p.*, b.name AS business_name, u.email AS owner_email
         FROM promotions p
         JOIN businesses b ON b.id = p.business_id
         JOIN users u ON u.id = p.user_id
         WHERE $where ORDER BY p.created_at DESC LIMIT 30 OFFSET $offset");
    view_raw('admin/promotions', compact('meta', 'u', 'list', 'status', 'page'));

} elseif ($sub === 'members' && ($segments[2] ?? '') === 'edit') {
    // One member, everything about them, and every listing they own. The list
    // page used to carry a plan dropdown per row; the plan belongs here with
    // the rest of the account, where changing it is a deliberate act rather
    // than a stray click while scrolling a table.
    $m = row('SELECT * FROM users WHERE id = ?', [(int)($_GET['id'] ?? 0)]);
    if (!$m) not_found();
    if ($m['role'] !== 'member' && !is_superadmin()) not_found();

    $listings = rows(
        'SELECT b.*, ci.name AS city_name, ci.slug AS city_slug, ci.country_code, r.slug AS region_slug,
                c.label AS category_label
           FROM businesses b
           LEFT JOIN cities ci ON ci.id = b.city_id
           LEFT JOIN regions r ON r.id = ci.region_id
           LEFT JOIN categories c ON c.id = b.category_id
          WHERE b.owner_id = ? ORDER BY b.created_at DESC', [(int)$m['id']]);

    $mPlan   = plan_for($m);
    $history = cycles_ready()
        ? rows('SELECT * FROM member_tasks WHERE user_id = ? ORDER BY month DESC LIMIT 12', [(int)$m['id']])
        : [];
    $meta['title'] = $m['email'] . " — $site";
    view_raw('admin/member-edit', compact('meta', 'u', 'm', 'listings', 'mPlan', 'history'));

} elseif ($sub === 'members') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $target = row('SELECT * FROM users WHERE id = ?', [(int)post('id')]);
        $action = post('action');
        // Admins cannot act on other admins/superadmins; only superadmin can.
        if ($target && ($target['role'] === 'member' || is_superadmin()) && (int)$target['id'] !== (int)$u['id']) {
            if ($action === 'suspend') {
                q('UPDATE users SET status = IF(status = "active", "suspended", "active") WHERE id = ?', [$target['id']]);
                flash_set('success', 'Member status toggled.');
            } elseif ($action === 'setplan' && in_array(post('plan'), ['free','pro','featured'], true)) {
                // Set by hand, so it is a comp: it gets a renewal date a month
                // out, opens its first service cycle today, and Stripe is not
                // allowed to take it away again.
                cycle_set_plan((int)$target['id'], post('plan'), true);
                flash_set('success', 'Plan set to ' . post('plan') . '.'
                    . (post('plan') === 'free'
                        ? ' Any open monthly task has been closed.'
                        : ' Renews ' . date('j M Y', strtotime(cycle_add_month(date('Y-m-d'))))
                          . ', and they are on this month\'s queue now.'));
            } elseif ($action === 'tokens') {
                $delta = (int)post('delta');
                if ($delta !== 0) {
                    token_adjust((int)$target['id'], $delta,
                        'Adjusted by ' . $u['name'] . (post('note') !== '' ? ': ' . mb_substr(post('note'), 0, 200) : ''));
                    flash_set('success', ($delta > 0 ? '+' : '') . $delta . ' tokens for ' . $target['email'] . '.');
                }
            } elseif ($action === 'delete') {
                q('DELETE FROM users WHERE id = ?', [$target['id']]);
                flash_set('success', 'Account deleted. Their listings remain, unclaimed.');
            }
        }
        // Back to wherever the button was: the member page for everything
        // except deleting the member, whose page no longer exists.
        $back = (int)post('back_to') === 1 && $action !== 'delete'
            ? '/superadmin/members/edit?id=' . (int)post('id')
            : '/superadmin/members';
        redirect($back);
    }
    $qstr = trim((string)($_GET['q'] ?? ''));
    $page = page_param();
    $offset = ($page - 1) * 30;
    if ($qstr !== '') {
        $like = '%' . $qstr . '%';
        $list = rows("SELECT u.*, (SELECT COUNT(*) FROM businesses WHERE owner_id = u.id) AS listing_count
                      FROM users u WHERE u.role = 'member' AND (u.email LIKE ? OR u.name LIKE ?)
                      ORDER BY u.created_at DESC LIMIT 30 OFFSET $offset", [$like, $like]);
    } else {
        $list = rows("SELECT u.*, (SELECT COUNT(*) FROM businesses WHERE owner_id = u.id) AS listing_count
                      FROM users u WHERE u.role = 'member'
                      ORDER BY u.created_at DESC LIMIT 30 OFFSET $offset");
    }
    $domains = member_domains_bulk(array_column($list, 'id'));
    view_raw('admin/members', compact('meta', 'u', 'list', 'qstr', 'page', 'domains'));

} elseif ($sub === 'claims') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $claim = row('SELECT * FROM claims WHERE id = ? AND status = "pending"', [(int)post('id')]);
        if ($claim) {
            $biz = row('SELECT * FROM businesses WHERE id = ?', [$claim['business_id']]);
            if (post('action') === 'approve' && $biz && empty($biz['owner_id'])) {
                $newOwner = row('SELECT * FROM users WHERE id = ?', [$claim['user_id']]);
                q('UPDATE businesses SET owner_id = ?, tier = ? WHERE id = ?',
                  [$claim['user_id'], $newOwner['plan'] ?? 'free', $biz['id']]);
                q('UPDATE claims SET status = "approved" WHERE id = ?', [$claim['id']]);
                q('UPDATE claims SET status = "rejected" WHERE business_id = ? AND status = "pending"', [$biz['id']]);
                notify_claim_decision($claim, $biz, true);
                flash_set('success', 'Claim approved — listing transferred.');
            } elseif (post('action') === 'reject') {
                q('UPDATE claims SET status = "rejected" WHERE id = ?', [$claim['id']]);
                if ($biz) notify_claim_decision($claim, $biz, false);
                flash_set('success', 'Claim rejected.');
            }
        }
        redirect('/superadmin/claims');
    }
    $list = rows(
        'SELECT cl.*, b.name AS business_name, u.name AS claimant_name, u.email AS claimant_email, u.plan AS claimant_plan
         FROM claims cl
         JOIN businesses b ON b.id = cl.business_id
         JOIN users u ON u.id = cl.user_id
         ORDER BY cl.status = "pending" DESC, cl.created_at DESC LIMIT 50');
    view_raw('admin/claims', compact('meta', 'u', 'list'));

} elseif ($sub === 'reviews') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $rev = row('SELECT * FROM reviews WHERE id = ?', [(int)post('id')]);
        if ($rev) {
            if (post('action') === 'toggle') {
                q('UPDATE reviews SET status = IF(status = "live", "hidden", "live") WHERE id = ?', [$rev['id']]);
            } elseif (post('action') === 'delete') {
                q('DELETE FROM reviews WHERE id = ?', [$rev['id']]);
            }
            refresh_rating((int)$rev['business_id']);
            flash_set('success', 'Review updated.');
        }
        redirect('/superadmin/reviews');
    }
    $page = page_param();
    $offset = ($page - 1) * 30;
    $list = rows(
        "SELECT r.*, b.name AS business_name
         FROM reviews r JOIN businesses b ON b.id = r.business_id
         ORDER BY r.created_at DESC LIMIT 30 OFFSET $offset");
    view_raw('admin/reviews', compact('meta', 'u', 'list', 'page'));

} elseif ($sub === 'categories') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $action = post('action');
        if ($action === 'add') {
            $id = slugify(post('id') ?: post('label'));
            $label = mb_substr(post('label'), 0, 120);
            if ($id !== '' && $label !== '' && !category_by_id($id)) {
                q('INSERT INTO categories (id, label, icon) VALUES (?,?,?)', [$id, $label, mb_substr(post('icon'), 0, 16)]);
                flash_set('success', 'Category added.');
            } else {
                flash_set('error', 'Invalid or duplicate category.');
            }
        } elseif ($action === 'delete') {
            $inUse = (int)scalar('SELECT COUNT(*) FROM businesses WHERE category_id = ?', [post('id')]);
            if ($inUse > 0) {
                flash_set('error', "Can't delete — $inUse listing(s) use this category.");
            } else {
                q('DELETE FROM categories WHERE id = ?', [post('id')]);
                flash_set('success', 'Category deleted.');
            }
        }
        redirect('/superadmin/categories');
    }
    $list = rows('SELECT c.*, (SELECT COUNT(*) FROM businesses WHERE category_id = c.id) AS in_use FROM categories c ORDER BY c.label');
    view_raw('admin/categories', compact('meta', 'u', 'list'));

} elseif ($sub === 'admins') {
    require_superadmin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $action = post('action');
        if ($action === 'add') {
            $email = filter_var(post('email'), FILTER_VALIDATE_EMAIL);
            $name  = mb_substr(post('name'), 0, 140);
            $pass  = (string)($_POST['password'] ?? '');
            if ($email && $name !== '' && strlen($pass) >= 8 && !row('SELECT id FROM users WHERE email = ?', [$email])) {
                q('INSERT INTO users (email, password_hash, name, role) VALUES (?,?,?,"admin")',
                  [$email, password_hash($pass, PASSWORD_DEFAULT), $name]);
                flash_set('success', 'Admin account created.');
            } else {
                flash_set('error', 'Check the fields — email must be unused, password 8+ characters.');
            }
        } elseif ($action === 'edit') {
            $t = row('SELECT * FROM users WHERE id = ? AND role IN ("admin","superadmin")', [(int)post('id')]);
            $email = filter_var(post('email'), FILTER_VALIDATE_EMAIL);
            $name  = mb_substr(post('name'), 0, 140);
            $role  = post('role') === 'superadmin' ? 'superadmin' : 'admin';
            $pass  = (string)($_POST['password'] ?? '');
            $taken = $email ? row('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $t['id'] ?? 0]) : null;

            if (!$t) {
                flash_set('error', 'Staff account not found.');
            } elseif (!$email || $name === '') {
                flash_set('error', 'Name and a valid email are both required.');
            } elseif ($taken) {
                flash_set('error', 'That email is already used by another account.');
            } elseif ($pass !== '' && strlen($pass) < 8) {
                flash_set('error', 'New password must be at least 8 characters.');
            } elseif ($t['role'] === 'superadmin' && $role !== 'superadmin' && last_superadmin((int)$t['id'])) {
                flash_set('error', "Can't change the role of the only superadmin.");
            } else {
                q('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?', [$name, $email, $role, $t['id']]);
                if ($pass !== '') {
                    q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $t['id']]);
                }
                $note = $pass !== '' ? ' Password changed.' : '';
                flash_set('success', 'Account updated.' . $note);
            }
        } elseif ($action === 'demote') {
            $t = row('SELECT * FROM users WHERE id = ? AND role IN ("admin","superadmin")', [(int)post('id')]);
            if (!$t) {
                flash_set('error', 'Staff account not found.');
            } elseif ((int)$t['id'] === (int)$u['id']) {
                flash_set('error', "You can't demote your own account.");
            } elseif (last_superadmin((int)$t['id'])) {
                flash_set('error', "Can't demote the only superadmin.");
            } else {
                q('UPDATE users SET role = "member" WHERE id = ?', [$t['id']]);
                flash_set('success', 'Account demoted to member.');
            }
        } elseif ($action === 'delete') {
            $t = row('SELECT * FROM users WHERE id = ? AND role IN ("admin","superadmin")', [(int)post('id')]);
            if (!$t) {
                flash_set('error', 'Staff account not found.');
            } elseif ((int)$t['id'] === (int)$u['id']) {
                flash_set('error', "You can't delete your own account.");
            } elseif (last_superadmin((int)$t['id'])) {
                flash_set('error', "Can't delete the only superadmin.");
            } else {
                q('UPDATE users SET role = "member", status = "suspended" WHERE id = ?', [$t['id']]);
                flash_set('success', 'Staff access revoked and the account suspended.');
            }
        }
        redirect('/superadmin/admins');
    }
    $list = rows('SELECT * FROM users WHERE role IN ("admin","superadmin") ORDER BY role DESC, created_at');
    $editId = (int)($_GET['edit'] ?? 0);
    $edit   = $editId ? row('SELECT * FROM users WHERE id = ? AND role IN ("admin","superadmin")', [$editId]) : null;
    view_raw('admin/admins', compact('meta', 'u', 'list', 'edit'));

} elseif ($sub === 'articles') {
    // The Featured tier's monthly articles: what members have briefed, and
    // where we are with writing and posting each one.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $a = row('SELECT * FROM articles WHERE id = ?', [(int)post('id')]);
        if ($a) {
            $status = in_array(post('status'), ['requested','writing','published'], true) ? post('status') : $a['status'];
            q('UPDATE articles SET status = ?, url = ?, staff_note = ? WHERE id = ?',
              [$status, clean_url(post('url')), mb_substr(post('staff_note'), 0, 500) ?: null, $a['id']]);
            flash_set('success', 'Article updated.');
        }
        redirect('/superadmin/articles');
    }
    $list = !articles_ready() ? [] : rows("SELECT a.*, u.email AS owner_email, u.name AS owner_name, b.name AS business_name
                  FROM articles a
                  JOIN users u ON u.id = a.user_id
                  LEFT JOIN businesses b ON b.id = a.business_id
                  ORDER BY FIELD(a.status,'requested','writing','published'), a.month DESC, a.id DESC
                  LIMIT 100");
    $meta = ['title' => "Articles — $site", 'robots' => 'noindex'];
    view_raw('admin/articles', compact('meta', 'u', 'list'));

} elseif ($sub === 'settings') {
    require_superadmin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        foreach (['site_name','site_tagline','price_pro_monthly','price_featured_monthly','stripe_price_pro','stripe_price_featured','anthropic_api_key',
                  'tokens_cost_promo',
                  'tokens_grant_free','tokens_grant_pro','tokens_grant_featured',
                  'tokens_earn_free','tokens_earn_pro','tokens_earn_featured',
                  'tokens_daily_free','tokens_daily_pro','tokens_daily_featured',
                  'promos_max_free','promos_max_pro','promos_max_featured',
                  'feed_boost_pro','feed_boost_featured'] as $k) {
            if (isset($_POST[$k])) setting_save($k, trim((string)$_POST[$k]));
        }
        flash_set('success', 'Settings saved.');
        redirect('/superadmin/settings');
    }
    view_raw('admin/settings', compact('meta', 'u'));

} else {
    not_found();
}
