<?php
/** Admin: Reports — members, payments, attendance, expiry, revenue. Export CSV/Print. */
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$tab = $_GET['tab'] ?? 'members';
$currency = get_setting('currency', '$');

$members = db()->query("SELECT m.*, mp.name AS plan_name FROM members m LEFT JOIN membership_plans mp ON mp.id=m.plan_id ORDER BY m.created_at DESC")->fetchAll();
$payments = db()->query("SELECT p.*, m.full_name, m.member_no FROM payments p JOIN members m ON m.id=p.member_id ORDER BY p.payment_date DESC")->fetchAll();
$attendance = db()->query("SELECT a.*, m.full_name, m.member_no FROM attendance a JOIN members m ON m.id=a.member_id ORDER BY a.check_date DESC")->fetchAll();
$expiring = db()->query("SELECT member_no, full_name, email, phone, expiry_date, status FROM members WHERE expiry_date IS NOT NULL ORDER BY expiry_date ASC")->fetchAll();
$revenue = db()->query("SELECT DATE_FORMAT(payment_date,'%Y-%m') AS month, COUNT(*) AS txns, COALESCE(SUM(amount),0) AS total FROM payments WHERE status='paid' GROUP BY month ORDER BY month DESC")->fetchAll();
?>
<div class="page-head">
  <div class="left"><h1>Reports</h1><p>Generate and export reports.</p></div>
  <div class="right">
    <button class="btn btn-secondary" id="csvBtn"><i class="fa-solid fa-file-csv"></i> CSV</button>
    <button class="btn btn-secondary" onclick="App.printArea('#reportArea')"><i class="fa-solid fa-print"></i> Print</button>
  </div>
</div>

<div class="toolbar">
  <a href="?tab=members" class="btn btn-sm <?= $tab==='members'?'btn-primary':'btn-secondary' ?>">Members</a>
  <a href="?tab=payments" class="btn btn-sm <?= $tab==='payments'?'btn-primary':'btn-secondary' ?>">Payments</a>
  <a href="?tab=attendance" class="btn btn-sm <?= $tab==='attendance'?'btn-primary':'btn-secondary' ?>">Attendance</a>
  <a href="?tab=expiry" class="btn btn-sm <?= $tab==='expiry'?'btn-primary':'btn-secondary' ?>">Membership Expiry</a>
  <a href="?tab=revenue" class="btn btn-sm <?= $tab==='revenue'?'btn-primary':'btn-secondary' ?>">Revenue</a>
</div>

<div class="card" id="reportArea"><div class="card-body">
<?php if ($tab === 'members'): ?>
  <h3 class="mb-2">Members Report</h3>
  <div class="table-wrap"><table class="data" id="repTable">
    <thead><tr><th>Member No</th><th>Name</th><th>Email</th><th>Phone</th><th>Plan</th><th>Join</th><th>Expiry</th><th>Status</th></tr></thead>
    <tbody><?php foreach($members as $m):?><tr><td><?= e($m['member_no']) ?></td><td><?= e($m['full_name']) ?></td><td><?= e($m['email']) ?></td><td><?= e($m['phone']?:'-') ?></td><td><?= e($m['plan_name']?:'-') ?></td><td><?= e(fmt_date($m['join_date'])) ?></td><td><?= e(fmt_date($m['expiry_date'])) ?></td><td><span class="badge <?= $m['status']==='active'?'badge-success':'badge-muted' ?>"><?= e($m['status']) ?></span></td></tr><?php endforeach; ?></tbody>
  </table></div>
<?php elseif ($tab === 'payments'): ?>
  <h3 class="mb-2">Payments Report</h3>
  <div class="table-wrap"><table class="data" id="repTable">
    <thead><tr><th>Receipt</th><th>Member</th><th>Amount</th><th>Date</th><th>Method</th><th>Status</th></tr></thead>
    <tbody><?php foreach($payments as $r):?><tr><td><?= e($r['receipt_no']) ?></td><td><?= e($r['full_name']) ?></td><td><?= fmt_money($r['amount']) ?></td><td><?= e(fmt_date($r['payment_date'])) ?></td><td><?= e($r['payment_method']) ?></td><td><span class="badge <?= $r['status']==='paid'?'badge-success':($r['status']==='pending'?'badge-warning':'badge-danger') ?>"><?= e($r['status']) ?></span></td></tr><?php endforeach; ?></tbody>
  </table></div>
<?php elseif ($tab === 'attendance'): ?>
  <h3 class="mb-2">Attendance Report</h3>
  <div class="table-wrap"><table class="data" id="repTable">
    <thead><tr><th>Member</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
    <tbody><?php foreach($attendance as $r):?><tr><td><?= e($r['full_name']) ?> (<?= e($r['member_no']) ?>)</td><td><?= e(fmt_date($r['check_date'])) ?></td><td><?= e(substr($r['check_time'],0,5)) ?></td><td><span class="badge <?= $r['status']==='present'?'badge-success':($r['status']==='late'?'badge-warning':'badge-danger') ?>"><?= e($r['status']) ?></span></td></tr><?php endforeach; ?></tbody>
  </table></div>
<?php elseif ($tab === 'expiry'): ?>
  <h3 class="mb-2">Membership Expiry Report</h3>
  <div class="table-wrap"><table class="data" id="repTable">
    <thead><tr><th>Member No</th><th>Name</th><th>Email</th><th>Phone</th><th>Expiry</th><th>Status</th></tr></thead>
    <tbody><?php foreach($expiring as $r):
      $expired = $r['expiry_date'] && $r['expiry_date'] < date('Y-m-d'); ?>
      <tr><td><?= e($r['member_no']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['phone']?:'-') ?></td><td><?= e(fmt_date($r['expiry_date'])) ?></td><td><?php if($expired):?><span class="badge badge-danger">Expired</span><?php else:?><span class="badge badge-success">Active</span><?php endif;?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
<?php else: ?>
  <h3 class="mb-2">Revenue Report</h3>
  <div class="table-wrap"><table class="data" id="repTable">
    <thead><tr><th>Month</th><th>Transactions</th><th>Total Revenue</th></tr></thead>
    <tbody><?php foreach($revenue as $r):?><tr><td><?= e($r['month']) ?></td><td><?= (int)$r['txns'] ?></td><td><?= fmt_money($r['total']) ?></td></tr><?php endforeach; ?></tbody>
  </table></div>
<?php endif; ?>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
document.getElementById('csvBtn').addEventListener('click', () => {
  const table = document.getElementById('repTable');
  const rows = [];
  table.querySelectorAll('tr').forEach(tr => {
    const cells = Array.from(tr.querySelectorAll('th,td')).map(td => td.innerText.trim());
    rows.push(cells);
  });
  App.exportCSV('report_<?= e($tab) ?>.csv', rows);
});
</script>
