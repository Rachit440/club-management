<?php
/** Admin: Payments management with AJAX CRUD + receipts + filters. */
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) json_response(['error' => 'Invalid token'], 419);
    $action = $_POST['action'] ?? '';
    if ($action === 'store') {
        $mid = (int)($_POST['member_id'] ?? 0);
        if (!$mid) json_response(['error' => 'Select a member'], 422);
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) json_response(['error' => 'Amount must be > 0'], 422);
        $receipt = generate_receipt_no();
        db()->prepare("INSERT INTO payments (receipt_no,member_id,amount,payment_date,payment_method,reference_no,status,notes) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$receipt, $mid, $amount, $_POST['payment_date'] ?: date('Y-m-d'), $_POST['payment_method'] ?? 'Cash', clean($_POST['reference_no']), $_POST['status'] ?? 'paid', clean($_POST['notes'])]);
        log_activity("Recorded payment $receipt", $u['id'], 'admin');
        json_response(['ok' => true, 'receipt' => $receipt]);
    }
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0); if (!$id) json_response(['error' => 'Invalid payment'], 422);
        db()->prepare("UPDATE payments SET member_id=?,amount=?,payment_date=?,payment_method=?,reference_no=?,status=?,notes=? WHERE id=?")
            ->execute([(int)$_POST['member_id'], (float)$_POST['amount'], $_POST['payment_date'], $_POST['payment_method'], clean($_POST['reference_no']), $_POST['status'], clean($_POST['notes']), $id]);
        log_activity("Updated payment #$id", $u['id'], 'admin');
        json_response(['ok' => true]);
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("DELETE FROM payments WHERE id=?")->execute([$id]);
        log_activity("Deleted payment #$id", $u['id'], 'admin');
        json_response(['ok' => true]);
    }
}
if (($_GET['action'] ?? '') === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM payments WHERE id=?"); $stmt->execute([$id]);
    json_response($stmt->fetch() ?: ['error' => 'Not found']);
}

// Filters
$where = []; $params = [];
if (!empty($_GET['q'])) { $where[] = "(m.full_name LIKE ? OR p.receipt_no LIKE ?)"; $q='%'.$_GET['q'].'%'; array_push($params,$q,$q); }
if (!empty($_GET['status'])) { $where[] = "p.status=?"; $params[] = $_GET['status']; }
if (!empty($_GET['method'])) { $where[] = "p.payment_method=?"; $params[] = $_GET['method']; }
if (!empty($_GET['from'])) { $where[] = "p.payment_date>=?"; $params[] = $_GET['from']; }
if (!empty($_GET['to'])) { $where[] = "p.payment_date<=?"; $params[] = $_GET['to']; }
$clause = $where ? ('WHERE '.implode(' AND ',$where)) : '';
$sql = "SELECT p.*, m.full_name, m.member_no FROM payments p JOIN members m ON m.id=p.member_id $clause ORDER BY p.payment_date DESC, p.id DESC";
$stmt = db()->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();

$members = db()->query("SELECT id, member_no, full_name FROM members ORDER BY full_name")->fetchAll();
$monthTotal = stat_monthly_revenue();
$outstanding = stat_outstanding_payments();
$allTotal = db()->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'")->fetchColumn();
?>
<div class="page-head">
  <div class="left"><h1>Payments</h1><p><?= count($rows) ?> transaction(s)</p></div>
  <div class="right">
    <button class="btn btn-secondary" onclick="exportPay()"><i class="fa-solid fa-file-csv"></i> CSV</button>
    <button class="btn btn-secondary" onclick="App.printArea('#printPay')"><i class="fa-solid fa-print"></i> Print</button>
    <button class="btn btn-primary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Add Payment</button>
  </div>
</div>

<div class="grid grid-cols-3 mb-3">
  <div class="stat-card"><div class="ic green"><i class="fa-solid fa-circle-dollar-to-slot"></i></div><div class="meta"><div class="label">Total Revenue</div><div class="value"><?= fmt_money($allTotal) ?></div></div></div>
  <div class="stat-card"><div class="ic blue"><i class="fa-solid fa-calendar"></i></div><div class="meta"><div class="label">This Month</div><div class="value"><?= fmt_money($monthTotal) ?></div></div></div>
  <div class="stat-card"><div class="ic amber"><i class="fa-solid fa-circle-exclamation"></i></div><div class="meta"><div class="label">Outstanding</div><div class="value"><?= fmt_money($outstanding) ?></div></div></div>
