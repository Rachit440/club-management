<?php
/** Member dashboard - Elite Club redesign: profile cover, animated stats,
 *  circular progress for membership, attendance chart, upcoming events,
 *  announcements, recent payments & attendance. */
require_once __DIR__ . '/../includes/header.php';
$u = current_user();
$mid = (int) $u['id'];

$member = db()->prepare("SELECT m.*, mp.name AS plan_name, mp.price AS plan_price
  FROM members m LEFT JOIN membership_plans mp ON mp.id=m.plan_id WHERE m.id=?");
$member->execute([$mid]); $m = $member->fetch();
if (!$m) { redirect(APP_URL . '/logout.php'); }

$totalDays = $m['expiry_date'] && $m['join_date'] ? max(1, (int)((strtotime($m['expiry_date']) - strtotime($m['join_date']))/86400)) : 1;
$daysLeft = $m['expiry_date'] ? max(0, (int)((strtotime($m['expiry_date']) - time())/86400)) : 0;
$daysUsed = $totalDays - $daysLeft;
$memPct = $totalDays > 0 ? round(($daysUsed / $totalDays) * 100) : 0;
$memPct = min(100, max(0, $memPct));

$attCountStmt = db()->prepare("SELECT COUNT(*) FROM attendance WHERE member_id=?"); $attCountStmt->execute([$mid]); $attCount = (int)$attCountStmt->fetchColumn();
$payStmt = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE member_id=? AND status='paid'"); $payStmt->execute([$mid]); $totalPaid = (float)$payStmt->fetchColumn();

$upcoming = db()->prepare("SELECT e.* FROM events e INNER JOIN event_registration r ON r.event_id=e.id WHERE r.member_id=? AND e.event_date >= CURDATE() AND e.status='upcoming' ORDER BY e.event_date ASC LIMIT 5");
$upcoming->execute([$mid]); $upcomingRows = $upcoming->fetchAll();

$recentPay = db()->prepare("SELECT * FROM payments WHERE member_id=? ORDER BY payment_date DESC LIMIT 4"); $recentPay->execute([$mid]); $recentPayRows = $recentPay->fetchAll();
$recentAtt = db()->prepare("SELECT * FROM attendance WHERE member_id=? ORDER BY check_date DESC LIMIT 4"); $recentAtt->execute([$mid]); $recentAttRows = $recentAtt->fetchAll();
$ann = db()->query("SELECT * FROM announcements WHERE status='published' AND audience IN ('all','members') ORDER BY created_at DESC LIMIT 2")->fetchAll();

$attRows = db()->prepare("SELECT check_date, COUNT(*) AS c FROM attendance WHERE member_id=? AND check_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY check_date ORDER BY check_date ASC");
$attRows->execute([$mid]); $ar = $attRows->fetchAll();
$attLabels = json_encode(array_map(fn($r) => date('D', strtotime($r['check_date'])), $ar));
$attRows->execute([$mid]); $attData = json_encode(array_map('intval', array_column($attRows->fetchAll(), 'c')));
$avatar = $m['photo'] ?: APP_URL.'/assets/images/avatar.png';
?>
<div class="page-head">
  <div class="left"><h1>Hello, <?= e(explode(' ', $u['name'])[0]) ?>!</h1><p>Your membership overview and latest updates.</p></div>
  <div class="right"><a class="btn btn-primary" href="<?= APP_URL ?>/member/profile.php"><i class="fa-solid fa-user-pen"></i> Update Profile</a></div>
</div>

<!-- Profile cover + summary -->
<div class="card mb-3" data-reveal>
  <div class="profile-cover">
    <div class="cover-blob a"></div><div class="cover-blob b"></div>
  </div>
  <div class="card-body" style="margin-top:-3.5rem">
    <div class="profile-banner">
      <img class="avatar-lg" src="<?= e($avatar) ?>" alt="photo" style="border:4px solid var(--surface)">
      <div style="flex:1">
        <h2 style="margin-bottom:.3rem"><?= e($m['full_name']) ?></h2>
        <p class="muted" style="margin-bottom:.5rem"><i class="fa-solid fa-id-card" style="color:var(--primary)"></i> <?= e($m['member_no']) ?> &middot; <i class="fa-solid fa-envelope" style="color:var(--primary)"></i> <?= e($m['email']) ?></p>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
          <span class="mem-badge"><i class="fa-solid fa-gem"></i> <?= e($m['plan_name'] ?? 'No Plan') ?></span>
          <span class="badge <?= $m['status']==='active'?'badge-success':'badge-muted' ?>"><?= e($m['status']) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($daysLeft <= 7 && $m['expiry_date']): ?>
<div class="card mb-3" style="border-left:4px solid var(--warning)" data-reveal>
  <div class="card-body flex-between"><div><i class="fa-solid fa-triangle-exclamation text-warning"></i> Your membership <?= $daysLeft == 0 ? 'expires today' : "expires in $daysLeft day(s)" ?> (<?= e(fmt_date($m['expiry_date'])) ?>).</div><a href="<?= APP_URL ?>/member/payments.php" class="btn btn-sm btn-warning">Renew Now</a></div>
</div>
<?php endif; ?>

<div class="grid grid-cols-4 mb-3" data-reveal>
  <div class="stat-card"><div class="ic blue"><i class="fa-solid fa-id-card"></i></div><div class="meta"><div class="label">Member No.</div><div class="value" style="font-size:1.25rem"><?= e($m['member_no']) ?></div></div></div>
  <div class="stat-card"><div class="ic green"><i class="fa-solid fa-circle-check"></i></div><div class="meta"><div class="label">Status</div><div class="value" style="font-size:1.25rem;text-transform:capitalize"><?= e($m['status']) ?></div></div></div>
  <div class="stat-card"><div class="ic amber"><i class="fa-solid fa-clock"></i></div><div class="meta"><div class="label">Days Left</div><div class="value" data-counter="<?= $daysLeft ?>">0</div></div></div>
  <div class="stat-card"><div class="ic purple"><i class="fa-solid fa-money-bill-wave"></i></div><div class="meta"><div class="label">Total Paid</div><div class="value" style="font-size:1.25rem"><?= fmt_money($totalPaid) ?></div></div></div>
</div>

<div class="grid grid-cols-2-1 mb-3" data-reveal>
  <div class="card">
    <div class="card-head"><h3>My Profile</h3><a href="<?= APP_URL ?>/member/profile.php" class="btn btn-sm btn-secondary">Edit</a></div>
    <div class="card-body">
      <ul class="info-list">
        <li><span><i class="fa-solid fa-phone"></i> Phone</span><span><?= e($m['phone'] ?: '-') ?></span></li>
        <li><span><i class="fa-solid fa-location-dot"></i> City</span><span><?= e($m['city'] ?: '-') ?></span></li>
        <li><span><i class="fa-solid fa-calendar"></i> Join Date</span><span><?= e(fmt_date($m['join_date'])) ?></span></li>
        <li><span><i class="fa-solid fa-calendar-xmark"></i> Expiry Date</span><span><?= e(fmt_date($m['expiry_date'])) ?></span></li>
        <li><span><i class="fa-solid fa-user-shield"></i> Emergency</span><span><?= e($m['emergency_contact'] ?: '-') ?></span></li>
      </ul>
    </div>
  </div>
  <div class="card">
    <div class="card-head"><h3>Membership Progress</h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:.8rem">
      <div class="circ-progress" data-circ="<?= $memPct ?>"><svg width="130" height="130"><circle class="track" cx="65" cy="65" r="56" fill="none" stroke-width="11"/><circle class="bar" cx="65" cy="65" r="56" fill="none" stroke-width="11"/></svg><div class="pct"><b>0%</b><span>Used</span></div></div>
      <p class="muted center" style="font-size:.85rem"><?= $daysLeft ?> day(s) remaining of <?= $totalDays ?> day plan</p>
    </div>
  </div>
</div>

<div class="grid grid-cols-2-1 mb-3" data-reveal>
  <div class="card">
    <div class="card-head"><h3>Attendance (7 days)</h3><span class="badge badge-info"><?= $attCount ?> total</span></div>
    <div class="card-body"><div class="chart-box sm"><canvas id="attChart"></canvas></div></div>
  </div>
  <div class="card">
    <div class="card-head"><h3>Upcoming Events</h3><a href="<?= APP_URL ?>/member/events.php" class="btn btn-sm btn-secondary">All</a></div>
    <div class="card-body">
      <?php if (!$upcomingRows): ?><div class="empty-state"><i class="fa-solid fa-calendar-day"></i><p>No upcoming events registered</p></div><?php else: ?>
      <ul class="timeline"><?php foreach ($upcomingRows as $ev): ?>
        <li><div class="dot"><i class="fa-solid fa-calendar-day"></i></div><div class="meta"><div><strong><?= e($ev['title']) ?></strong></div><div class="when"><?= e(fmt_date($ev['event_date'])) ?> at <?= e(substr($ev['event_time'],0,5)) ?> &middot; <?= e($ev['location']) ?></div></div></li>
      <?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="grid grid-cols-2 mb-3" data-reveal>
  <div class="card">
    <div class="card-head"><h3>Recent Payments</h3><a href="<?= APP_URL ?>/member/payments.php" class="btn btn-sm btn-secondary">All</a></div>
    <div class="card-body">
      <?php if (!$recentPayRows): ?><div class="empty-state"><i class="fa-solid fa-receipt"></i><p>No payments yet</p></div><?php else: ?>
      <ul class="timeline"><?php foreach ($recentPayRows as $p): ?>
        <li><div class="dot" style="background:linear-gradient(135deg,#22C55E,#16a34a)"><i class="fa-solid fa-money-bill-wave"></i></div><div class="meta"><div><strong><?= e(fmt_money($p['amount'])) ?></strong> &middot; <?= e($p['payment_method']) ?></div><div class="when"><?= e(fmt_date($p['payment_date'])) ?> &middot; <?= e($p['receipt_no']) ?></div></div></li>
      <?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-head"><h3>Recent Attendance</h3><a href="<?= APP_URL ?>/member/attendance.php" class="btn btn-sm btn-secondary">All</a></div>
    <div class="card-body">
      <?php if (!$recentAttRows): ?><div class="empty-state"><i class="fa-solid fa-calendar-check"></i><p>No attendance yet</p></div><?php else: ?>
      <ul class="timeline"><?php foreach ($recentAttRows as $a): ?>
        <li><div class="dot" style="background:linear-gradient(135deg,#06B6D4,#0891b2)"><i class="fa-solid fa-calendar-check"></i></div><div class="meta"><div><strong><?= e(fmt_date($a['check_date'])) ?></strong> at <?= e(substr($a['check_time'],0,5)) ?></div><div class="when"><span class="badge <?= $a['status']==='present'?'badge-success':'badge-warning' ?>"><?= e($a['status']) ?></span></div></div></li>
      <?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($ann): ?>
<div class="card" data-reveal>
  <div class="card-head"><h3><i class="fa-solid fa-bullhorn" style="color:var(--primary)"></i> Announcements</h3><a href="<?= APP_URL ?>/member/announcements.php" class="btn btn-sm btn-secondary">All</a></div>
  <div class="card-body">
    <?php foreach ($ann as $a): ?>
      <div class="mb-2" style="padding-bottom:.8rem;border-bottom:1px dashed var(--border)"><strong><?= e($a['title']) ?></strong><p class="muted" style="font-size:.8rem"><?= e(relative_time($a['created_at'])) ?></p><p style="font-size:.9rem"><?= e(substr($a['body'],0,140)) ?>...</p></div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family='Inter, sans-serif';
Chart.defaults.color=getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim();
new Chart(document.getElementById('attChart'),{type:'bar',data:{labels:<?= $attLabels ?>,datasets:[{label:'Check-ins',data:<?= $attData ?>,backgroundColor:(c)=>{const g=c.chart.ctx.createLinearGradient(0,0,0,200);g.addColorStop(0,'#4F46E5');g.addColorStop(1,'#06B6D4');return g;},borderRadius:6,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
</script>
