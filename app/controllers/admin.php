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

    $back   = in_array($_GET['back'] ?? 'pending', ['pending','live','rejected','all'], true) ? $_GET['back'] : 'pending';
    $errors = [];

    // Staff get the full field set regardless of what the owner is paying for.
    $staffPlan = ['enhanced' => true, 'max_listings' => PHP_INT_MAX, 'label' => 'Staff', 'analytics' => true];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        [$data, $errors] = listing_form_data($u, $staffPlan);

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
            q('UPDATE businesses SET name=?, slug=?, category_id=?, city_id=?, tagline=?, description=?, phone=?,
                      website=?, email=?, address=?, founded=?, video_url=?, social=?, status=?, tier=?, verified=?, owner_id=?
               WHERE id=?',
              [$data['name'], $slug, $data['category_id'], $data['city_id'], $data['tagline'], $data['description'],
               $data['phone'], $data['website'], $data['email'], $data['address'], $data['founded'],
               $data['video_url'] ?? $biz['video_url'], $data['social'] ?? $biz['social'],
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
                notify_listing_decision($biz, true);
                $url = business_url_by_id((int)$biz['id']);
                if ($url) indexnow_ping([$url]);
                flash_set('success', '"' . $biz['name'] . '" is now live.');
            } elseif ($action === 'reject') {
                q('UPDATE businesses SET status = "rejected" WHERE id = ?', [$biz['id']]);
                notify_listing_decision($biz, false);
                flash_set('success', '"' . $biz['name'] . '" rejected.');
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
                q('UPDATE users SET plan = ? WHERE id = ?', [post('plan'), $target['id']]);
                sync_business_tiers((int)$target['id'], post('plan'));
                flash_set('success', 'Plan updated to ' . post('plan') . '.');
            } elseif ($action === 'delete') {
                q('DELETE FROM users WHERE id = ?', [$target['id']]);
                flash_set('success', 'Account deleted. Their listings remain, unclaimed.');
            }
        }
        redirect('/superadmin/members');
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
    view_raw('admin/members', compact('meta', 'u', 'list', 'qstr', 'page'));

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

} elseif ($sub === 'settings') {
    require_superadmin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        foreach (['site_name','site_tagline','price_pro_monthly','price_featured_monthly','stripe_price_pro','stripe_price_featured','anthropic_api_key'] as $k) {
            if (isset($_POST[$k])) setting_save($k, trim((string)$_POST[$k]));
        }
        flash_set('success', 'Settings saved.');
        redirect('/superadmin/settings');
    }
    view_raw('admin/settings', compact('meta', 'u'));

} else {
    not_found();
}
