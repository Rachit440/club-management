<?php
/** Member: own attendance history. */
require_once __DIR__ . '/../includes/header.php';
require_role('member');
$u = current_user(); $mid = (int)$u['id'];
$stmt = db()->prepare("SELECT * FROM attendance WHERE member_id=? ORDER BY check_date DESC, check_time DESC");
$stmt->execute([$mid]); $rows = $stmt->fetchAll();
$total = count($rows);
$present = 0; $late = 0;
foreach ($rows as $r) { if ($r['status']==='present') $present++; elseif ($r['status']==='late') $late++; }
?>
<div class="page-head"><div class="left"><h1>My Attendance</h1><p><?= $total ?> check-in(s) total</p></div>
  <div class="right"><button class="btn btn-secondary" onclick="App.printArea('.card')"><i class="fa-solid fa-print"></i> Print</button></div></div>

<div class="grid grid-cols-3 mb-3">
  <div class="stat-card"><div class="ic green"><i class="fa-solid fa-calendar-check"></i></div><div class="meta"><div class="label">Total</div><div class="value"><?= $total ?></div></div></div>
  <div class="stat-card"><div class="ic blue"><i class="fa-solid fa-circle-check"></i></div><div class="meta"><div class="label">On time</div><div class="value"><?= $present ?></div></div></div>
  <div class="stat-card"><div class="ic amber"><i class="fa-solid fa-clock"></i></div><div class="meta"><div class="label">Late</div><div class="value"><?= $late ?></div></div></div>
</div>

<div class="card"><div class="card-body">
  <div class="table-wrap"><table class="data" id="attTable">
    <thead><tr><th>Date</th><th>Time</th><th>Status</th><th>Note</th></tr></thead>
    <tbody><?php if(!$rows):?><tr><td colspan="4"><div class="empty-state"><i class="fa-solid fa-calendar-check"></i><p>No attendance records</p></div></td></tr><?php else: foreach($rows as $r):?><tr>
      <td><?= e(fmt_date($r['check_date'])) ?></td><td><?= e(substr($r['check_time'],0,5)) ?></td>
      <td><span class="badge <?= $r['status']==='present'?'badge-success':($r['status']==='late'?'badge-warning':'badge-danger') ?>"><?= e($r['status']) ?></span></td>
      <td><?= e($r['note']?:'-') ?></td>
    </tr><?php endforeach; endif; ?></tbody>
  </table></div>
  <div class="pager"><div class="info"></div><div class="pages"></div></div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>paginateTable('attTable',10);</script>
