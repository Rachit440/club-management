<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) { redirect(APP_URL . '/dashboard.php'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid session token. Please retry.';
    } else {
        [$ok, $err] = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($ok) { redirect(APP_URL . '/dashboard.php'); }
        $error = $err;
    }
}
$token = csrf_token();
$clubName = get_setting('club_name', 'Elite Club');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In - <?= e($clubName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='24' fill='%234F46E5'/><text x='50' y='68' font-size='58' text-anchor='middle' fill='white' font-family='sans-serif' font-weight='bold'>E</text></svg>">
</head>
<body>
<div class="boot-screen" id="bootScreen"><div class="logo"><i class="fa-solid fa-gem"></i></div><div class="bar"><i></i></div><div class="name">ELITE CLUB</div></div>

<div class="auth-wrap">
  <aside class="auth-aside">
    <div class="brand"><span class="logo-mark"><i class="fa-solid fa-gem"></i></span> <?= e($clubName) ?></div>
    <div>
      <h2>Welcome to the future of club management.</h2>
      <p>Sign in to access your dashboard, manage members, track payments and grow your club - all from one beautiful portal.</p>
      <ul>
        <li><i class="fa-solid fa-circle-check"></i> Real-time analytics &amp; insights</li>
        <li><i class="fa-solid fa-circle-check"></i> Secure role-based access</li>
        <li><i class="fa-solid fa-circle-check"></i> Exportable reports in seconds</li>
        <li><i class="fa-solid fa-circle-check"></i> Built for XAMPP &amp; MySQL</li>
      </ul>
    </div>
    <div class="credit">&copy; <?= date('Y') ?> <?= e($clubName) ?>. All rights reserved.</div>
  </aside>

  <main class="auth-main">
    <div class="auth-card">
      <div class="logo">
        <div class="mark"><i class="fa-solid fa-gem"></i></div>
        <h1>Welcome back</h1>
        <p class="sub">Sign in to your account to continue</p>
      </div>
      <?php if ($error): ?><div class="toast error" style="margin-bottom:1.2rem"><i class="fa-solid fa-circle-xmark"></i><span><?= e($error) ?></span></div><?php endif; ?>
      <form method="POST" id="loginForm" autocomplete="off">
        <?= csrf_field() ?>
        <div class="form-group floating input-icon">
          <i class="fa-solid fa-envelope"></i>
          <input type="email" name="email" id="lemail" placeholder=" " required value="<?= e($_POST['email'] ?? '') ?>">
          <label for="lemail">Email address</label>
        </div>
        <div class="form-group input-icon" style="position:relative">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" id="lpass" required placeholder=" ">
          <label for="lpass" style="position:absolute;left:42px;top:.35rem;transform:translateY(0);font-size:.72rem;color:var(--primary);font-weight:500;pointer-events:none">Password</label>
          <button type="button" class="icon-btn" style="position:absolute;right:6px;top:50%;transform:translateY(-50%)" onclick="const p=document.getElementById('lpass');p.type=p.type==='password'?'text':'password';this.querySelector('i').className=p.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash'"><i class="fa-solid fa-eye"></i></button>
        </div>
        <div class="flex-between mb-2" style="font-size:.88rem">
          <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer"><input type="checkbox" name="remember"> Remember me</label>
          <a href="<?= APP_URL ?>/forgot-password.php">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fa-solid fa-right-to-bracket"></i> Sign In</button>
      </form>
      <div style="text-align:center;margin:1.4rem 0 .4rem;position:relative"><span style="background:var(--surface);padding:0 .8rem;color:var(--text-faint);font-size:.8rem;position:relative;z-index:1">or</span><hr style="position:absolute;top:50%;left:0;right:0;border:none;border-top:1px solid var(--border);margin:0"></div>
      <a href="<?= APP_URL ?>/index.php" class="btn btn-secondary btn-block"><i class="fa-solid fa-house"></i> Back to Home</a>
      <p class="muted center mt-2" style="font-size:.82rem">Demo admin: <strong>admin@club.com</strong> / <strong>admin123</strong></p>
    </div>
  </main>
</div>

<div class="toast-wrap"></div>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
document.getElementById('loginForm').addEventListener('submit', function(e){
  if (!App.validateForm(this)) { e.preventDefault(); App.toast('Please fix the highlighted fields.', 'error'); }
});
</script>
</body>
</html>
