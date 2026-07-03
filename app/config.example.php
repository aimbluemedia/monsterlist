<?php
// ---------------------------------------------------------------------------
// MonsterList configuration.
// Copy to config.php and fill in real values. config.php is git-ignored.
// ---------------------------------------------------------------------------
return [
    // Absolute site URL, no trailing slash. Used for canonicals, sitemaps, JSON-LD.
    'site_url' => 'https://monsterlist.org',

    'db' => [
        'host'    => 'localhost',
        'name'    => 'monsterlist',
        'user'    => 'monsterlist',
        'pass'    => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],

    // Stripe (dashboard.stripe.com → Developers → API keys)
    'stripe' => [
        'secret_key'      => '',   // sk_live_... / sk_test_...
        'publishable_key' => '',   // pk_live_... / pk_test_...
        'webhook_secret'  => '',   // whsec_... (Developers → Webhooks)
    ],

    // Transactional email (password resets, notifications)
    'mail' => [
        'from'      => 'no-reply@monsterlist.org',
        'from_name' => 'MonsterList',
    ],

    // Set false in production to hide PHP errors from visitors.
    'debug' => false,
];
