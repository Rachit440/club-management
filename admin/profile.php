<?php
/** Admin profile page - redirects to settings where profile is editable. */
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$u = current_user();
$admin = db()->prepare("SELECT * FROM admins WHERE id=?"); $admin->execute([$u['id']]); $a = $admin->fetch();
?>
<div class="page-head"><div class="left"><h1>My Profile</h1><p>Your administrator account details.</p></div>
  <div class="right"><a href="<?= APP_URL ?>/admin/settings.php" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit in Settings</a></div></div>
<div class="grid grid-cols-2-1">
  <div class="card"><div class="card-body">
    <div class="profile-banner mb-2"><img class="avatar-lg" src="<?= e($a['avatar'] ?: APP_URL.'/assets/images/avatar.png') ?>"><div><h2><?= e($a['name']) ?></h2><p class="muted"><?= e($a['email']) ?></p><span class="badge badge-primary"><?= e(ucfirst($a['role'])) ?></span></div></div>
    <ul class="info-list"><li><span>Name</span><span><?= e($a['name']) ?></span></li><li><span>Email</span><span><?= e($a['email']) ?></span></li><li><span>Phone</span><span><?= e($a['phone'] ?? '-') ?></span></li><li><span>Role</span><span><?= e(ucfirst($a['role'])) ?></span></li><li><span>Member since</span><span><?= e(fmt_date($a['created_at'])) ?></span></li></ul>
  </div></div>
  <div class="card"><div class="card-head"><h3>Recent Activity</h3></div><div class="card-body">
    <ul class="timeline"><?php $logs = db()->prepare("SELECT * FROM activity_logs WHERE user_id=? AND user_type='admin' ORDER BY created_at DESC LIMIT 8"); $logs->execute([$u['id']]); foreach($logs->fetchAll() as $l): ?><li><div class="dot"><i class="fa-solid fa-bolt"></i></div><div class="meta"><div><?= e($l['action']) ?></div><div class="when"><?= e(relative_time($l['created_at'])) ?></div></div></li><?php endforeach; if(!$logs->rowCount()):?><li class="muted">No recent activity</li><?php endif;?></ul>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
