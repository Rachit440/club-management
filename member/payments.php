<?php
/** Member: own payment history. */
require_once __DIR__ . '/../includes/header.php';
require_role('member');
$u = current_user(); $mid = (int)$u['id'];
$stmt = db()->prepare("SELECT * FROM payments WHERE member_id=? ORDER BY payment_date DESC");
$stmt->execute([$mid]); $rows = $stmt->fetchAll();
$total = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE member_id=? AND status='paid'"); $total->execute([$mid]);
?>
<div class="page-head"><div class="left"><h1>My Payments</h1><p><?= count($rows) ?> payment record(s)</p></div>
  <div class="right"><button class="btn btn-secondary" onclick="exportPay()"><i class="fa-solid fa-file-csv"></i> Export</button></div></div>

<div class="grid grid-cols-3 mb-3">
  <div class="stat-card"><div class="ic green"><i class="fa-solid fa-circle-check"></i></div><div class="meta"><div class="label">Total Paid</div><div class="value"><?= fmt_money($total->fetchColumn()) ?></div></div></div>
  <div class="stat-card"><div class="ic amber"><i class="fa-solid fa-hourglass-half"></i></div><div class="meta"><div class="label">Pending</div><div class="value"><?php $p=db()->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE member_id=? AND status='pending'");$p->execute([$mid]);echo fmt_money($p->fetchColumn());?></div></div></div>
  <div class="stat-card"><div class="ic blue"><i class="fa-solid fa-receipt"></i></div><div class="meta"><div class="label">Transactions</div><div class="value"><?= count($rows) ?></div></div></div>
</div>

<div class="card"><div class="card-body">
  <div class="table-wrap"><table class="data" id="payTable">
    <thead><tr><th>Receipt No.</th><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th></tr></thead>
    <tbody><?php if(!$rows):?><tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-receipt"></i><p>No payments yet</p></div></td></tr><?php else: foreach($rows as $r):?><tr>
      <td><strong><?= e($r['receipt_no']) ?></strong></td><td><?= e(fmt_date($r['payment_date'])) ?></td><td><?= fmt_money($r['amount']) ?></td>
      <td><?= e($r['payment_method']) ?></td><td><?= e($r['reference_no']?:'-') ?></td>
      <td><span class="badge <?= $r['status']==='paid'?'badge-success':($r['status']==='pending'?'badge-warning':'badge-danger') ?>"><?= e($r['status']) ?></span></td>
    </tr><?php endforeach; endif; ?></tbody>
  </table></div>
  <div class="pager"><div class="info"></div><div class="pages"></div></div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
function exportPay(){const rows=[['Receipt','Date','Amount','Method','Reference','Status']];<?php foreach($rows as $r):?>rows.push([<?= json_encode($r['receipt_no'])?>,<?= json_encode($r['payment_date'])?>,<?= json_encode($r['amount'])?>,<?= json_encode($r['payment_method'])?>,<?= json_encode($r['reference_no'])?>,<?= json_encode($r['status'])?>]);<?php endforeach;?>App.exportCSV('my_payments.csv',rows);}
paginateTable('payTable',10);
</script>
