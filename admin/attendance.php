<?php
/** Admin: Attendance — mark today's attendance, view history, export. */
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$u = current_user();
$lateThreshold = get_setting('late_threshold', '09:00:00');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) json_response(['error' => 'Invalid token'], 419);
    $mid = (int)($_POST['member_id'] ?? 0);
    if (!$mid) json_response(['error' => 'Select a member'], 422);
    $time = $_POST['check_time'] ?: date('H:i:s');
    $status = $time > $lateThreshold ? 'late' : 'present';
    $date = $_POST['check_date'] ?: date('Y-m-d');
    try {
        db()->prepare("INSERT INTO attendance (member_id,check_date,check_time,status,note) VALUES (?,?,?,?,?)")
            ->execute([$mid, $date, $time, $status, clean($_POST['note'] ?? '')]);
        log_activity("Marked attendance for member #$mid", $u['id'], 'admin');
        json_response(['ok' => true, 'status' => $status]);
    } catch (PDOException $ex) { json_response(['error' => 'Already marked for today'], 422); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) json_response(['error' => 'Invalid token'], 419);
    $id = (int)($_POST['id'] ?? 0);
    db()->prepare("DELETE FROM attendance WHERE id=?")->execute([$id]);
    json_response(['ok' => true]);
}

// Filters
$where = []; $params = [];
if (!empty($_GET['q'])) { $where[] = "(m.full_name LIKE ? OR m.member_no LIKE ?)"; $q='%'.$_GET['q'].'%'; array_push($params,$q,$q); }
if (!empty($_GET['date'])) { $where[] = "a.check_date=?"; $params[] = $_GET['date']; }
if (!empty($_GET['status'])) { $where[] = "a.status=?"; $params[] = $_GET['status']; }
$clause = $where ? ('WHERE '.implode(' AND ',$where)) : '';
$sql = "SELECT a.*, m.full_name, m.member_no FROM attendance a JOIN members m ON m.id=a.member_id $clause ORDER BY a.check_date DESC, a.check_time DESC";
$stmt = db()->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();

$members = db()->query("SELECT id, member_no, full_name FROM members WHERE status='active' ORDER BY full_name")->fetchAll();
$todayCount = stat_attendance_today();
$presentToday = (int) db()->query("SELECT COUNT(*) FROM attendance WHERE check_date=CURDATE() AND status='present'")->fetchColumn();
$lateToday = (int) db()->query("SELECT COUNT(*) FROM attendance WHERE check_date=CURDATE() AND status='late'")->fetchColumn();
?>
<div class="page-head">
  <div class="left"><h1>Attendance</h1><p>Track member check-ins.</p></div>
  <div class="right">
    <button class="btn btn-secondary" onclick="exportAtt()"><i class="fa-solid fa-file-csv"></i> Export</button>
    <button class="btn btn-primary" data-modal-open="attModal"><i class="fa-solid fa-circle-check"></i> Mark Attendance</button>
  </div>
</div>

<div class="grid grid-cols-3 mb-3">
  <div class="stat-card"><div class="ic blue"><i class="fa-solid fa-calendar-check"></i></div><div class="meta"><div class="label">Today's Check-ins</div><div class="value"><?= $todayCount ?></div></div></div>
  <div class="stat-card"><div class="ic green"><i class="fa-solid fa-circle-check"></i></div><div class="meta"><div class="label">On Time Today</div><div class="value"><?= $presentToday ?></div></div></div>
  <div class="stat-card"><div class="ic amber"><i class="fa-solid fa-clock"></i></div><div class="meta"><div class="label">Late Today</div><div class="value"><?= $lateToday ?></div></div></div>
</div>

<div class="toolbar">
  <div class="search grow"><i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:14px;color:var(--text-muted)"></i><input id="q" placeholder="Member name or no." value="<?= e($_GET['q']??'') ?>" oninput="dbSearch()"></div>
  <input type="date" id="fDate" value="<?= e($_GET['date']??'') ?>" onchange="filt()" title="Date">
  <select id="fStatus" onchange="filt()"><option value="">All Status</option><option value="present" <?=($_GET['status']??'')==='present'?'selected':''?>>Present</option><option value="late" <?=($_GET['status']??'')==='late'?'selected':''?>>Late</option><option value="absent" <?=($_GET['status']??'')==='absent'?'selected':''?>>Absent</option></select>
  <a href="<?= APP_URL ?>/admin/attendance.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-right"></i></a>
</div>

