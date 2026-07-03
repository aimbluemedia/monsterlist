<?php
// Create the first superadmin (or additional admins) from the command line.
// Usage: php scripts/create_admin.php email@example.com "Full Name" password [superadmin|admin]

require __DIR__ . '/../app/bootstrap.php';

$email = $argv[1] ?? null;
$name  = $argv[2] ?? null;
$pass  = $argv[3] ?? null;
$role  = $argv[4] ?? 'superadmin';

if (!$email || !$name || !$pass || !in_array($role, ['superadmin', 'admin'], true)) {
    exit("Usage: php scripts/create_admin.php email \"Full Name\" password [superadmin|admin]\n");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) exit("Invalid email.\n");
if (strlen($pass) < 8) exit("Password must be at least 8 characters.\n");
if (row('SELECT id FROM users WHERE email = ?', [$email])) exit("A user with that email already exists.\n");

q('INSERT INTO users (email, password_hash, name, role) VALUES (?,?,?,?)',
  [$email, password_hash($pass, PASSWORD_DEFAULT), $name, $role]);

echo "Created $role: $email\n";
