<?php
/** Member: change own password. */
require_once __DIR__ . '/../includes/header.php';
require_role('member');
$u = current_user(); $mid = (int)$u['id'];
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) $error = 'Invalid token.';
    else {
        $cur = clean($_POST['current_password'] ?? '');
        $new = $_POST['new_password'] ?? '';
        $cpw = $_POST['confirm_password'] ?? '';
        $stmt = db()->prepare("SELECT password FROM members WHERE id=?"); $stmt->execute([$mid]);
        $hash = $stmt->fetchColumn();
        if (!password_verify($cur, $hash)) $error = 'Current password is incorrect.';
        elseif (strlen($new) < 8) $error = 'New password must be at least 8 characters.';
        elseif (password_strength($new) < 3) $error = 'Password too weak. Use uppercase, numbers & symbols.';
        elseif ($new !== $cpw) $error = 'New passwords do not match.';
        else {
            db()->prepare("UPDATE members SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_BCRYPT), $mid]);
            log_activity('Member changed password', $mid, 'member');
            $success = 'Password changed successfully.';
        }
    }
}
?>
<div class="page-head"><div class="left"><h1>Change Password</h1><p>Keep your account secure.</p></div></div>
<?php if ($error): ?><div data-auto-alert="error" style="display:none"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div data-auto-alert="success" style="display:none"><?= e($success) ?></div><?php endif; ?>
<div class="card" style="max-width:520px">
  <div class="card-body">
    <form method="POST" id="pwForm">
      <?= csrf_field() ?>
      <div class="form-group"><label>Current Password <span class="req">*</span></label><div class="input-icon"><i class="fa-solid fa-lock"></i><input type="password" name="current_password" required></div></div>
      <div class="form-group"><label>New Password <span class="req">*</span></label><div class="input-icon"><i class="fa-solid fa-lock"></i><input type="password" name="new_password" id="newpw" required minlength="8"></div><div class="form-help" id="strength"></div></div>
      <div class="form-group"><label>Confirm New Password <span class="req">*</span></label><div class="input-icon"><i class="fa-solid fa-lock"></i><input type="password" name="confirm_password" required></div></div>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Update Password</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const np=document.getElementById('newpw');
np.addEventListener('input',()=>{const v=np.value;let s=0;if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;const l=['Very weak','Weak','Fair','Good','Strong'];const c=['#ef4444','#f59e0b','#f59e0b','#22c55e','#22c55e'];document.getElementById('strength').innerHTML=`<span style="color:${c[s]}">${l[s]}</span>`;});
document.getElementById('pwForm').addEventListener('submit',e=>{if(!App.validateForm(e.target)){e.preventDefault();App.toast('Please fill all fields.','error');}});
</script>