<div class="card"><div class="card-body">
  <div class="table-wrap"><table class="data" id="attTable">
    <thead><tr><th>Member</th><th>Date</th><th>Time</th><th>Status</th><th>Note</th><th>Actions</th></tr></thead>
    <tbody><?php if(!$rows):?><tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-calendar-check"></i><p>No attendance records</p></div></td></tr><?php else: foreach($rows as $r):?><tr>
      <td><?= e($r['full_name']) ?><div class="muted" style="font-size:.78rem"><?= e($r['member_no']) ?></div></td>
      <td><?= e(fmt_date($r['check_date'])) ?></td><td><?= e(substr($r['check_time'],0,5)) ?></td>
      <td><span class="badge <?= $r['status']==='present'?'badge-success':($r['status']==='late'?'badge-warning':'badge-danger') ?>"><?= e($r['status']) ?></span></td>
      <td><?= e($r['note']?:'-') ?></td>
      <td><button class="btn btn-icon btn-ghost" onclick="delAtt(<?= $r['id'] ?>)"><i class="fa-solid fa-trash" style="color:var(--error)"></i></button></td>
    </tr><?php endforeach; endif; ?></tbody>
  </table></div>
  <div class="pager"><div class="info"></div><div class="pages"></div></div>
</div></div>

<div class="modal-overlay" id="attModal">
  <div class="modal">
    <div class="modal-head"><h3>Mark Attendance</h3><button class="close" data-modal-close="attModal">&times;</button></div>
    <form id="attForm">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="form-group"><label>Member <span class="req">*</span></label><select name="member_id" required><option value="">Select member</option><?php foreach($members as $m):?><option value="<?= $m['id'] ?>"><?= e($m['member_no'].' - '.$m['full_name']) ?></option><?php endforeach;?></select></div>
        <div class="form-row">
          <div class="form-group"><label>Date</label><input type="date" name="check_date" value="<?= date('Y-m-d') ?>"></div>
          <div class="form-group"><label>Time</label><input type="time" name="check_time" value="<?= date('H:i') ?>"></div>
        </div>
        <div class="form-help">Entries after <?= e(substr($lateThreshold,0,5)) ?> are marked as <strong>late</strong>.</div>
        <div class="form-group mt-2"><label>Note</label><input type="text" name="note" placeholder="Optional"></div>
      </div>
      <div class="modal-foot"><button type="button" class="btn btn-secondary" data-modal-close="attModal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Mark</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP='<?= APP_URL ?>/admin/attendance.php'; const TOKEN='<?= csrf_token() ?>';
let t;function dbSearch(){clearTimeout(t);t=setTimeout(filt,350);}
function filt(){const p=new URLSearchParams();const q=document.getElementById('q').value.trim();if(q)p.set('q',q);const d=document.getElementById('fDate').value;if(d)p.set('date',d);const s=document.getElementById('fStatus').value;if(s)p.set('status',s);location=APP+'?'+p;}
document.getElementById('attForm').addEventListener('submit',async e=>{e.preventDefault();if(!App.validateForm(e.target)){App.toast('Select a member.','error');return;}App.showLoading();const fd=new FormData(e.target);fd.set('csrf_token',TOKEN);fd.set('action','mark');const r=await fetch(APP,{method:'POST',body:fd});const d=await r.json();App.hideLoading();if(d.error)App.toast(d.error,'error');else{App.toast('Attendance marked as '+d.status,'success');App.closeModal('attModal');setTimeout(()=>location.reload(),700);}});
function delAtt(id){App.confirm({title:'Delete record?',text:'Remove this attendance entry?',danger:true,confirmText:'Delete',onConfirm:async()=>{const fd=new FormData();fd.set('csrf_token',TOKEN);fd.set('id',id);fd.set('action','delete');const r=await fetch(APP,{method:'POST',body:fd});const d=await r.json();if(d.error)App.toast(d.error,'error');else{App.toast('Deleted','success');setTimeout(()=>location.reload(),700);}}});}
function exportAtt(){const rows=[['Member','Date','Time','Status','Note']];<?php foreach($rows as $r):?>rows.push([<?= json_encode($r['full_name'])?>,<?= json_encode($r['check_date'])?>,<?= json_encode($r['check_time'])?>,<?= json_encode($r['status'])?>,<?= json_encode($r['note'])?>]);<?php endforeach;?>App.exportCSV('attendance.csv',rows);}
paginateTable('attTable',10);
</script>
