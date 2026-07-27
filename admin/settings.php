<?php
/** Admin: Settings — club info, currency, timezone, late threshold, admin profile. */
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$u = current_user();
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'settings') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) $error = 'Invalid token.';
    else {
        foreach (['site_name','club_name','currency','timezone','late_threshold'] as $k) {
            if (isset($_POST[$k])) update_setting($k, clean($_POST[$k]));
        }
        log_activity('Updated settings', $u['id'], 'admin');
        $success = 'Settings saved.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profile') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) $error = 'Invalid token.';
    else {
        $name = clean($_POST['name']); $phone = clean($_POST['phone']);
        $avatar = handle_upload('avatar', $u['avatar'] ?? null);
        db()->prepare("UPDATE admins SET name=?, phone=?, avatar=? WHERE id=?")->execute([$name, $phone, $avatar, $u['id']]);
        $_SESSION['user']['name'] = $name; $_SESSION['user']['avatar'] = $avatar;
        log_activity('Admin updated profile', $u['id'], 'admin');
        $success = 'Profile updated.';
        $u['name'] = $name;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) $error = 'Invalid token.';
    else {
        $cur = $_POST['current_password'] ?? ''; $new = $_POST['new_password'] ?? ''; $cpw = $_POST['confirm_password'] ?? '';
        $stmt = db()->prepare("SELECT password FROM admins WHERE id=?"); $stmt->execute([$u['id']]);
        if (!password_verify($cur, $stmt->fetchColumn())) $error = 'Current password incorrect.';
        elseif (strlen($new) < 8) $error = 'New password too short.';
        elseif ($new !== $cpw) $error = 'Passwords do not match.';
        else { db()->prepare("UPDATE admins SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_BCRYPT), $u['id']]); $success = 'Password changed.'; }
    }
}
$admin = db()->prepare("SELECT * FROM admins WHERE id=?"); $admin->execute([$u['id']]); $a = $admin->fetch();
?>
<div class="page-head"><div class="left"><h1>Settings</h1><p>Configure your club and account.</p></div></div>
<?php if ($error): ?><div data-auto-alert="error" style="display:none"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div data-auto-alert="success" style="display:none"><?= e($success) ?></div><?php endif; ?>

<div class="grid grid-cols-2">
  <div class="card"><div class="card-head"><h3>Club Settings</h3></div><div class="card-body">
    <form method="POST">
      <?= csrf_field() ?><input type="hidden" name="action" value="settings">
      <div class="form-group"><label>Site Name</label><input type="text" name="site_name" value="<?= e(get_setting('site_name')) ?>"></div>
      <div class="form-group"><label>Club Name</label><input type="text" name="club_name" value="<?= e(get_setting('club_name')) ?>"></div>
      <div class="form-row">
        <div class="form-group"><label>Currency Symbol</label><input type="text" name="currency" value="<?= e(get_setting('currency','$')) ?>" maxlength="3"></div>
        <div class="form-group"><label>Timezone</label><input type="text" name="timezone" value="<?= e(get_setting('timezone')) ?>"></div>
      </div>
      <div class="form-group"><label>Late Threshold (HH:MM)</label><input type="time" name="late_threshold" value="<?= e(substr(get_setting('late_threshold','09:00:00'),0,5)) ?>"><div class="form-help">Check-ins after this time are marked late.</div></div>
      <button class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Settings</button>
    </form>
  </div></div>

  <div class="card"><div class="card-head"><h3>Admin Profile</h3></div><div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <div class="profile-banner mb-2">
        <img class="avatar-lg" id="aprev" src="<?= e($a['avatar'] ?: APP_URL.'/assets/images/avatar.png') ?>">
        <div><h3><?= e($a['name']) ?></h3><p class="muted"><?= e($a['email']) ?></p><label class="btn btn-sm btn-secondary"><i class="fa-solid fa-camera"></i> Change<input type="file" name="avatar" accept="image/*" hidden onchange="document.getElementById('aprev').src=URL.createObjectURL(this.files[0])"></label></div>
      </div>
      <div class="form-group"><label>Name</label><input type="text" name="name" value="<?= e($a['name']) ?>"></div>
      <div class="form-group"><label>Phone</label><input type="tel" name="phone" value="<?= e($a['phone'] ?? '') ?>"></div>
      <button class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Profile</button>
    </form>
  </div></div>
</div>

<div class="card mt-3" style="max-width:540px"><div class="card-head"><h3>Change Admin Password</h3></div><div class="card-body">
  <form method="POST">
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
    <div class="form-group"><label>New Password</label><input type="password" name="new_password" id="np" required minlength="8"><div class="form-help" id="str"></div></div>
    <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" required></div>
    <button class="btn btn-primary"><i class="fa-solid fa-key"></i> Update Password</button>
  </form>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const np=document.getElementById('np');
if(np) np.addEventListener('input',()=>{const v=np.value;let s=0;if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;const l=['Very weak','Weak','Fair','Good','Strong'];const c=['#ef4444','#f59e0b','#f59e0b','#22c55e','#22c55e'];document.getElementById('str').innerHTML=`<span style="color:${c[s]}">${l[s]}</span>`;});
</script>
