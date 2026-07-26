<?php
// ---------------------------------------------------------------------------
// Real-folder entry point for the member area.
//
// "Members" is the friendlier public-facing name; /account/ is what the app's
// internal links use. Both folders resolve to the same controller and the same
// features, so either URL works.
//
// Member features reachable from here:
//   /members/                    dashboard
//   /members/?p=listings         my listings
//   /members/?p=listings/new     new listing (incl. AI autofill from a URL)
//   /members/?p=analytics        views + click analytics
//   /members/?p=billing          plan + Stripe billing portal
//   /members/?p=settings         profile, email, password
// With mod_rewrite active the clean forms (/members/listings, ...) work too.
// ---------------------------------------------------------------------------
$ML_WEB_ROOT = dirname(__DIR__);
$ML_SECTION  = 'members';
$ML_ALIAS    = 'account';   // the controller is written against /account
require $ML_WEB_ROOT . '/app/entry.php';

require APP_ROOT . '/controllers/account.php';
