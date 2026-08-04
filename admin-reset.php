<?php
// ---------------------------------------------------------------------------
// Staff-login recovery tool.
//
// Diagnoses why /superadmin/ is rejecting a login, and creates or resets a
// superadmin account. Use this instead of pasting a bcrypt hash into
// phpMyAdmin — the hash contains "$" characters that are easy to mangle in
// transit, and a mangled hash fails silently with the same error message as a
// wrong password.
//
// SAFETY GATE: this page refuses to do anything unless a file named
// "admin-reset.allow" sits next to it. Create that file to enable the tool,
// then DELETE BOTH FILES the moment you are back in. Anyone who can reach an
// enabled copy of this page can take over the site.
//
//   1. File Manager → public_html → new empty file "admin-reset.allow"
//   2. Visit  https://yoursite/admin-reset.php
//   3. Read the diagnosis, set a new password
//   4. Delete admin-reset.allow AND admin-reset.php
// ---------------------------------------------------------------------------
header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

$allowFile = __DIR__ . '/admin-reset.allow';
$enabled   = is_file($allowFile);

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$notices = [];   // [class, message]
$accounts = [];
$dbError  = null;
$throttle = null;

if ($enabled) {
    require __DIR__ . '/app/bootstrap.php';

    // ---- act on the form ---------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim((string)($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');
        $name  = trim((string)($_POST['name'] ?? '')) ?: 'Super Admin';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $notices[] = ['bad', 'That does not look like a valid email address.'];
        } elseif (strlen($pass) < 8) {
            $notices[] = ['bad', 'Password must be at least 8 characters.'];
        } else {
            try {
                $hash     = password_hash($pass, PASSWORD_DEFAULT);
                $existing = row('SELECT id FROM users WHERE email = ?', [$email]);
                if ($existing) {
                    q('UPDATE users SET password_hash = ?, role = "superadmin", status = "active" WHERE id = ?',
                      [$hash, $existing['id']]);
                    $notices[] = ['ok', 'Password reset for ' . h($email)
                        . ' — the account is now an active superadmin.'];
                } else {
                    q('INSERT INTO users (email, password_hash, name, role, status) VALUES (?,?,?,"superadmin","active")',
                      [$email, $hash, $name]);
                    $notices[] = ['ok', 'Created superadmin ' . h($email) . '.'];
                }
                // Clear the rate limiter so repeated failed attempts don't lock
                // you out of the account you just fixed.
                q('DELETE FROM login_attempts');
                $notices[] = ['ok', 'Cleared the failed-login lockout. '
                    . 'Log in at /superadmin/, then delete admin-reset.allow and admin-reset.php.'];
            } catch (Throwable $e) {
                $notices[] = ['bad', 'Database write failed: ' . h($e->getMessage())];
            }
        }
    }

    // ---- read current state ------------------------------------------------
    try {
        $accounts = rows('SELECT id, email, name, role, status, password_hash, last_login_at
                          FROM users WHERE role IN ("admin","superadmin") ORDER BY id');
        $throttle = (int)scalar('SELECT COUNT(*) FROM login_attempts
                                 WHERE attempted_at > (NOW() - INTERVAL 15 MINUTE)');
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

/** Why would password_verify() fail for this row, structurally? */
function hash_verdict(string $hash): array
{
    if ($hash === '')                                return ['bad', 'EMPTY — no password set'];
    if (strlen($hash) !== 60)                        return ['bad', 'wrong length (' . strlen($hash) . ', expected 60) — the hash was truncated or mangled'];
    if (strncmp($hash, '$2y$', 4) !== 0
        && strncmp($hash, '$2a$', 4) !== 0)          return ['bad', 'not a bcrypt hash (starts "' . substr($hash, 0, 4) . '") — probably stored as plain text or MD5'];
    return ['ok', 'looks like a valid bcrypt hash'];
}
?>
<!doctype html>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Staff login recovery</title>
<style>
 body{font:15px/1.55 system-ui,sans-serif;max-width:780px;margin:40px auto;padding:0 18px;color:#111}
 h1{font-size:1.45rem;margin:0 0 4px} h2{font-size:1.05rem;margin:26px 0 8px}
 .ok{color:#0a7d32;font-weight:700} .bad{color:#b3261e;font-weight:700} .warn{color:#8a6100;font-weight:700}
 .box{border:1px solid #ddd;border-radius:10px;padding:16px 18px;margin:18px 0}
 .box.danger{border-color:#f0b4ae;background:#fdf4f3}
 .box.good{border-color:#b6e2c4;background:#f3fbf6}
 code{background:#f4f4f5;padding:1px 5px;border-radius:4px;font-size:.9em}
 table{border-collapse:collapse;width:100%;font-size:.92rem}
 th,td{text-align:left;padding:7px 10px;border-bottom:1px solid #eee;vertical-align:top}
 label{display:block;font-weight:600;margin:14px 0 5px}
 input{width:100%;padding:9px 11px;border:1px solid #ccc;border-radius:8px;font:inherit}
 button{margin-top:16px;padding:10px 18px;border:0;border-radius:8px;background:#2563eb;color:#fff;font:inherit;font-weight:700;cursor:pointer}
 ol li{margin:6px 0}
</style>
<h1>Staff login recovery</h1>

<?php if (!$enabled): ?>
  <div class="box danger">
    <p class="bad">Disabled — this tool is doing nothing.</p>
    <p>It stays inert until you deliberately switch it on, so that leaving the file
       on the server by accident cannot hand anyone your site.</p>
    <ol>
      <li>Open <strong>hPanel → File Manager → public_html</strong></li>
      <li>Create an empty file named <code>admin-reset.allow</code> beside this one</li>
      <li>Reload this page</li>
    </ol>
    <p>Looking for <code><?= h($allowFile) ?></code></p>
  </div>
<?php else: ?>

  <?php foreach ($notices as [$cls, $msg]): ?>
    <div class="box <?= $cls === 'ok' ? 'good' : 'danger' ?>"><p class="<?= $cls ?>"><?= $msg ?></p></div>
  <?php endforeach; ?>

  <?php if ($dbError !== null): ?>
    <div class="box danger">
      <p class="bad">Could not read the users table.</p>
      <p><code><?= h($dbError) ?></code></p>
      <p>If this says the table does not exist, the database schema was never imported —
         import <code>database/schema.sql</code> first.</p>
    </div>
  <?php else: ?>

    <h2>Staff accounts on this database</h2>
    <?php if (!$accounts): ?>
      <div class="box danger">
        <p class="bad">There are no admin or superadmin accounts at all.</p>
        <p>That is why every login is rejected — there is nothing to log in to.
           Create one with the form below.</p>
      </div>
    <?php else: ?>
      <table>
        <tr><th>Email</th><th>Role</th><th>Status</th><th>Password hash</th><th>Last login</th></tr>
        <?php foreach ($accounts as $a): ?>
          <?php [$cls, $verdict] = hash_verdict((string)$a['password_hash']); ?>
          <tr>
            <td><?= h($a['email']) ?></td>
            <td><?= h($a['role']) ?></td>
            <td class="<?= $a['status'] === 'active' ? 'ok' : 'bad' ?>"><?= h($a['status']) ?></td>
            <td class="<?= $cls ?>"><?= h($verdict) ?></td>
            <td><?= h($a['last_login_at'] ?? 'never') ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
      <p>The login form gives one identical message for four different problems:
         no such email, wrong password, a role that is not staff, and a suspended
         account. The table above tells you which one you actually have. Note the
         exact spelling of the email — that is what you must type to log in.</p>
    <?php endif; ?>

    <?php if ($throttle !== null && $throttle >= 8): ?>
      <div class="box danger">
        <p class="bad">Rate limiter tripped: <?= (int)$throttle ?> failed attempts in the last 15 minutes.</p>
        <p>At 8 or more, the login form rejects you no matter what you type.
           Saving the form below clears this.</p>
      </div>
    <?php elseif ($throttle !== null): ?>
      <p><?= (int)$throttle ?> failed login attempt(s) in the last 15 minutes (the lockout starts at 8).</p>
    <?php endif; ?>

    <h2>Set a superadmin password</h2>
    <div class="box">
      <p>Creates the account if the email is new, or resets the password and
         restores superadmin access if it already exists. The password is hashed
         here on the server, so nothing fragile has to survive a copy-paste.</p>
      <form method="post">
        <label>Email</label>
        <input type="email" name="email" required autocomplete="off"
               value="<?= h($accounts[0]['email'] ?? 'super@monsterlist.org') ?>">
        <label>Name (only used when creating a new account)</label>
        <input type="text" name="name" autocomplete="off" value="Super Admin">
        <label>New password (8 characters minimum)</label>
        <input type="password" name="password" required minlength="8" autocomplete="new-password">
        <button type="submit">Save password</button>
      </form>
    </div>

  <?php endif; ?>

  <div class="box danger">
    <p class="bad">Delete both files when you are done.</p>
    <p><code>admin-reset.allow</code> and <code>admin-reset.php</code>. While they are
       both present, anyone who guesses this URL can reset your superadmin password.</p>
  </div>

<?php endif; ?>
