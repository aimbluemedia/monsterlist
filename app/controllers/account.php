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

/** Find-or-create the city for a submitted location. Returns city id or null. */
function resolve_city(string $countryCode, string $regionSlug, string $cityName): ?int
{
    $country = row('SELECT * FROM countries WHERE code = ?', [$countryCode]);
    if (!$country || $cityName === '') return null;

    $regionId = null;
    if ($country['code'] === 'US') {
        if ($regionSlug === '') return null;
        $region = region_by_slug('US', $regionSlug);
        if (!$region) return null;
        $regionId = (int)$region['id'];
    }
    $slug = slugify($cityName);
    if ($slug === '') return null;

    $existing = $regionId
        ? city_in_region($regionId, $slug)
        : city_in_country($country['code'], $slug);
    if ($existing) return (int)$existing['id'];

    q('INSERT INTO cities (country_code, region_id, name, slug) VALUES (?,?,?,?)',
      [$country['code'], $regionId, mb_substr(trim($cityName), 0, 140), $slug]);
    return (int)db()->lastInsertId();
}

/** Read + validate the listing form. Returns [data, errors]. */
function listing_form_data(array $user, array $plan): array
{
    $errors = [];
    $name = mb_substr(post('name'), 0, 180);
    if ($name === '') $errors[] = 'Business name is required.';

    $catId = post('category_id');
    if ($catId === '' || !category_by_id($catId)) $errors[] = 'Please choose a category.';

    $cityId = resolve_city(strtoupper(post('country')), post('region'), post('city'));
    if (!$cityId) $errors[] = 'Please choose a valid country' . (strtoupper(post('country')) === 'US' ? ', state' : '') . ' and city.';

    $maxDesc = $plan['enhanced'] ? 5000 : 300;
    $data = [
        'name'        => $name,
        'category_id' => $catId,
        'city_id'     => $cityId,
        'tagline'     => mb_substr(post('tagline'), 0, 255),
        'description' => mb_substr(post('description'), 0, $maxDesc),
        'phone'       => mb_substr(post('phone'), 0, 40),
        'website'     => clean_url(post('website')),
        'email'       => filter_var(post('email'), FILTER_VALIDATE_EMAIL) ?: null,
        'address'     => mb_substr(post('address'), 0, 255),
        'founded'     => (int)post('founded') ?: null,
    ];
    if ($plan['enhanced']) {
        $data['video_url'] = clean_url(post('video_url'));
        $social = [];
        foreach (['facebook','instagram','tiktok','youtube','pinterest','linkedin','x'] as $net) {
            $v = clean_url(post('social_' . $net));
            if ($v) $social[$net] = $v;
        }
        $data['social'] = $social ? json_encode($social) : null;
        $urls = array_filter(array_map('clean_url', array_slice(preg_split('/\R+/', post('gallery_urls')) ?: [], 0, 6)));
        $data['_gallery'] = array_values($urls);
    }
    return [$data, $errors];
}

function save_gallery(int $bizId, array $urls): void
{
    q('DELETE FROM gallery WHERE business_id = ?', [$bizId]);
    foreach ($urls as $i => $url) {
        q('INSERT INTO gallery (business_id, url, sort) VALUES (?,?,?)', [$bizId, $url, $i]);
    }
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
            if (!empty($data['_gallery'])) save_gallery($bizId, $data['_gallery']);
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
            if (isset($data['_gallery'])) save_gallery((int)$biz['id'], $data['_gallery']);
            refresh_city_count((int)$biz['city_id']);
            if ((int)$data['city_id'] !== (int)$biz['city_id']) refresh_city_count((int)$data['city_id']);
            flash_set('success', 'Listing updated.' . ($needsReview ? ' Changes will be re-reviewed before going live.' : ''));
            redirect('/account/listings');
        }
    }
    $gallery   = rows('SELECT url FROM gallery WHERE business_id = ? ORDER BY sort', [$biz['id']]);
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