</div>

<div class="toolbar">
  <div class="search grow"><i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:14px;color:var(--text-muted)"></i><input id="q" placeholder="Receipt or member name" value="<?= e($_GET['q']??'') ?>" oninput="dbSearch()"></div>
  <select id="fStatus" onchange="filt()"><option value="">All Status</option><option value="paid" <?=($_GET['status']??'')==='paid'?'selected':''?>>Paid</option><option value="pending" <?=($_GET['status']??'')==='pending'?'selected':''?>>Pending</option><option value="failed" <?=($_GET['status']??'')==='failed'?'selected':''?>>Failed</option></select>
  <select id="fMethod" onchange="filt()"><option value="">All Methods</option><?php foreach(['Cash','Card','Bank Transfer','UPI','Cheque'] as $m):?><option value="<?=$m?>" <?=($_GET['method']??'')===$m?'selected':''?>><?=$m?></option><?php endforeach;?></select>
  <input type="date" id="fFrom" value="<?= e($_GET['from']??'') ?>" onchange="filt()" title="From">
  <input type="date" id="fTo" value="<?= e($_GET['to']??'') ?>" onchange="filt()" title="To">
  <a href="<?= APP_URL ?>/admin/payments.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-right"></i></a>
</div>

<div class="card" id="printPay"><div class="card-body">
  <div class="table-wrap"><table class="data" id="payTable">
    <thead><tr><th>Receipt</th><th>Member</th><th>Amount</th><th>Date</th><th>Method</th><th>Reference</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody><?php if(!$rows):?><tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-receipt"></i><p>No payments found</p></div></td></tr><?php else: foreach($rows as $r):?><tr>
      <td><strong><?= e($r['receipt_no']) ?></strong></td>
      <td><?= e($r['full_name']) ?><div class="muted" style="font-size:.78rem"><?= e($r['member_no']) ?></div></td>
      <td><?= fmt_money($r['amount']) ?></td><td><?= e(fmt_date($r['payment_date'])) ?></td>
      <td><?= e($r['payment_method']) ?></td><td><?= e($r['reference_no']?:'-') ?></td>
      <td><span class="badge <?= $r['status']==='paid'?'badge-success':($r['status']==='pending'?'badge-warning':'badge-danger') ?>"><?= e($r['status']) ?></span></td>
      <td><div class="actions">
        <button class="btn btn-icon btn-ghost" title="Receipt" onclick="printReceipt(<?= $r['id'] ?>)"><i class="fa-solid fa-file-invoice"></i></button>
        <button class="btn btn-icon btn-ghost" title="Edit" onclick="editPay(<?= $r['id'] ?>)"><i class="fa-solid fa-pen"></i></button>
        <button class="btn btn-icon btn-ghost" title="Delete" onclick="delPay(<?= $r['id'] ?>)"><i class="fa-solid fa-trash" style="color:var(--error)"></i></button>
      </div></td>
    </tr><?php endforeach; endif; ?></tbody>
  </table></div>
  <div class="pager"><div class="info"></div><div class="pages"></div></div>
</div></div>

<div class="modal-overlay" id="payModal">
  <div class="modal">
    <div class="modal-head"><h3 id="payTitle">Add Payment</h3><button class="close" data-modal-close="payModal">&times;</button></div>
    <form id="payForm">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="pay_id">
      <div class="modal-body">
        <div class="form-group"><label>Member <span class="req">*</span></label><select name="member_id" required><option value="">Select member</option><?php foreach($members as $m):?><option value="<?= $m['id'] ?>"><?= e($m['member_no'].' - '.$m['full_name']) ?></option><?php endforeach;?></select></div>
        <div class="form-row">
          <div class="form-group"><label>Amount <span class="req">*</span></label><input type="number" name="amount" step="0.01" min="0" required></div>
          <div class="form-group"><label>Payment Date <span class="req">*</span></label><input type="date" name="payment_date" required value="<?= date('Y-m-d') ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Method</label><select name="payment_method"><?php foreach(['Cash','Card','Bank Transfer','UPI','Cheque'] as $m):?><option><?= $m ?></option><?php endforeach;?></select></div>
          <div class="form-group"><label>Status</label><select name="status"><option value="paid">Paid</option><option value="pending">Pending</option><option value="failed">Failed</option></select></div>
        </div>
        <div class="form-group"><label>Reference Number</label><input type="text" name="reference_no"></div>
        <div class="form-group"><label>Notes</label><textarea name="notes"></textarea></div>
      </div>
      <div class="modal-foot"><button type="button" class="btn btn-secondary" data-modal-close="payModal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP='<?= APP_URL ?>/admin/payments.php'; const TOKEN='<?= csrf_token() ?>';
