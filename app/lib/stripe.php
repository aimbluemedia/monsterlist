<?php
// Minimal Stripe API client over cURL — no Composer/SDK needed on shared hosting.
// Docs: https://stripe.com/docs/api

/**
 * One Stripe setting.
 *
 * Read through here rather than off $GLOBALS directly: a config.php written
 * against an older release has no 'webhook_secret' key at all, and on PHP 8 the
 * missing index is a fatal — which would take out the webhook endpoint, the one
 * page whose failures nobody sees.
 */
function stripe_config(string $key): string
{
    return (string)($GLOBALS['config']['stripe'][$key] ?? '');
}

function stripe_configured(): bool
{
    return stripe_config('secret_key') !== '';
}

/** 'test', 'live', or '' when there is no key. Test keys start sk_test_. */
function stripe_mode(): string
{
    $key = stripe_config('secret_key');
    if ($key === '') return '';
    return strpos($key, '_test_') !== false ? 'test' : 'live';
}

/**
 * $timeout is the whole request. It is short for calls made while someone is
 * waiting on a page — Diagnostics asks Stripe about two prices every time it is
 * opened, and a Stripe having a bad day must not become an admin page that
 * hangs until nginx gives up on it.
 */
function stripe_request(string $method, string $path, array $params = [], int $timeout = 20): array
{
    $key = stripe_config('secret_key');
    $ch  = curl_init('https://api.stripe.com/v1' . $path);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => $key . ':',
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
    ];
    if (strtoupper($method) === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($params);
    } elseif ($params) {
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1' . $path . '?' . http_build_query($params));
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    if ($body === false) {
        throw new RuntimeException('Stripe request failed: ' . curl_error($ch));
    }
    $data = json_decode($body, true) ?: [];
    if (isset($data['error'])) {
        throw new RuntimeException('Stripe: ' . ($data['error']['message'] ?? 'unknown error'));
    }
    return $data;
}

/** Create (or reuse) the Stripe customer for a user; returns customer id. */
function stripe_customer_for(array $user): string
{
    if (!empty($user['stripe_customer_id'])) return $user['stripe_customer_id'];
    $cust = stripe_request('POST', '/customers', [
        'email' => $user['email'],
        'name'  => $user['name'],
        'metadata[user_id]' => $user['id'],
    ]);
    q('UPDATE users SET stripe_customer_id = ? WHERE id = ?', [$cust['id'], $user['id']]);
    return $cust['id'];
}

/** Start a subscription Checkout session; returns the redirect URL. */
/**
 * A checkout session for one plan.
 *
 * $businessId is the listing the member started the upgrade from. It changes no
 * pricing — the plan is held on the account — but it decides where they come
 * back to, and coming back to the listing they upgraded is what makes "upgrade
 * this listing" true all the way through the purchase rather than only on the
 * button that began it. Zero means the checkout began somewhere account-wide,
 * like the public pricing page.
 */
function stripe_checkout_url(array $user, string $plan, int $businessId = 0): string
{
    $priceId = setting($plan === 'featured' ? 'stripe_price_featured' : 'stripe_price_pro');
    if ($priceId === '') {
        throw new RuntimeException('Stripe price ID for the ' . $plan . ' plan is not configured (Admin → Settings).');
    }
    // Stripe swaps {CHECKOUT_SESSION_ID} into the URL it sends the member back
    // to. That id is what lets the return page confirm the payment itself
    // instead of trusting that the webhook has already arrived — see
    // stripe_fulfill_session(). Braces stay raw; Stripe does not substitute an
    // encoded placeholder.
    $done   = ($businessId ? '/account/listings/edit?id=' . $businessId . '&' : '/account/billing?')
            . 'upgraded=1&session_id={CHECKOUT_SESSION_ID}';
    $cancel = $businessId ? '/account/listings/upgrade?id=' . $businessId : '/pricing';
    $session = stripe_request('POST', '/checkout/sessions', [
        'mode'                 => 'subscription',
        'customer'             => stripe_customer_for($user),
        'line_items[0][price]' => $priceId,
        'line_items[0][quantity]' => 1,
        'success_url'          => site_url($done),
        'cancel_url'           => site_url($cancel),
        'metadata[user_id]'    => $user['id'],
        'metadata[plan]'       => $plan,
        'metadata[business_id]' => $businessId,
        'subscription_data[metadata][user_id]' => $user['id'],
        'subscription_data[metadata][plan]'    => $plan,
    ]);
    return $session['url'];
}

/** Customer billing portal (cancel / change card). */
function stripe_portal_url(array $user): string
{
    $session = stripe_request('POST', '/billing_portal/sessions', [
        'customer'   => stripe_customer_for($user),
        'return_url' => site_url('/account/billing'),
    ]);
    return $session['url'];
}

/**
 * What Stripe holds for a price id, or null if it will not answer for it.
 *
 * Used by Diagnostics to prove a configured price is real before a member finds
 * out it isn't. Never throws: an unset id, a wrong id and an unreachable Stripe
 * are all "cannot confirm this", and the caller says so.
 */
