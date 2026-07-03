<?php
// Minimal Stripe API client over cURL — no Composer/SDK needed on shared hosting.
// Docs: https://stripe.com/docs/api

function stripe_configured(): bool
{
    return !empty($GLOBALS['config']['stripe']['secret_key']);
}

function stripe_request(string $method, string $path, array $params = []): array
{
    $key = $GLOBALS['config']['stripe']['secret_key'];
    $ch  = curl_init('https://api.stripe.com/v1' . $path);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => $key . ':',
        CURLOPT_TIMEOUT        => 20,
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
function stripe_checkout_url(array $user, string $plan): string
{
    $priceId = setting($plan === 'featured' ? 'stripe_price_featured' : 'stripe_price_pro');
    if ($priceId === '') {
        throw new RuntimeException('Stripe price ID for the ' . $plan . ' plan is not configured (Admin → Settings).');
    }
    $session = stripe_request('POST', '/checkout/sessions', [
        'mode'                 => 'subscription',
        'customer'             => stripe_customer_for($user),
        'line_items[0][price]' => $priceId,
        'line_items[0][quantity]' => 1,
        'success_url'          => site_url('/account/billing?upgraded=1'),
        'cancel_url'           => site_url('/pricing'),
        'metadata[user_id]'    => $user['id'],
        'metadata[plan]'       => $plan,
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

/** Verify a webhook signature (Stripe-Signature header). */
function stripe_webhook_event(): ?array
{
    $payload = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $secret = $GLOBALS['config']['stripe']['webhook_secret'];
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
