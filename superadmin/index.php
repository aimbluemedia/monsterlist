<?php
// ---------------------------------------------------------------------------
// Real-folder entry point for the staff area.
//
// The site normally routes every URL through the front controller (/index.php)
// via .htaccess mod_rewrite. This folder is a belt-and-braces entrance so that
// /superadmin/ resolves even where mod_rewrite is unavailable — Apache serves
// this file directly as the folder's DirectoryIndex.
//
// Staff features reachable from here:
//   /superadmin/                  dashboard + moderation queue
//   /superadmin/?p=listings       approve / reject / verify submissions
//   /superadmin/?p=members        member accounts
//   /superadmin/?p=claims         listing ownership claims
//   /superadmin/?p=reviews        review moderation
//   /superadmin/?p=categories     category management
//   /superadmin/?p=admins         staff accounts        (superadmin only)
//   /superadmin/?p=settings       site settings + AI key (superadmin only)
// With mod_rewrite active the clean forms (/superadmin/listings, ...) work too.
// ---------------------------------------------------------------------------
$ML_WEB_ROOT = dirname(__DIR__);
$ML_SECTION  = 'superadmin';
require $ML_WEB_ROOT . '/app/entry.php';

// Bare /superadmin/ from a signed-out visitor: serve the login form here rather
// than redirecting to /superadmin/login, which itself needs mod_rewrite.
if (count($segments) === 1 && !is_admin()) {
    $segments[] = 'login';
    $path = '/' . implode('/', $segments);
}

require APP_ROOT . '/controllers/admin.php';
