<?php
/** Admin dashboard - Elite Club redesign: animated counters, circular
 *  progress, quick actions, upcoming events, recent payments, calendar,
 *  charts, activity timeline, latest announcement. */
require_once __DIR__ . '/../includes/header.php';

$u = current_user();
$total = stat_total_members();
$active = stat_active_members();
$expired = stat_expired_memberships();
$upcoming = stat_upcoming_events();
$monthRev = stat_monthly_revenue();
$attToday = stat_attendance_today();
$outstanding = stat_outstanding_payments();
$bday = stat_today_birthdays();

// Circular percentages
$activePct = $total > 0 ? round(($active / $total) * 100) : 0;
$expiredPct = $total > 0 ? round(($expired / $total) * 100) : 0;
$attRate = $total > 0 ? min(100, round(($attToday / max(1, $active)) * 100)) : 0;
$renewPct = $total > 0 ? 100 - $expiredPct : 100;

// Charts data
$revRows = db()->query("SELECT DATE_FORMAT(payment_date,'%b') AS m, COALESCE(SUM(amount),0) AS total
  FROM payments WHERE status='paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY YEAR(payment_date), MONTH(payment_date) ORDER BY payment_date ASC")->fetchAll();
$revLabels = json_encode(array_column($revRows, 'm'));
$revData = json_encode(array_map('floatval', array_column($revRows, 'total')));

$attRows = db()->query("SELECT DATE_FORMAT(check_date,'%a') AS d, COUNT(*) AS c FROM attendance
  WHERE check_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY check_date ORDER BY check_date ASC")->fetchAll();
$attLabels = json_encode(array_column($attRows, 'd'));
$attData = json_encode(array_map('intval', array_column($attRows, 'c')));

$planRows = db()->query("SELECT COALESCE(mp.name,'No Plan') AS name, COUNT(m.id) AS c
  FROM members m LEFT JOIN membership_plans mp ON mp.id=m.plan_id GROUP BY mp.id ORDER BY c DESC")->fetchAll();
$planLabels = json_encode(array_column($planRows, 'name'));
$planData = json_encode(array_map('intval', array_column($planRows, 'c')));

// Upcoming events widget + calendar markers
$events = db()->query("SELECT * FROM events WHERE event_date >= CURDATE() AND status='upcoming' ORDER BY event_date ASC LIMIT 5")->fetchAll();
$calEvents = [];
foreach (db()->query("SELECT event_date FROM events WHERE event_date >= DATE_SUB(CURDATE(), INTERVAL 5 DAY) AND event_date <= DATE_ADD(CURDATE(), INTERVAL 25 DAY)") as $er) {
    $calEvents[$er['event_date']] = 'Event';
}
// Recent payments
$recentPay = db()->query("SELECT p.*, m.full_name FROM payments p JOIN members m ON m.id=p.member_id ORDER BY p.created_at DESC LIMIT 5")->fetchAll();
$activities = recent_activities(6);
$ann = db()->query("SELECT * FROM announcements WHERE status='published' ORDER BY created_at DESC LIMIT 1")->fetch();
$clubName = get_setting('club_name', 'Elite Club');
?>
<div class="page-head">
  <div class="left"><h1>Welcome back, <?= e(explode(' ', $u['name'])[0]) ?>!</h1><p>Here's what's happening at <?= e($clubName) ?> today.</p></div>
  <div class="right">
    <button class="btn btn-secondary" onclick="App.printArea('#dashPrint')"><i class="fa-solid fa-print"></i> Print</button>
    <a class="btn btn-primary" href="<?= APP_URL ?>/admin/members.php?action=new"><i class="fa-solid fa-user-plus"></i> Add Member</a>
  </div>
</div>

<div id="dashPrint">
<!-- Stat cards with animated counters -->
<div class="grid grid-cols-4 mb-3" data-reveal>
  <div class="stat-card"><div class="ic blue"><i class="fa-solid fa-users"></i></div><div class="meta"><div class="label">Total Members</div><div class="value" data-counter="<?= $total ?>">0</div><div class="trend up"><i class="fa-solid fa-arrow-up"></i> All registered</div></div></div>
  <div class="stat-card"><div class="ic green"><i class="fa-solid fa-user-check"></i></div><div class="meta"><div class="label">Active Members</div><div class="value" data-counter="<?= $active ?>">0</div><div class="trend up"><?= $activePct ?>% of total</div></div></div>
  <div class="stat-card"><div class="ic amber"><i class="fa-solid fa-clock-rotate-left"></i></div><div class="meta"><div class="label">Expired Memberships</div><div class="value" data-counter="<?= $expired ?>">0</div><div class="trend down">Need renewal</div></div></div>
  <div class="stat-card"><div class="ic red"><i class="fa-solid fa-cake-candles"></i></div><div class="meta"><div class="label">Today's Birthdays</div><div class="value" data-counter="<?= $bday ?>">0</div><div class="trend">Say happy birthday!</div></div></div>
  <div class="stat-card"><div class="ic purple"><i class="fa-solid fa-calendar-day"></i></div><div class="meta"><div class="label">Upcoming Events</div><div class="value" data-counter="<?= $upcoming ?>">0</div><div class="trend up">Scheduled</div></div></div>
  <div class="stat-card"><div class="ic cyan"><i class="fa-solid fa-money-bill-wave"></i></div><div class="meta"><div class="label">Monthly Revenue</div><div class="value" data-counter="<?= $monthRev ?>" data-prefix="<?= e(get_setting('currency','$')) ?>" data-decimals="2">0</div><div class="trend up">This month</div></div></div>
  <div class="stat-card"><div class="ic green"><i class="fa-solid fa-calendar-check"></i></div><div class="meta"><div class="label">Attendance Today</div><div class="value" data-counter="<?= $attToday ?>">0</div><div class="trend">Check-ins</div></div></div>
  <div class="stat-card"><div class="ic amber"><i class="fa-solid fa-circle-exclamation"></i></div><div class="meta"><div class="label">Outstanding</div><div class="value" data-counter="<?= $outstanding ?>" data-prefix="<?= e(get_setting('currency','$')) ?>" data-decimals="2">0</div><div class="trend down">Pending</div></div></div>
</div>

<!-- Circular progress + quick actions + calendar -->
<div class="grid grid-cols-3 mb-3" data-reveal>
  <div class="card">
    <div class="card-head"><h3>Membership Health</h3></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;place-items:center">
      <div class="circ-progress" data-circ="<?= $activePct ?>"><svg width="120" height="120"><circle class="track" cx="60" cy="60" r="52" fill="none" stroke-width="10"/><circle class="bar" cx="60" cy="60" r="52" fill="none" stroke-width="10"/></svg><div class="pct"><b>0%</b><span>Active</span></div></div>
      <div class="circ-progress" data-circ="<?= $renewPct ?>"><svg width="120" height="120"><circle class="track" cx="60" cy="60" r="52" fill="none" stroke-width="10"/><circle class="bar" cx="60" cy="60" r="52" fill="none" stroke-width="10"/></svg><div class="pct"><b>0%</b><span>Renewed</span></div></div>
    </div>
  </div>
  <div class="card">
    <div class="card-head"><h3>Quick Actions</h3></div>
    <div class="card-body">
      <div class="quick-actions">
        <a href="<?= APP_URL ?>/admin/members.php?action=new"><i class="fa-solid fa-user-plus"></i> Add Member</a>
        <a href="<?= APP_URL ?>/admin/payments.php"><i class="fa-solid fa-plus"></i> Add Payment</a>
        <a href="<?= APP_URL ?>/admin/events.php"><i class="fa-solid fa-calendar-plus"></i> New Event</a>
        <a href="<?= APP_URL ?>/admin/attendance.php"><i class="fa-solid fa-check"></i> Mark Attendance</a>
        <a href="<?= APP_URL ?>/admin/announcements.php"><i class="fa-solid fa-bullhorn"></i> Announce</a>
        <a href="<?= APP_URL ?>/admin/reports.php"><i class="fa-solid fa-file-export"></i> Reports</a>
      </div>
    </div>
  </div>
  <div class="card cal-widget">
    <div class="card-head"><h3>Calendar</h3><i class="fa-regular fa-calendar" style="color:var(--primary)"></i></div>
    <div class="card-body" id="calWidget"></div>
  </div>
</div>

<!-- Charts row -->
<div class="grid grid-cols-2-1 mb-3" data-reveal>
  <div class="card">
    <div class="card-head"><h3>Revenue Overview</h3><span class="badge badge-success"><?= e(get_setting('currency','$')) ?><?= number_format($monthRev,2) ?> this month</span></div>
    <div class="card-body"><div class="chart-box"><canvas id="revenueChart"></canvas></div></div>
  </div>
  <div class="card">
    <div class="card-head"><h3>Membership Distribution</h3></div>
    <div class="card-body"><div class="chart-box"><canvas id="planChart"></canvas></div></div>
  </div>
</div>

<div class="grid grid-cols-2-1 mb-3" data-reveal>
  <div class="card">
    <div class="card-head"><h3>Weekly Attendance</h3></div>
    <div class="card-body"><div class="chart-box sm"><canvas id="attChart"></canvas></div></div>
  </div>
  <div class="card">
    <div class="card-head"><h3>Recent Activity</h3></div>
    <div class="card-body">
      <?php if (!$activities): ?><div class="empty-state"><i class="fa-solid fa-clock-rotate-left"></i><p>No recent activity</p></div><?php else: ?>
      <ul class="timeline">
        <?php foreach ($activities as $a): ?>
          <li><div class="dot"><i class="fa-solid fa-bolt"></i></div><div class="meta"><div><?= e($a['action']) ?></div><div class="when"><?= e(relative_time($a['created_at'])) ?> &middot; <?= e($a['user_type']) ?></div></div></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Upcoming events + recent payments -->
<div class="grid grid-cols-2 mb-3" data-reveal>
  <div class="card">
    <div class="card-head"><h3>Upcoming Events</h3><a href="<?= APP_URL ?>/admin/events.php" class="btn btn-sm btn-secondary">View all</a></div>
    <div class="card-body">
      <?php if (!$events): ?><div class="empty-state"><i class="fa-solid fa-calendar-day"></i><p>No upcoming events</p></div><?php else: ?>
      <ul class="timeline">
        <?php foreach ($events as $ev): ?>
          <li><div class="dot"><i class="fa-solid fa-calendar-day"></i></div><div class="meta"><div><strong><?= e($ev['title']) ?></strong></div><div class="when"><?= e(fmt_date($ev['event_date'])) ?> at <?= e(substr($ev['event_time'],0,5)) ?> &middot; <?= e($ev['location'] ?: '-') ?></div></div></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-head"><h3>Recent Payments</h3><a href="<?= APP_URL ?>/admin/payments.php" class="btn btn-sm btn-secondary">View all</a></div>
    <div class="card-body">
      <?php if (!$recentPay): ?><div class="empty-state"><i class="fa-solid fa-receipt"></i><p>No payments yet</p></div><?php else: ?>
      <ul class="timeline">
        <?php foreach ($recentPay as $p): ?>
          <li><div class="dot" style="background:linear-gradient(135deg,#22C55E,#16a34a)"><i class="fa-solid fa-money-bill-wave"></i></div><div class="meta"><div><strong><?= e(fmt_money($p['amount'])) ?></strong> from <?= e($p['full_name']) ?></div><div class="when"><?= e(relative_time($p['created_at'])) ?> &middot; <?= e($p['receipt_no']) ?></div></div></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Latest announcement -->
<?php if ($ann): ?>
<div class="card" data-reveal>
  <div class="card-head"><h3><i class="fa-solid fa-bullhorn" style="color:var(--primary)"></i> Latest Announcement</h3><a href="<?= APP_URL ?>/admin/announcements.php" class="btn btn-sm btn-secondary">Manage</a></div>
  <div class="card-body"><h3 style="margin-bottom:.4rem"><?= e($ann['title']) ?></h3><p class="muted" style="margin-bottom:.6rem"><?= e(relative_time($ann['created_at'])) ?></p><p><?= nl2br(e($ann['body'])) ?></p></div>
</div>
<?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim();
const grad = (ctx) => { const g = ctx.createLinearGradient(0,0,0,300); g.addColorStop(0,'rgba(79,70,229,.85)'); g.addColorStop(1,'rgba(6,182,212,.65)'); return g; };
new Chart(document.getElementById('revenueChart'), { type:'bar', data:{ labels:<?= $revLabels ?>, datasets:[{ label:'Revenue', data:<?= $revData ?>, backgroundColor:(c)=>grad(c.chart.ctx), borderRadius:8, borderSkipped:false }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{ beginAtZero:true, ticks:{callback:v=>v} } } } });
new Chart(document.getElementById('planChart'), { type:'doughnut', data:{ labels:<?= $planLabels ?>, datasets:[{ data:<?= $planData ?>, backgroundColor:['#4F46E5','#06B6D4','#22C55E','#F59E0B','#EF4444','#a855f7'], borderWidth:0, hoverOffset:8 }] }, options:{ responsive:true, maintainAspectRatio:false, cutout:'62%', plugins:{legend:{position:'bottom',labels:{usePointStyle:true,pointStyle:'circle',padding:14}}} } });
new Chart(document.getElementById('attChart'), { type:'line', data:{ labels:<?= $attLabels ?>, datasets:[{ label:'Check-ins', data:<?= $attData ?>, borderColor:'#06B6D4', backgroundColor:'rgba(6,182,212,.15)', fill:true, tension:.4, pointBackgroundColor:'#06B6D4', pointRadius:5, pointHoverRadius:7 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{ beginAtZero:true, ticks:{stepSize:1} } } } });
// Calendar
const calEvents = <?= json_encode($calEvents) ?>;
App.renderCalendar('calWidget', calEvents);
</script>
