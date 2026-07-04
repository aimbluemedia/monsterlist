<?php
// Staff area: /superadmin[/listings|/members|/claims|/reviews|/categories|/admins|/settings]
// Roles: admin = moderation + members; superadmin = admins + site settings too.
$site = setting('site_name');
$sub  = $segments[1] ?? 'dashboard';
$meta = ['title' => "Admin — $site", 'robots' => 'noindex'];

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
        redirect('/superadmin/listings' . (post('back') ? '?status=' . post('back') : ''));
    }
    $status = in_array($_GET['status'] ?? 'pending', ['pending','live','rejected','all'], true) ? ($_GET['status'] ?? 'pending') : 'pending';
    $where  = $status === 'all' ? '1=1' : 'b.status = ' . db()->quote($status);
    $page   = page_param();
    $offset = ($page - 1) * 30;
    $list = rows(
        "SELECT b.*, u.email AS owner_email, ci.name AS city_name, c.label AS category_label
         FROM businesses b
         LEFT JOIN users u ON u.id = b.owner_id
         LEFT JOIN cities ci ON ci.id = b.city_id
         LEFT JOIN categories c ON c.id = b.category_id
         WHERE $where ORDER BY b.created_at DESC LIMIT 30 OFFSET $offset");
    view_raw('admin/listings', compact('meta', 'u', 'list', 'status', 'page'));

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
        } elseif ($action === 'demote') {
            $t = row('SELECT * FROM users WHERE id = ? AND role = "admin"', [(int)post('id')]);
            if ($t) { q('UPDATE users SET role = "member" WHERE id = ?', [$t['id']]); flash_set('success', 'Admin demoted to member.'); }
        }
        redirect('/superadmin/admins');
    }
    $list = rows('SELECT * FROM users WHERE role IN ("admin","superadmin") ORDER BY role DESC, created_at');
    view_raw('admin/admins', compact('meta', 'u', 'list'));

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
