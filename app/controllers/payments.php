<?php
// Stripe: /stripe/checkout (POST), /stripe/portal (POST), /stripe/webhook (POST from Stripe)

if ($path === '/stripe/checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = require_login();
    $plan = post('plan');
    // The listing the upgrade was started from, when it was started from one.
    // Checked against the owner here: it decides where the member is sent back
    // to, and an id from someone else's account would name their business on
    // this member's screen.
    $bizId = (int)($_POST['business_id'] ?? 0);
    if ($bizId && !scalar('SELECT id FROM businesses WHERE id = ? AND owner_id = ?', [$bizId, $u['id']])) {
        $bizId = 0;
    }
    // Somewhere to send them when this cannot go ahead — back to the page they
    // pressed the button on, not out to the public price list.
    $back = $bizId ? '/account/listings/upgrade?id=' . $bizId : '/pricing';
    if (!in_array($plan, ['pro', 'featured'], true)) redirect($back);
    if (!stripe_configured()) {
        flash_set('error', 'Payments are not configured yet — add your Stripe keys to app/config.php.');
        redirect($back);
    }
    try {
        redirect(stripe_checkout_url($u, $plan, $bizId));
    } catch (Throwable $e) {
        flash_set('error', 'Could not start checkout: ' . $e->getMessage());
        redirect($back);
    }
}

if ($path === '/stripe/portal' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = require_login();
    if (!stripe_configured() || empty($u['stripe_customer_id'])) {
        flash_set('error', 'No billing profile found for this account.');
        redirect('/account/billing');
    }
    try {
        redirect(stripe_portal_url($u));
    } catch (Throwable $e) {
        flash_set('error', 'Could not open the billing portal: ' . $e->getMessage());
        redirect('/account/billing');
    }
}

if ($path === '/stripe/webhook' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $event = stripe_webhook_event();
    if (!$event) { http_response_code(400); exit('bad signature'); }

    $type = $event['type'] ?? '';
    $obj  = $event['data']['object'] ?? [];

    if ($type === 'checkout.session.completed') {
        // Same call the member's return from checkout makes, so whichever gets
        // here first grants the plan and the second is a no-op.
        $subId = $obj['subscription'] ?? null;
        stripe_apply_plan(
            (int)($obj['metadata']['user_id'] ?? 0),
            (string)($obj['metadata']['plan'] ?? ''),
            is_string($subId) ? $subId : null
        );
    } elseif (in_array($type, ['customer.subscription.updated', 'customer.subscription.deleted'], true)) {
        $subId  = $obj['id'] ?? '';
        $userId = (int)($obj['metadata']['user_id'] ?? 0);
        if (!$userId && $subId) {
            $userId = (int)(scalar('SELECT user_id FROM subscriptions WHERE stripe_subscription_id = ?', [$subId]) ?? 0);
        }
        if ($userId) {
            $status = $obj['status'] ?? 'canceled';
            $ended  = $type === 'customer.subscription.deleted' || in_array($status, ['canceled', 'unpaid', 'incomplete_expired'], true);
            $periodEnd = !empty($obj['current_period_end']) ? date('Y-m-d H:i:s', (int)$obj['current_period_end']) : null;
            q('UPDATE subscriptions SET status = ?, current_period_end = ? WHERE stripe_subscription_id = ?',
              [$ended ? 'canceled' : ($status === 'past_due' ? 'past_due' : 'active'), $periodEnd, $subId]);
            if ($ended) {
                q('UPDATE users SET plan = "free" WHERE id = ?', [$userId]);
                sync_business_tiers($userId, 'free');
            }
        }
    }
    http_response_code(200);
    exit('ok');
}

not_found();
