<?php
// ---------------------------------------------------------------------------
// Real-folder entry point for the member area.
//
// This is the member area's address. /account/ was its old name and now
// redirects here, so older links keep working.
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
require $ML_WEB_ROOT . '/app/entry.php';

require APP_ROOT . '/controllers/account.php';
