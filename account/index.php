<?php
// ---------------------------------------------------------------------------
// Real-folder entry point for the member area. Identical to /members/ — this
// is the name the app's own links use. See members/index.php for the feature
// list and superadmin/index.php for the full rationale.
// ---------------------------------------------------------------------------
$ML_WEB_ROOT = dirname(__DIR__);
$ML_SECTION  = 'account';
require $ML_WEB_ROOT . '/app/entry.php';

require APP_ROOT . '/controllers/account.php';
