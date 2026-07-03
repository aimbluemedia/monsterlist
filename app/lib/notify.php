<?php
// Transactional notifications + IndexNow pings.

function notify_welcome(string $email, string $name): void
{
    $site = setting('site_name');
    send_mail($email, "Welcome to $site 🎉",
        "Hi $name,\n\n" .
        "Welcome to $site — your account is ready.\n\n" .
        "Next step: create your free listing (it takes about five minutes):\n" .
        site_url('/account/listings/new') . "\n\n" .
        "Every listing is reviewed by our team before going live, usually within 24 hours.\n\n" .
        "Want a bigger presence? Pro and Featured plans add photo galleries, video,\n" .
        "analytics and priority placement: " . site_url('/pricing') . "\n\n" .
        "— The $site team");
}

function notify_listing_decision(array $biz, bool $approved): void
{
    if (empty($biz['owner_id'])) return;
    $owner = row('SELECT email, name FROM users WHERE id = ?', [$biz['owner_id']]);
    if (!$owner) return;
    $site = setting('site_name');
    if ($approved) {
        $url = site_url(business_url_by_id((int)$biz['id']) ?? '/');
        send_mail($owner['email'], "\"{$biz['name']}\" is now live on $site ✅",
            "Hi {$owner['name']},\n\nGood news — your listing \"{$biz['name']}\" was approved and is now live:\n$url\n\n" .
            "Tips to get more customers:\n" .
            "• Share your listing link on your social profiles\n" .
            "• Ask happy customers to leave a review\n" .
            "• Upgrade for photos, video and priority placement: " . site_url('/pricing') . "\n\n— The $site team");
    } else {
        send_mail($owner['email'], "Your $site listing \"{$biz['name']}\" needs changes",
            "Hi {$owner['name']},\n\nYour listing \"{$biz['name']}\" was not approved in its current form.\n\n" .
            "Common reasons: incomplete details, duplicate listing, or content that doesn't\n" .
            "meet our guidelines. You can edit and resubmit it from your dashboard:\n" .
            site_url('/account/listings') . "\n\nQuestions? Just reply to this email.\n\n— The $site team");
    }
}

function notify_plan_change(int $userId, string $plan): void
{
    $user = row('SELECT email, name FROM users WHERE id = ?', [$userId]);
    if (!$user) return;
    $site  = setting('site_name');
    $label = plans()[$plan]['label'] ?? ucfirst($plan);
    send_mail($user['email'], "You're on the $label plan — thank you! 🚀",
        "Hi {$user['name']},\n\nYour payment went through and your $site membership is now $label.\n" .
        "All your listings have been upgraded automatically.\n\n" .
        "Manage your subscription anytime: " . site_url('/account/billing') . "\n\n— The $site team");
}

function notify_claim_decision(array $claim, array $biz, bool $approved): void
{
    $user = row('SELECT email, name FROM users WHERE id = ?', [$claim['user_id']]);
    if (!$user) return;
    $site = setting('site_name');
    if ($approved) {
        send_mail($user['email'], "Claim approved — \"{$biz['name']}\" is yours on $site",
            "Hi {$user['name']},\n\nYour claim for \"{$biz['name']}\" was approved. You can now manage\n" .
            "this listing from your dashboard:\n" . site_url('/account/listings') . "\n\n— The $site team");
    } else {
        send_mail($user['email'], "About your claim for \"{$biz['name']}\" on $site",
            "Hi {$user['name']},\n\nWe couldn't verify your claim for \"{$biz['name']}\" and it was declined.\n" .
            "If you believe this is a mistake, reply to this email with proof of ownership\n" .
            "(a company email address, website access, or business documents).\n\n— The $site team");
    }
}

/** Canonical storefront path for a business id (or null). */
function business_url_by_id(int $bizId): ?string
{
    $b = row(
        'SELECT b.slug, ci.slug AS city_slug, ci.country_code, ci.region_id, r.slug AS region_slug
         FROM businesses b JOIN cities ci ON ci.id = b.city_id LEFT JOIN regions r ON r.id = ci.region_id
         WHERE b.id = ?', [$bizId]);
    if (!$b) return null;
    $cc = strtolower($b['country_code']);
    return $b['region_id']
        ? "/$cc/{$b['region_slug']}/{$b['city_slug']}/{$b['slug']}"
        : "/$cc/{$b['city_slug']}/{$b['slug']}";
}

// ---- IndexNow: tell Bing/Yandex/Seznam (and AI engines that consume it) instantly ----

function indexnow_key(): string
{
    $key = setting('indexnow_key');
    if ($key === '') {
        $key = md5(random_bytes(16));
        setting_save('indexnow_key', $key);
    }
    return $key;
}

/** Fire-and-forget ping for freshly published URLs. Never blocks the response. */
function indexnow_ping(array $paths): void
{
    if (!$paths) return;
    $host = parse_url($GLOBALS['config']['site_url'], PHP_URL_HOST);
    if (!$host || str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) return;
    $body = json_encode([
        'host'        => $host,
        'key'         => indexnow_key(),
        'keyLocation' => site_url('/' . indexnow_key() . '.txt'),
        'urlList'     => array_map('site_url', array_slice($paths, 0, 100)),
    ]);
    $ch = curl_init('https://api.indexnow.org/indexnow');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 3,
    ]);
    @curl_exec($ch);
    curl_close($ch);
}
