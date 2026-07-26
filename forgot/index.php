<?php
// ---------------------------------------------------------------------------
// Real-folder entry point for /forgot — so the public auth routes resolve
// even where mod_rewrite is unavailable. See superadmin/index.php for the
// full rationale.
// ---------------------------------------------------------------------------
$ML_WEB_ROOT = dirname(__DIR__);
$ML_SECTION  = 'forgot';
$ML_SUBPATHS = false;   // flat route, no sub-pages
require $ML_WEB_ROOT . '/app/entry.php';

require APP_ROOT . '/controllers/auth.php';
