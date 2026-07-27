<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) { redirect(APP_URL . '/dashboard.php'); }

$msg = '';
$error = '';
$showReset = false;
$clubName = get_setting('club_name', 'Elite Club');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { $error = 'Invalid session token.'; }
    else {
        $email = clean($_POST['email'] ?? '');
        if (!validate_email($email)) { $error = 'Enter a valid email address.'; }
        else {
            $stmt = db()->prepare("SELECT id FROM admins WHERE email=? UNION SELECT id FROM members WHERE email=?");
            $stmt->execute([$email, $email]);
            if ($stmt->fetch()) {
                $_SESSION['reset_email'] = $email;
                $msg = "A password reset link has been generated for $email. Set a new password below.";
                $showReset = true;
            } else { $error = 'No account found with that email.'; }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { $error = 'Invalid session token.'; }
    else {
        $pw = $_POST['password'] ?? ''; $cpw = $_POST['confirm_password'] ?? ''; $email = $_SESSION['reset_email'] ?? '';
        if (strlen($pw) < 8) { $error = 'Password must be at least 8 characters.'; }
        elseif ($pw !== $cpw) { $error = 'Passwords do not match.'; }
        elseif (!$email) { $error = 'Reset session expired. Start again.'; }
        else {
            $hash = password_hash($pw, PASSWORD_BCRYPT);
            db()->prepare("UPDATE admins SET password=? WHERE email=?")->execute([$hash, $email]);
            db()->prepare("UPDATE members SET password=? WHERE email=?")->execute([$hash, $email]);
            unset($_SESSION['reset_email']);
            $msg = 'Password updated successfully. You can now sign in.';
            $showReset = false;
        }
    }
}
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - <?= e($clubName) ?></title>
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
    <div><h2>Forgot your password?</h2><p>No worries - verify your email and set a new password to regain access to your account.</p></div>
    <div class="credit">&copy; <?= date('Y') ?> <?= e($clubName) ?></div>
  </aside>

  <main class="auth-main">
    <div class="auth-card">
      <div class="logo"><div class="mark" style="background:linear-gradient(135deg,var(--warning),#d97706)"><i class="fa-solid fa-key"></i></div><h1>Reset password</h1><p class="sub"><?= $showReset ? 'Set a new password for your account' : 'Enter your registered email to continue' ?></p></div>
      <?php if ($error): ?><div class="toast error" style="margin-bottom:1.2rem"><i class="fa-solid fa-circle-xmark"></i><span><?= e($error) ?></span></div><?php endif; ?>
      <?php if ($msg): ?><div class="toast success" style="margin-bottom:1.2rem"><i class="fa-solid fa-circle-check"></i><span><?= e($msg) ?></span></div><?php endif; ?>

      <?php if ($showReset): ?>
        <form method="POST" id="resetForm">
          <?= csrf_field() ?><input type="hidden" name="action" value="reset">
          <div class="form-group input-icon"><i class="fa-solid fa-lock"></i><input type="password" name="password" id="password" required minlength="8" placeholder="New password (min 8 chars)"></div>
          <div class="form-help" id="pwStrength" style="margin-bottom:.8rem"></div>
          <div class="form-group input-icon"><i class="fa-solid fa-lock"></i><input type="password" name="confirm_password" required placeholder="Confirm new password"></div>
          <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fa-solid fa-check"></i> Update Password</button>
        </form>
      <?php else: ?>
        <form method="POST" id="forgotForm">
          <?= csrf_field() ?><input type="hidden" name="action" value="request">
          <div class="form-group floating input-icon"><i class="fa-solid fa-envelope"></i><input type="email" name="email" id="femail" placeholder=" " required value="<?= e($_POST['email'] ?? '') ?>"><label for="femail">Email address</label></div>
          <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fa-solid fa-paper-plane"></i> Send Reset Link</button>
        </form>
      <?php endif; ?>
      <p class="center mt-2"><a href="<?= APP_URL ?>/login.php"><i class="fa-solid fa-arrow-left"></i> Back to login</a></p>
    </div>
  </main>
</div>

<div class="toast-wrap"></div>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
const pw = document.getElementById('password');
if (pw) pw.addEventListener('input', () => {
  const v = pw.value; let s = 0;
  if (v.length >= 8) s++; if (/[A-Z]/.test(v)) s++; if (/[0-9]/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v)) s++;
  const labels = ['Very weak','Weak','Fair','Good','Strong']; const colors = ['#EF4444','#F59E0B','#F59E0B','#22C55E','#22C55E'];
  document.getElementById('pwStrength').innerHTML = `<span style="color:${colors[s]}">${labels[s]}</span>`;
});
document.querySelectorAll('#forgotForm,#resetForm').forEach(f => f && f.addEventListener('submit', e => { if(!App.validateForm(f)){ e.preventDefault(); App.toast('Please complete the form.','error'); } }));
</script>
</body>
</html>
