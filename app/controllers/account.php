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

// This month's token allowance, handed out on first sight rather than by a
// scheduled job — shared-hosting cron is the least dependable part of this
// stack, and the ledger's unique key makes repeating the attempt free.
token_grant_monthly($u);
$u['token_balance'] = token_balance((int)$u['id']);

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

    // The field is read-only in the browser, which is presentation, not
    // enforcement. When the account has a registered domain that is the only
    // site we will read, whatever the request body says.
    $url = !empty($u['website']) ? (string)$u['website'] : trim((string)post('url'));
    if ($url === '') { echo json_encode(['ok' => false, 'error' => 'Please enter your website address.']); exit; }

    $fields = ai_extract_listing($url, $aiError);
    if ($fields === null) {
        echo json_encode(['ok' => false, 'error' => $aiError ?: 'Something went wrong — please fill the form manually.']);
        exit;
    }
    $_SESSION['ai_fills'][] = time();
    // Hold the suggested services for the wizard's next step. They are not form
    // fields, so they would otherwise be thrown away on submit.
    $_SESSION['ai_services'] = $fields['services'] ?? [];
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
            q('INSERT INTO businesses (owner_id, name, slug, category_id, city_id, tier, status, tagline, description, profile, phone, website, email, address, founded, video_url, social, review_links)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
              [$u['id'], $data['name'], $slug, $data['category_id'], $data['city_id'], $u['plan'], 'pending',
               $data['tagline'], $data['description'], $data['profile'] ?? null,
               $data['phone'] ?? null, $data['website'], $data['email'] ?? null,
               $data['address'], $data['founded'], $data['video_url'] ?? null, $data['social'] ?? null,
               $data['review_links'] ?? null]);
            $bizId = (int)db()->lastInsertId();
            $imgErrors = [];
            handle_listing_images($bizId, $plan, $imgErrors);
            foreach ($imgErrors as $ie) flash_set('error', $ie);
            flash_set('success', 'Listing saved. Three quick steps to finish your profile — skip any of them.');
            redirect(wizard_url('services', $bizId));
        }
    }
    // Prefill the website with the domain given at signup — it is the whole
    // reason we asked for it, and retyping it is friction for no gain. This is
    // NOT $biz: a non-null $biz flips the shared form into edit mode.
    $biz     = null;
    $prefill = !empty($u['website']) ? ['website' => $u['website']] : [];
    $countries = all_countries();
    $usStates  = regions_of('US');
    $cats      = categories_all();
    view('account/listing-form', compact('meta', 'u', 'plan', 'errors', 'biz', 'prefill', 'countries', 'usStates', 'cats'));

} elseif ($sub === 'listings' && $act === 'edit') {
    $biz = own_business((int)($_GET['id'] ?? 0), (int)$u['id']);
    if (!$biz) not_found();
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        [$data, $errors] = listing_form_data($u, $plan, (int)$biz['id']);
        if (!$errors) {
            $slug = unique_business_slug($data['name'], (int)$data['city_id'], (int)$biz['id']);
            // Edits go back to moderation only if core public fields changed
            $needsReview = $data['name'] !== $biz['name'] || $data['description'] !== (string)$biz['description'];
            q('UPDATE businesses SET name=?, slug=?, category_id=?, city_id=?, tagline=?, description=?, profile=?, phone=?, website=?, email=?, address=?, founded=?, video_url=?, social=?, review_links=?, status=?
               WHERE id=? AND owner_id=?',
              [$data['name'], $slug, $data['category_id'], $data['city_id'], $data['tagline'], $data['description'],
               $data['profile'] ?? $biz['profile'],
               // Paid-only fields are absent from $data on a free plan — keep
               // whatever is stored rather than blanking it, so an upgrade
               // brings the old phone and email back instead of nothing.
               $data['phone'] ?? $biz['phone'], $data['website'], $data['email'] ?? $biz['email'],
               $data['address'], $data['founded'],
               $data['video_url'] ?? $biz['video_url'], $data['social'] ?? $biz['social'],
               $data['review_links'] ?? $biz['review_links'],
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

} elseif ($sub === 'listings' && in_array($act, ['services', 'social', 'reviews'], true)) {
    // Setup wizard, run once after a listing is created and reachable again
    // later from "My listings". Every step can be skipped.
    $biz = own_business((int)($_GET['id'] ?? 0), (int)$u['id']);
    if (!$biz) not_found();
    $step = $act;
    $next = wizard_next($step);
    $done = $next === null ? '/account/listings' : wizard_url($next, (int)$biz['id']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        // Build one message for the whole step. Two flash_set calls on the last
        // step stacked "Saved 2 links." above "Profile complete!", which reads
        // as the page repeating itself.
        $said = '';
        if (post('action') !== 'skip') {
            if ($step === 'services') {
                // Selected bubbles and typed-in boxes are the same list.
                $picked = array_merge((array)($_POST['services'] ?? []), (array)($_POST['custom'] ?? []));
                $n = wizard_save_services((int)$biz['id'], $picked);
                $said = $n ? "Saved $n service" . ($n === 1 ? '' : 's') . '.' : 'No services saved.';
            } else {
                $isSocial = $step === 'social';
                $n = wizard_save_links(
                    (int)$biz['id'],
                    $isSocial ? 'social' : 'review_links',
                    $isSocial ? wizard_socials() : wizard_reviews(),
                    (array)($_POST['links'] ?? [])
                );
                $said = $n
                    ? "Saved $n link" . ($n === 1 ? '' : 's') . '.'
                    : ($isSocial ? 'No social links saved.' : 'No review links saved.');
            }
        }
        if ($next === null) {
            $said = trim($said . ' Profile complete! Your listing appears publicly once approved — usually within 24 hours.');
        }
        if ($said !== '') flash_set('success', $said);
        redirect($done);
    }

    $suggestions = $step === 'services' ? wizard_take_suggestions((int)$biz['id']) : [];
    $existing    = $step === 'services'
        ? array_column(rows('SELECT name FROM services WHERE business_id = ? ORDER BY id', [$biz['id']]), 'name')
        : wizard_links($biz[$step === 'social' ? 'social' : 'review_links'] ?? null);
    $meta = ['title' => ucfirst($step) . " — $site", 'robots' => 'noindex'];
    view('account/wizard-' . $step, compact('meta', 'u', 'plan', 'biz', 'step', 'suggestions', 'existing', 'done'));

} elseif ($sub === 'listings' && $act === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $biz = own_business((int)post('id'), (int)$u['id']);
    if ($biz) {
        q('DELETE FROM businesses WHERE id = ? AND owner_id = ?', [$biz['id'], $u['id']]);
        if ($biz['city_id']) refresh_city_count((int)$biz['city_id']);
        flash_set('success', 'Listing deleted.');
    }
    redirect('/account/listings');

} elseif ($sub === 'promotions') {
    // The promotion engine: submit a link you've already published elsewhere.
    $mine   = rows('SELECT id, name FROM businesses WHERE owner_id = ? ORDER BY name', [$u['id']]);
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $action = post('action');

        if ($action === 'delete') {
            $promo = promotion_owned((int)post('id'), (int)$u['id']);
            if ($promo) {
                q('DELETE FROM promotions WHERE id = ? AND user_id = ?', [$promo['id'], $u['id']]);
                flash_set('success', 'Promotion removed.');
            }
            redirect('/account/promotions');
        }

        $bizId = (int)post('business_id');
        $biz   = $bizId ? own_business($bizId, (int)$u['id']) : null;
        $url   = clean_url(post('url'));
        $title = mb_substr(post('title'), 0, 200);
        $blurb = mb_substr(post('blurb'), 0, 400);
        $chan  = post('channel');
        if (!isset(promo_channels()[$chan])) $chan = $url ? promo_guess_channel($url) : 'other';

        if (!$biz)                 $errors[] = 'Choose which of your listings this belongs to.';
        if (!$url)                 $errors[] = 'Enter the full web address of the post, starting with https://';
        if ($title === '')         $errors[] = 'Give it a title so members know what they are opening.';
        if ($biz && $url && !$errors) {
            $dupe = row('SELECT id FROM promotions WHERE user_id = ? AND url = ?', [$u['id'], $url]);
            if ($dupe) $errors[] = 'You have already submitted that link.';
        }

        // The plan's monthly ceiling comes first. Tokens buy a promotion; the
        // plan decides how many you may run — which is the part effort cannot
        // lift, and the reason a paid membership is worth paying for.
        $promoMax  = promos_monthly_max((string)$u['plan']);
        $promoUsed = promos_used_this_month((int)$u['id']);
        if (!$errors && $promoMax > 0 && $promoUsed >= $promoMax) {
            $errors[] = 'Your ' . $plan['label'] . ' plan runs ' . $promoMax
                . ' promotion' . ($promoMax === 1 ? '' : 's') . ' a month and you have used them all. '
                . 'They reset on the 1st — or upgrade to run more.';
        }

        // Running a promotion costs tokens. Charge second: token_spend() checks
        // the balance and deducts in one statement, so two submissions racing
        // each other cannot both spend the same tokens.
        $cost = token_rules((string)$u['plan'])['cost_promo'];
        if (!$errors && $cost > 0 && !token_spend((int)$u['id'], $cost, 'Promotion: ' . $title)) {
            $errors[] = 'That costs ' . $cost . ' tokens and you have ' . token_balance((int)$u['id'])
                . '. Open a few member promotions to earn more, or upgrade for a bigger monthly allowance.';
        }

        if (!$errors) {
            q('INSERT INTO promotions (business_id, user_id, channel, url, title, blurb, status)
               VALUES (?,?,?,?,?,?,"pending")',
              [$biz['id'], $u['id'], $chan, $url, $title, $blurb ?: null]);
            flash_set('success', 'Submitted' . ($cost > 0 ? " for $cost tokens" : '')
                . '. It goes live in the member feed once our team approves it — usually within 24 hours.');
            redirect('/account/promotions');
        }
    }

    $list      = promotions_for_user((int)$u['id']);
    $promoMax  = promos_monthly_max((string)$u['plan']);
    $promoUsed = promos_used_this_month((int)$u['id']);
    $meta = ['title' => "Promotion engine — $site", 'robots' => 'noindex'];
    view('account/promotions', compact('meta', 'u', 'plan', 'errors', 'mine', 'list', 'promoMax', 'promoUsed'));

} elseif ($sub === 'tokens') {
    $rules     = token_rules((string)$u['plan']);
    $history   = token_history((int)$u['id']);
    $promoMax  = promos_monthly_max((string)$u['plan']);
    $promoUsed = promos_used_this_month((int)$u['id']);
    $meta      = ['title' => "Tokens — $site", 'robots' => 'noindex'];
    view('account/tokens', compact('meta', 'u', 'plan', 'rules', 'history', 'promoMax', 'promoUsed'));

} elseif ($sub === 'article') {
    // The Featured tier's monthly article: the member briefs it, our team
    // writes it, publishes it and posts it out across the network's channels.
    if (empty($plan['concierge'])) {
        view('account/article-upsell', compact('meta', 'u', 'plan'));
    } else {
        $month  = date('Y-m');
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $topic = mb_substr(post('topic'), 0, 200);
            $brief = mb_substr(post('brief'), 0, 4000);
            $bizId = (int)post('business_id');
            $biz   = $bizId ? own_business($bizId, (int)$u['id']) : null;
            if ($topic === '') $errors[] = 'Give the article a topic — one line is enough.';
            if (!$errors) {
                // One per member per month, enforced by the unique key: a second
                // submission updates the brief rather than queueing another.
                q('INSERT INTO articles (user_id, business_id, month, topic, brief)
                   VALUES (?,?,?,?,?)
                   ON DUPLICATE KEY UPDATE business_id = VALUES(business_id),
                       topic = VALUES(topic), brief = VALUES(brief)',
                  [$u['id'], $biz['id'] ?? null, $month, $topic, $brief ?: null]);
                flash_set('success', 'Brief received. Our team writes it, publishes it, and posts it out across our channels and yours.');
                redirect('/account/article');
            }
        }
        $current = article_for_month((int)$u['id'], $month);
        $past    = rows('SELECT * FROM articles WHERE user_id = ? AND month != ? ORDER BY month DESC LIMIT 12',
                        [$u['id'], $month]);
        $mine    = rows('SELECT id, name FROM businesses WHERE owner_id = ? ORDER BY name', [$u['id']]);
        $meta    = ['title' => "Monthly article — $site", 'robots' => 'noindex'];
        view('account/article', compact('meta', 'u', 'plan', 'errors', 'current', 'past', 'mine', 'month'));
    }

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
