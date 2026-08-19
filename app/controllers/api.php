<?php
// ---------------------------------------------------------------------------
// The one public API endpoint: POST /api/members — create a member account
// from an email, a password and a domain.
//
// Authenticated by a key in a header, not by a session, because the caller is a
// script somewhere else. The key lives in settings and is shown (and rotatable)
// at Superadmin -> Member intake.
//
// It creates the ACCOUNT only. Building the listing costs an AI call and a
// fetch of somebody else's website, so it stays a button a person presses on
// the intake page after looking at what arrived.
// ---------------------------------------------------------------------------

header('Content-Type: application/json; charset=UTF-8');

/** Every exit from here is JSON. A caller that is a script deserves no HTML. */
function api_fail(int $code, string $message, array $extra = []): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message] + $extra, JSON_UNESCAPED_SLASHES);
    exit;
}

if (($segments[1] ?? '') !== 'members') api_fail(404, 'Unknown endpoint. The only one is POST /api/members.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_fail(405, 'POST only.');
if (!intake_ready()) api_fail(503, 'Member intake is not installed on this site yet — run database/upgrade-all.sql.');

// Header first, then a form field, because some hosts strip unknown headers
// from CGI and the caller has no way to tell that is what happened.
$given = (string)($_SERVER['HTTP_X_API_KEY'] ?? '');
if ($given === '' && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $given = trim(preg_replace('/^Bearer\s+/i', '', (string)$_SERVER['HTTP_AUTHORIZATION']));
}

// Read a JSON body if that is what was sent, so both content types work.
$body = [];
if (stripos((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== false) {
    $body = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body)) api_fail(400, 'Body is not valid JSON.');
} else {
    $body = $_POST;
}
if ($given === '') $given = (string)($body['api_key'] ?? '');

// hash_equals, not ===: string comparison stops at the first wrong byte, and
// the time that takes is enough to guess a key one byte at a time.
if ($given === '' || !hash_equals(intake_api_key(), $given)) api_fail(401, 'Bad or missing API key.');

$email    = (string)($body['email'] ?? '');
$password = (string)($body['password'] ?? '');
$domain   = (string)($body['domain'] ?? $body['website'] ?? '');
if ($email === '' || $domain === '') api_fail(422, 'email and domain are both required. password is optional — leave it out and one is generated.');

[$user, $errors] = intake_create_member($email, $password, $domain);
if (!$user) api_fail(422, implode(' ', $errors), ['errors' => $errors]);

http_response_code(201);
echo json_encode([
    'ok'       => true,
    'id'       => (int)$user['id'],
    'email'    => $user['email'],
    'domain'   => $user['website'],
    // Returned once, here, and never stored in readable form. If this is lost
    // the member resets it like anybody else.
    'password' => $user['plain_password'],
    'login'    => site_url('/login'),
    'note'     => 'Account created. Its listing is built from Superadmin → Member intake.',
], JSON_UNESCAPED_SLASHES);
exit;
