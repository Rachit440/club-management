<?php
/** Member: view & update own profile - Elite Club redesign with cover,
 *  avatar, membership badge, stats cards, activity timeline, payment &
 *  attendance history. */
require_once __DIR__ . '/../includes/header.php';
require_role('member');
$u = current_user(); $mid = (int)$u['id'];
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { $error = 'Invalid session token.'; }
    else {
        $email = clean($_POST['email']);
        if (!validate_email($email)) { $error = 'Invalid email.'; }
        else {
            $dup = db()->prepare("SELECT id FROM members WHERE email=? AND id<>?"); $dup->execute([$email, $mid]);
            if ($dup->fetch()) { $error = 'Email already in use.'; }
            else {
                $cur = db()->prepare("SELECT photo FROM members WHERE id=?"); $cur->execute([$mid]); $old = $cur->fetchColumn();
                $photo = handle_upload('photo', $old);
                db()->prepare("UPDATE members SET full_name=?,email=?,photo=?,gender=?,date_of_birth=?,phone=?,address=?,city=?,state=?,emergency_contact=? WHERE id=?")
                    ->execute([clean($_POST['full_name']), $email, $photo, $_POST['gender'] ?: null, $_POST['date_of_birth'] ?: null,
                        clean($_POST['phone']), clean($_POST['address']), clean($_POST['city']), clean($_POST['state']), clean($_POST['emergency_contact']), $mid]);
                $_SESSION['user']['name'] = clean($_POST['full_name']); $_SESSION['user']['avatar'] = $photo;
                log_activity('Member updated profile', $mid, 'member');
                $success = 'Profile updated successfully.';
            }
        }
    }
}
$m = db()->prepare("SELECT m.*, mp.name AS plan_name FROM members m LEFT JOIN membership_plans mp ON mp.id=m.plan_id WHERE m.id=?");
$m->execute([$mid]); $member = $m->fetch();
if (!$member) { redirect(APP_URL . '/logout.php'); }

$payRows = db()->prepare("SELECT * FROM payments WHERE member_id=? ORDER BY payment_date DESC LIMIT 5"); $payRows->execute([$mid]);
$attRows = db()->prepare("SELECT * FROM attendance WHERE member_id=? ORDER BY check_date DESC LIMIT 5"); $attRows->execute([$mid]);
$logs = db()->prepare("SELECT * FROM activity_logs WHERE user_id=? AND user_type='member' ORDER BY created_at DESC LIMIT 6"); $logs->execute([$mid]);
$totalPaidStmt = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE member_id=? AND status='paid'"); $totalPaidStmt->execute([$mid]); $totalPaid = (float)$totalPaidStmt->fetchColumn();
$attCountStmt = db()->prepare("SELECT COUNT(*) FROM attendance WHERE member_id=?"); $attCountStmt->execute([$mid]); $attCount = (int)$attCountStmt->fetchColumn();
$avatar = $member['photo'] ?: APP_URL.'/assets/images/avatar.png';
?>
<div class="page-head"><div class="left"><h1>My Profile</h1><p>Update your personal information and view your activity.</p></div></div>

<?php if ($error): ?><div data-auto-alert="error" style="display:none"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div data-auto-alert="success" style="display:none"><?= e($success) ?></div><?php endif; ?>

<!-- Cover + avatar -->
<div class="card mb-3" data-reveal>
  <div class="profile-cover"><div class="cover-blob a"></div><div class="cover-blob b"></div></div>
  <div class="card-body" style="margin-top:-3.5rem">
    <div class="profile-banner">
      <img class="avatar-lg" src="<?= e($avatar) ?>" alt="photo" style="border:4px solid var(--surface)">
      <div style="flex:1">
        <h2 style="margin-bottom:.3rem"><?= e($member['full_name']) ?></h2>
        <p class="muted" style="margin-bottom:.5rem"><i class="fa-solid fa-id-card" style="color:var(--primary)"></i> <?= e($member['member_no']) ?></p>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
          <span class="mem-badge"><i class="fa-solid fa-gem"></i> <?= e($member['plan_name'] ?? 'No Plan') ?></span>
          <span class="badge <?= $member['status']==='active'?'badge-success':'badge-muted' ?>"><?= e($member['status']) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-3 mb-3" data-reveal>
  <div class="stat-card"><div class="ic green"><i class="fa-solid fa-money-bill-wave"></i></div><div class="meta"><div class="label">Total Paid</div><div class="value" style="font-size:1.3rem"><?= fmt_money($totalPaid) ?></div></div></div>
  <div class="stat-card"><div class="ic cyan"><i class="fa-solid fa-calendar-check"></i></div><div class="meta"><div class="label">Total Check-ins</div><div class="value" data-counter="<?= $attCount ?>">0</div></div></div>
  <div class="stat-card"><div class="ic blue"><i class="fa-solid fa-calendar"></i></div><div class="meta"><div class="label">Member Since</div><div class="value" style="font-size:1.1rem"><?= e(fmt_date($member['join_date'])) ?></div></div></div>
</div>

