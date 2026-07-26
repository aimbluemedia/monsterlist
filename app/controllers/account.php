<?php
// Member area: /account[/listings|/listings/new|/listings/edit|/listings/delete
//                        |/analytics|/billing|/settings]
$u    = require_login();
$site = setting('site_name');
$plan = plan_for($u);

$sub = $path === '/account' ? 'dashboard' : ($segments[1] ?? 'dashboard');
$act = $segments[2] ?? '';

// ---------- helpers ----------

function own_business(int $id, int $userId): ?array
{
    return row('SELECT * FROM businesses WHERE id = ? AND owner_id = ?', [$id, $userId]);
}

// ---------- routes ----------

$meta = ['title' => "My account — $site", 'robots' => 'noindex'];

if ($sub === 'dashboard') {
    $listings = rows('SELECT b.*, ci.name AS city_name FROM businesses b LEFT JOIN cities ci ON ci.id = b.city_id WHERE b.owner_id = ? ORDER BY b.created_at DESC', [$u['id']]);
    $stats = row(
        'SELECT COALESCE(SUM(CASE WHEN event = "view" THEN count END),0) views,
                COALESCE(SUM(CASE WHEN event = "click" THEN count END),0) clicks
         FROM listing_events le JOIN businesses b ON b.id = le.business_id
         WHERE b.owner_id = ? AND le.day > (CURDATE() - INTERVAL 30 DAY)', [$u['id']]
    );
    view('account/dashboard', compact('meta', 'u', 'plan', 'listings', 'stats'));

} elseif ($sub === 'listings' && $act === '') {
    $listings = rows(
        'SELECT b.*, ci.name AS city_name, ci.slug AS city_slug, ci.country_code, ci.region_id, r.slug AS region_slug,
                c.label AS category_label
         FROM businesses b
         LEFT JOIN cities ci ON ci.id = b.city_id
         LEFT JOIN regions r ON r.id = ci.region_id
         LEFT JOIN categories c ON c.id = b.category_id
         WHERE b.owner_id = ? ORDER BY b.created_at DESC', [$u['id']]);
    view('account/listings', compact('meta', 'u', 'plan', 'listings'));

} elseif ($sub === 'listings' && $act === 'autofill' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // AJAX: fetch the member's website and let Claude pre-fill the listing form.
    header('Content-Type: application/json; charset=UTF-8');
    csrf_check();

    // simple per-session throttle: 10 autofills per hour
    $_SESSION['ai_fills'] = array_filter($_SESSION['ai_fills'] ?? [], fn($t) => $t > time() - 3600);
    if (count($_SESSION['ai_fills']) >= 10) {
        echo json_encode(['ok' => false, 'error' => 'AI fill limit reached — try again in a little while.']);
        exit;
    }

    $url = trim((string)post('url'));
    if ($url === '') { echo json_encode(['ok' => false, 'error' => 'Please enter your website address.']); exit; }

    $fields = ai_extract_listing($url, $aiError);
    if ($fields === null) {
        echo json_encode(['ok' => false, 'error' => $aiError ?: 'Something went wrong — please fill the form manually.']);
        exit;
    }
    $_SESSION['ai_fills'][] = time();
    echo json_encode(['ok' => true, 'fields' => $fields], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;

} elseif ($sub === 'listings' && $act === 'new') {
    if (!user_can_add_listing($u)) {
        flash_set('error', 'Your ' . $plan['label'] . ' plan allows ' . $plan['max_listings'] . ' listing(s). Upgrade to add more.');
        redirect('/pricing');
    }
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        [$data, $errors] = listing_form_data($u, $plan);
        if (!$errors) {
            $slug = unique_business_slug($data['name'], (int)$data['city_id']);
            q('INSERT INTO businesses (owner_id, name, slug, category_id, city_id, tier, status, tagline, description, phone, website, email, address, founded, video_url, social)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
              [$u['id'], $data['name'], $slug, $data['category_id'], $data['city_id'], $u['plan'], 'pending',
               $data['tagline'], $data['description'], $data['phone'], $data['website'], $data['email'],
               $data['address'], $data['founded'], $data['video_url'] ?? null, $data['social'] ?? null]);
            $bizId = (int)db()->lastInsertId();
            $imgErrors = [];
            handle_listing_images($bizId, $plan, $imgErrors);
            foreach ($imgErrors as $ie) flash_set('error', $ie);
            flash_set('success', 'Listing submitted! It will appear publicly once approved by our team (usually within 24 hours).');
            redirect('/account/listings');
        }
    }
    $biz = null;
    $countries = all_countries();
    $usStates  = regions_of('US');
    $cats      = categories_all();
    view('account/listing-form', compact('meta', 'u', 'plan', 'errors', 'biz', 'countries', 'usStates', 'cats'));

} elseif ($sub === 'listings' && $act === 'edit') {
    $biz = own_business((int)($_GET['id'] ?? 0), (int)$u['id']);
    if (!$biz) not_found();
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        [$data, $errors] = listing_form_data($u, $plan);
        if (!$errors) {
            $slug = unique_business_slug($data['name'], (int)$data['city_id'], (int)$biz['id']);
            // Edits go back to moderation only if core public fields changed
            $needsReview = $data['name'] !== $biz['name'] || $data['description'] !== (string)$biz['description'];
            q('UPDATE businesses SET name=?, slug=?, category_id=?, city_id=?, tagline=?, description=?, phone=?, website=?, email=?, address=?, founded=?, video_url=?, social=?, status=?
               WHERE id=? AND owner_id=?',
              [$data['name'], $slug, $data['category_id'], $data['city_id'], $data['tagline'], $data['description'],
               $data['phone'], $data['website'], $data['email'], $data['address'], $data['founded'],
               $data['video_url'] ?? $biz['video_url'], $data['social'] ?? $biz['social'],
               $needsReview && $biz['status'] === 'live' ? 'pending' : $biz['status'],
               $biz['id'], $u['id']]);
            $imgErrors = [];
            handle_listing_images((int)$biz['id'], $plan, $imgErrors);
            foreach ($imgErrors as $ie) flash_set('error', $ie);
            refresh_city_count((int)$biz['city_id']);
            if ((int)$data['city_id'] !== (int)$biz['city_id']) refresh_city_count((int)$data['city_id']);
            flash_set('success', 'Listing updated.' . ($needsReview ? ' Changes will be re-reviewed before going live.' : ''));
            redirect('/account/listings');
        }
    }
    $gallery   = rows('SELECT id, url FROM gallery WHERE business_id = ? ORDER BY sort', [$biz['id']]);
    $countries = all_countries();
    $usStates  = regions_of('US');
    $cats      = categories_all();
    $cityRow   = $biz['city_id'] ? city_full((int)$biz['city_id']) : null;
    view('account/listing-form', compact('meta', 'u', 'plan', 'errors', 'biz', 'countries', 'usStates', 'cats', 'gallery', 'cityRow'));

} elseif ($sub === 'listings' && $act === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $biz = own_business((int)post('id'), (int)$u['id']);
    if ($biz) {
        q('DELETE FROM businesses WHERE id = ? AND owner_id = ?', [$biz['id'], $u['id']]);
        if ($biz['city_id']) refresh_city_count((int)$biz['city_id']);
        flash_set('success', 'Listing deleted.');
    }
    redirect('/account/listings');

} elseif ($sub === 'analytics') {
    if (!$plan['analytics']) {
        view('account/analytics-upsell', compact('meta', 'u', 'plan'));
    } else {
        $rows30 = rows(
            'SELECT b.id, b.name, le.event, SUM(le.count) total
             FROM businesses b
             LEFT JOIN listing_events le ON le.business_id = b.id AND le.day > (CURDATE() - INTERVAL 30 DAY)
             WHERE b.owner_id = ?
             GROUP BY b.id, b.name, le.event
             ORDER BY b.name', [$u['id']]);
        $byBiz = [];
        foreach ($rows30 as $r) {
            $byBiz[$r['id']]['name'] = $r['name'];
            if ($r['event']) $byBiz[$r['id']][$r['event']] = (int)$r['total'];
        }
        view('account/analytics', compact('meta', 'u', 'plan', 'byBiz'));
    }

} elseif ($sub === 'billing') {
    $subRow = row('SELECT * FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$u['id']]);
    if (isset($_GET['upgraded'])) flash_set('success', 'Payment received — welcome aboard! Your plan updates within a few seconds of Stripe confirming.');
    view('account/billing', compact('meta', 'u', 'plan', 'subRow'));

} elseif ($sub === 'settings') {
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $name = mb_substr(post('name'), 0, 140);
        if ($name !== '') q('UPDATE users SET name = ? WHERE id = ?', [$name, $u['id']]);
        $new = (string)($_POST['new_password'] ?? '');
        if ($new !== '') {
            if (!password_verify((string)($_POST['current_password'] ?? ''), $u['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($new) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } else {
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            }
        }
        if (!$errors) { flash_set('success', 'Settings saved.'); redirect('/account/settings'); }
    }
    view('account/settings', compact('meta', 'u', 'plan', 'errors'));

} else {
    not_found();
}