function stripe_price_info(string $priceId): ?array
{
    if ($priceId === '' || !stripe_configured()) return null;
    try {
        return stripe_request('GET', '/prices/' . rawurlencode($priceId), [], 8);
    } catch (Throwable $e) {
        return null;
    }
}

/** A price as money, e.g. "$19.00 / month". Stripe holds amounts in cents. */
function stripe_price_label(array $price): string
{
    $amount = isset($price['unit_amount']) ? (int)$price['unit_amount'] / 100 : 0;
    $cur    = strtoupper((string)($price['currency'] ?? ''));
    $sym    = $cur === 'USD' ? '$' : ($cur === 'GBP' ? '£' : ($cur === 'EUR' ? '€' : ''));
    $every  = $price['recurring']['interval'] ?? '';
    $count  = (int)($price['recurring']['interval_count'] ?? 1);
    $per    = $every === '' ? 'one-off' : ($count > 1 ? "every $count {$every}s" : "per $every");
    return $sym . number_format($amount, 2) . ($sym === '' ? " $cur" : '') . ' ' . $per;
}

/**
 * Put a user on a paid plan. The one place a plan is granted for money.
 *
 * Idempotent, because it is called from two directions that do not know about
 * each other: the webhook, and the member's own return from checkout. Whichever
 * arrives first does the work and the other changes nothing.
 */
function stripe_apply_plan(int $userId, string $plan, ?string $subId = null): void
{
    if (!$userId || !in_array($plan, ['pro', 'featured'], true)) return;
    $before = (string)scalar('SELECT plan FROM users WHERE id = ?', [$userId]);
    q('UPDATE users SET plan = ? WHERE id = ?', [$plan, $userId]);
    sync_business_tiers($userId, $plan);
    if ($subId) {
        q('INSERT INTO subscriptions (user_id, plan, status, stripe_subscription_id) VALUES (?,?,"active",?)
           ON DUPLICATE KEY UPDATE plan = VALUES(plan), status = "active"', [$userId, $plan, $subId]);
    }
    // Only on a real change: a member who reloads the thank-you page should not
    // be emailed "welcome to Pro" again each time.
    if ($before !== $plan) notify_plan_change($userId, $plan);
}

/**
 * Confirm a Checkout Session with Stripe and grant the plan it paid for.
 *
 * The webhook is the proper channel and stays the one that handles renewals and
 * cancellations. But a webhook that has not been set up yet — or a secret
 * pasted wrong — fails silently, and the member has paid and sees no change.
 * They are standing on the return page holding a session id, so ask Stripe
 * directly and settle it there and then.
 *
 * $expectUserId guards the obvious abuse: a session id belongs to whoever paid,
 * and one pasted into someone else's URL bar must not upgrade their account.
 *
 * Returns the plan granted, or '' when the session is unpaid, unknown, not this
 * member's, or Stripe cannot be reached.
 */
function stripe_fulfill_session(string $sessionId, int $expectUserId): string
{
    if ($sessionId === '' || strpos($sessionId, 'cs_') !== 0 || !stripe_configured()) return '';
    try {
        $s = stripe_request('GET', '/checkout/sessions/' . rawurlencode($sessionId));
    } catch (Throwable $e) {
        error_log('MonsterList: could not confirm Stripe session ' . $sessionId . ' — ' . $e->getMessage());
        return '';
    }
    $paid = ($s['payment_status'] ?? '') === 'paid' || ($s['status'] ?? '') === 'complete';
    $plan = (string)($s['metadata']['plan'] ?? '');
    $uid  = (int)($s['metadata']['user_id'] ?? 0);
    if (!$paid || $uid !== $expectUserId || !in_array($plan, ['pro', 'featured'], true)) return '';

    $subId = $s['subscription'] ?? null;
    stripe_apply_plan($uid, $plan, is_string($subId) ? $subId : null);
    return $plan;
}

/** Verify a webhook signature (Stripe-Signature header). */
function stripe_webhook_event(): ?array
{
    $payload = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $secret = stripe_config('webhook_secret');
    if ($secret === '' || $payload === '' || $sigHeader === '') return null;

    $parts = [];
    foreach (explode(',', $sigHeader) as $kv) {
        [$k, $v] = array_pad(explode('=', trim($kv), 2), 2, '');
        $parts[$k][] = $v;
    }
    $ts = $parts['t'][0] ?? '';
    if ($ts === '' || abs(time() - (int)$ts) > 300) return null; // 5 min tolerance

    $expected = hash_hmac('sha256', $ts . '.' . $payload, $secret);
    $ok = false;
    foreach ($parts['v1'] ?? [] as $sig) {
        if (hash_equals($expected, $sig)) { $ok = true; break; }
    }
    return $ok ? (json_decode($payload, true) ?: null) : null;
}
