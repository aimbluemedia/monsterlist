<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($meta['title']) ?></title>
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('/assets/css/style.css')) ?>">
<style>
  body{background:#14161c;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
  .admin-login{width:100%;max-width:400px;padding:36px;background:#1c1f27;border:1px solid #2c303b;border-radius:16px;color:#e8eaf0}
  .admin-login h1{color:#fff;font-size:1.35rem;margin:14px 0 4px}
  .admin-login .sub{color:#9aa1ad;font-size:.9rem;margin:0 0 20px}
  .admin-login label{color:#c3c8d4}
  .admin-login input{background:#14161c;border-color:#2c303b;color:#e8eaf0}
  .admin-login input:focus{outline:2px solid rgba(37,99,235,.35);border-color:var(--accent)}
  .admin-login .logo-mark{background:#1a1d24;border:1px solid #2c303b}
  .admin-login .back{display:block;text-align:center;margin-top:18px;color:#9aa1ad;font-size:.85rem}
  .admin-login .back:hover{color:#fff}
</style>
</head>
<body>
<div class="admin-login">
  <span class="logo-mark" style="width:38px;height:38px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:1.1rem">A</span>
  <h1><?= e(setting('site_name')) ?> — Staff sign in</h1>
  <p class="sub">Administrators and superadmins only. Members sign in on the <a href="/login" style="color:var(--accent)">member login</a>.</p>
  <?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post"><?= csrf_field() ?>
    <label>Email</label>
    <input type="email" name="email" value="<?= e(post('email')) ?>" required autocomplete="username">
    <label>Password</label>
    <input type="password" name="password" required autocomplete="current-password">
    <button class="btn btn-primary btn-block" style="margin-top:18px">Sign in</button>
  </form>
  <a class="back" href="/">← Back to the site</a>
</div>
</body>
</html>