let t;function dbSearch(){clearTimeout(t);t=setTimeout(filt,350);}
function filt(){const p=new URLSearchParams();const q=document.getElementById('q').value.trim();if(q)p.set('q',q);const s=document.getElementById('fStatus').value;if(s)p.set('status',s);const m=document.getElementById('fMethod').value;if(m)p.set('method',m);const f=document.getElementById('fFrom').value;if(f)p.set('from',f);const to=document.getElementById('fTo').value;if(to)p.set('to',to);location=APP+'?'+p;}
function openAdd(){document.getElementById('payTitle').textContent='Add Payment';document.getElementById('payForm').reset();document.getElementById('pay_id').value='';App.openModal('payModal');}
document.getElementById('payForm').addEventListener('submit',async e=>{e.preventDefault();if(!App.validateForm(e.target)){App.toast('Fix highlighted fields.','error');return;}App.showLoading();const fd=new FormData(e.target);fd.set('csrf_token',TOKEN);const edit=!!document.getElementById('pay_id').value;fd.set('action',edit?'update':'store');const r=await fetch(APP,{method:'POST',body:fd});const d=await r.json();App.hideLoading();if(d.error)App.toast(d.error,'error');else{App.toast(edit?'Payment updated':'Payment recorded '+d.receipt,'success');App.closeModal('payModal');setTimeout(()=>location.reload(),700);}});
async function editPay(id){App.showLoading();const r=await fetch(APP+'?action=get&id='+id);const p=await r.json();App.hideLoading();if(p.error){App.toast(p.error,'error');return;}document.getElementById('payTitle').textContent='Edit Payment';const f=document.getElementById('payForm');f.id.value=p.id;f.member_id.value=p.member_id;f.amount.value=p.amount;f.payment_date.value=p.payment_date;f.payment_method.value=p.payment_method;f.status.value=p.status;f.reference_no.value=p.reference_no;f.notes.value=p.notes;App.openModal('payModal');}
function delPay(id){App.confirm({title:'Delete payment?',text:'This payment record will be permanently removed.',danger:true,confirmText:'Delete',onConfirm:async()=>{const fd=new FormData();fd.set('csrf_token',TOKEN);fd.set('id',id);fd.set('action','delete');const r=await fetch(APP,{method:'POST',body:fd});const d=await r.json();if(d.error)App.toast(d.error,'error');else{App.toast('Payment deleted','success');setTimeout(()=>location.reload(),700);}}});}
function printReceipt(id){const w=window.open('','_blank','width=600,height=700');w.document.write('<html><head><title>Receipt</title><link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css"></head><body style="background:#fff;padding:2rem"><div style="max-width:500px;margin:auto"><h2><?= e(get_setting("club_name")) ?></h2><p>Official Payment Receipt</p><hr><div id="r"></div><hr><p style="text-align:center;color:#888">Thank you!</p></div></body></html>');w.document.close();fetch(APP+'?action=get&id='+id).then(r=>r.json()).then(p=>{w.document.getElementById('r').innerHTML=`<ul class="info-list"><li><span>Receipt No.</span><span>${p.receipt_no}</span></li><li><span>Member ID</span><span>${p.member_id}</span></li><li><span>Amount</span><span><?= e(get_setting('currency','$')) ?>${p.amount}</span></li><li><span>Date</span><span>${p.payment_date}</span></li><li><span>Method</span><span>${p.payment_method}</span></li><li><span>Reference</span><span>${p.reference_no||'-'}</span></li><li><span>Status</span><span>${p.status}</span></li></ul>`;w.focus();setTimeout(()=>w.print(),400);});}
function exportPay(){const rows=[['Receipt','Member','Amount','Date','Method','Reference','Status']];<?php foreach($rows as $r):?>rows.push([<?= json_encode($r['receipt_no'])?>,<?= json_encode($r['full_name'])?>,<?= json_encode($r['amount'])?>,<?= json_encode($r['payment_date'])?>,<?= json_encode($r['payment_method'])?>,<?= json_encode($r['reference_no'])?>,<?= json_encode($r['status'])?>]);<?php endforeach;?>App.exportCSV('payments.csv',rows);}
paginateTable('payTable',10);
</script>