<div class="grid grid-cols-2-1" data-reveal>
  <div class="card">
    <div class="card-head"><h3>Profile Information</h3></div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data" id="profileForm">
        <?= csrf_field() ?><input type="hidden" name="action" value="update_profile">
        <div class="flex gap-2 mb-2" style="align-items:center">
          <img class="avatar-md" id="preview" src="<?= e($avatar) ?>">
          <label class="btn btn-sm btn-secondary"><i class="fa-solid fa-camera"></i> Change Photo <input type="file" name="photo" accept="image/*" hidden onchange="document.getElementById('preview').src=URL.createObjectURL(this.files[0])"></label>
        </div>
        <div class="form-row">
          <div class="form-group floating"><input type="text" name="full_name" id="p_name" placeholder=" " required value="<?= e($member['full_name']) ?>"><label for="p_name">Full Name</label></div>
          <div class="form-group floating"><input type="email" name="email" id="p_email" placeholder=" " required value="<?= e($member['email']) ?>"><label for="p_email">Email</label></div>
        </div>
        <div class="form-row-3">
          <div class="form-group floating"><input type="tel" name="phone" id="p_phone" placeholder=" " value="<?= e($member['phone']) ?>"><label for="p_phone">Phone</label></div>
          <div class="form-group"><label>Gender</label><select name="gender"><?php foreach(['Male','Female','Other'] as $g): ?><option <?= $member['gender']===$g?'selected':'' ?>><?= $g ?></option><?php endforeach; ?><option value="" <?= !$member['gender']?'selected':'' ?>>Select</option></select></div>
          <div class="form-group floating"><input type="date" name="date_of_birth" id="p_dob" placeholder=" " value="<?= e($member['date_of_birth']) ?>" max="<?= date('Y-m-d') ?>"><label for="p_dob" style="top:.35rem;font-size:.72rem;color:var(--primary)">Date of Birth</label></div>
        </div>
        <div class="form-row-3">
          <div class="form-group floating"><input type="text" name="city" id="p_city" placeholder=" " value="<?= e($member['city']) ?>"><label for="p_city">City</label></div>
          <div class="form-group floating"><input type="text" name="state" id="p_state" placeholder=" " value="<?= e($member['state']) ?>"><label for="p_state">State</label></div>
          <div class="form-group floating"><input type="text" name="emergency_contact" id="p_em" placeholder=" " value="<?= e($member['emergency_contact']) ?>"><label for="p_em">Emergency Contact</label></div>
        </div>
        <div class="form-group"><label>Address</label><textarea name="address" placeholder=" "><?= e($member['address']) ?></textarea></div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Activity Timeline</h3></div>
    <div class="card-body">
      <?php $logRows = $logs->fetchAll(); if (!$logRows): ?><div class="empty-state"><i class="fa-solid fa-clock-rotate-left"></i><p>No recent activity</p></div><?php else: ?>
      <ul class="timeline"><?php foreach ($logRows as $l): ?>
        <li><div class="dot"><i class="fa-solid fa-bolt"></i></div><div class="meta"><div><?= e($l['action']) ?></div><div class="when"><?= e(relative_time($l['created_at'])) ?></div></div></li>
      <?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Payment & attendance history -->
<div class="grid grid-cols-2 mt-3" data-reveal>
  <div class="card">
    <div class="card-head"><h3>Payment History</h3><a href="<?= APP_URL ?>/member/payments.php" class="btn btn-sm btn-secondary">View all</a></div>
    <div class="card-body">
      <?php $pr = $payRows->fetchAll(); if (!$pr): ?><div class="empty-state"><i class="fa-solid fa-receipt"></i><p>No payments yet</p></div><?php else: ?>
      <ul class="timeline"><?php foreach ($pr as $p): ?>
        <li><div class="dot" style="background:linear-gradient(135deg,#22C55E,#16a34a)"><i class="fa-solid fa-money-bill-wave"></i></div><div class="meta"><div><strong><?= e(fmt_money($p['amount'])) ?></strong> &middot; <?= e($p['payment_method']) ?></div><div class="when"><?= e(fmt_date($p['payment_date'])) ?> &middot; <?= e($p['receipt_no']) ?></div></div></li>
      <?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-head"><h3>Attendance History</h3><a href="<?= APP_URL ?>/member/attendance.php" class="btn btn-sm btn-secondary">View all</a></div>
    <div class="card-body">
      <?php $ar = $attRows->fetchAll(); if (!$ar): ?><div class="empty-state"><i class="fa-solid fa-calendar-check"></i><p>No attendance yet</p></div><?php else: ?>
      <ul class="timeline"><?php foreach ($ar as $a): ?>
        <li><div class="dot" style="background:linear-gradient(135deg,#06B6D4,#0891b2)"><i class="fa-solid fa-calendar-check"></i></div><div class="meta"><div><strong><?= e(fmt_date($a['check_date'])) ?></strong> at <?= e(substr($a['check_time'],0,5)) ?></div><div class="when"><span class="badge <?= $a['status']==='present'?'badge-success':'badge-warning' ?>"><?= e($a['status']) ?></span></div></div></li>
      <?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
document.getElementById('profileForm').addEventListener('submit',e=>{if(!App.validateForm(e.target)){e.preventDefault();App.toast('Please fix highlighted fields.','error');}});
</script>
