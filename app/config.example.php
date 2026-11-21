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

    // Anthropic Claude API — powers "AI fill" on the listing form, and
    // "Write it with AI" under the Profile box in the staff listing editor.
    // The Profile writer uses Claude's web search, which has to be switched on
    // for the key at platform.claude.com → Settings.
    // Get a key at https://platform.claude.com → API keys. Leave empty to disable.
    'anthropic' => [
        'api_key' => '',                    // sk-ant-...
        'model'   => 'claude-opus-5',
    ],

    // Transactional email (password resets, notifications)
    'mail' => [
        'from'      => 'no-reply@monsterlist.org',
        'from_name' => 'MonsterList',
    ],

    // Set false in production to hide PHP errors from visitors.
    'debug' => false,

    // -----------------------------------------------------------------------
    // Toutify (toutify.com) — the parent account system.
    //
    // Leave 'mode' out, or set it to 'off', and none of this runs: the site's
    // own login, sign-up and password reset are in charge and /auth/toutify/*
    // answers 404. That is also the way back from any later step.
    //
    //   'test'  only the emails in test_allowlist may sign in through Toutify;
    //           everyone else is sent to the normal login page
    //   'live'  Toutify signs everybody in
    //
    // The two secrets come from Toutify. They are read from here and nowhere
    // else — there is no getenv() in this codebase, so TOUTIFY_* environment
    // variables would not be seen.
    // -----------------------------------------------------------------------
    'toutify' => [
        'issuer'         => 'https://toutify.com',
        'client_id'      => 'monsterlist',
        'client_secret'  => '',
        'webhook_secret' => '',
        // Where the "Log in" buttons point once mode is 'live'. Toutify's own
        // hosted page; client_id and return_to are added automatically.
        'login_url'      => 'https://toutify.com/members/login.php',
        'mode'           => 'off',
        'test_allowlist' => '',      // comma-separated emails, used by 'test'
    ],
];
