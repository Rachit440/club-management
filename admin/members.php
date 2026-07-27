<?php
/** Members management (Admin). CRUD via AJAX endpoints in this same file. */
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$u = current_user();

// -------- AJAX endpoints --------
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) json_response(['error' => 'Invalid token'], 419);
    $email = clean($_POST['email'] ?? '');
    if (!validate_email($email)) json_response(['error' => 'Invalid email'], 422);
    $dup = db()->prepare("SELECT id FROM members WHERE email=?"); $dup->execute([$email]);
    if ($dup->fetch()) json_response(['error' => 'Email already exists'], 422);
    $phone = clean($_POST['phone'] ?? '');
    if ($phone && !validate_phone($phone)) json_response(['error' => 'Invalid phone number'], 422);
    $pw = $_POST['password'] ?? 'member123';
    if (strlen($pw) < 6) json_response(['error' => 'Password too short (min 6)'], 422);
    $planId = (int)($_POST['plan_id'] ?: 0) ?: null;
    $joinDate = $_POST['join_date'] ?: date('Y-m-d');
    $expiry = $_POST['expiry_date'] ?: null;
    if ($planId && !$expiry) {
        $p = db()->prepare("SELECT duration_months FROM membership_plans WHERE id=?"); $p->execute([$planId]);
        $row = $p->fetch();
        if ($row) $expiry = date('Y-m-d', strtotime("+$row[duration_months] months", strtotime($joinDate)));
    }
    $photo = handle_upload('photo');
    $memberNo = generate_member_no();
    $stmt = db()->prepare("INSERT INTO members (member_no,full_name,email,password,photo,gender,date_of_birth,phone,address,city,state,plan_id,join_date,expiry_date,emergency_contact,status)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$memberNo, clean($_POST['full_name']), $email, password_hash($pw, PASSWORD_BCRYPT), $photo, $_POST['gender'] ?: null,
        $_POST['date_of_birth'] ?: null, $phone, clean($_POST['address']), clean($_POST['city']), clean($_POST['state']),
        $planId, $joinDate, $expiry, clean($_POST['emergency_contact']), $_POST['status'] ?? 'active']);
    $id = (int) db()->lastInsertId();
    log_activity("Added member #$memberNo", $u['id'], 'admin');
    json_response(['ok' => true, 'id' => $id, 'member_no' => $memberNo]);
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) json_response(['error' => 'Invalid token'], 419);
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) json_response(['error' => 'Invalid member'], 422);
    $email = clean($_POST['email'] ?? '');
    if (!validate_email($email)) json_response(['error' => 'Invalid email'], 422);
    $dup = db()->prepare("SELECT id FROM members WHERE email=? AND id<>?"); $dup->execute([$email, $id]);
    if ($dup->fetch()) json_response(['error' => 'Email already exists'], 422);
    $phone = clean($_POST['phone'] ?? '');
    if ($phone && !validate_phone($phone)) json_response(['error' => 'Invalid phone'], 422);
    $cur = db()->prepare("SELECT photo FROM members WHERE id=?"); $cur->execute([$id]); $oldPhoto = $cur->fetchColumn();
    $photo = handle_upload('photo', $oldPhoto);
    $planId = (int)($_POST['plan_id'] ?: 0) ?: null;
    $joinDate = $_POST['join_date'] ?: null;
    $expiry = $_POST['expiry_date'] ?: null;
    if ($planId && !$expiry && $joinDate) {
        $p = db()->prepare("SELECT duration_months FROM membership_plans WHERE id=?"); $p->execute([$planId]);
        $row = $p->fetch();
        if ($row) $expiry = date('Y-m-d', strtotime("+$row[duration_months] months", strtotime($joinDate)));
    }
    $stmt = db()->prepare("UPDATE members SET full_name=?,email=?,photo=?,gender=?,date_of_birth=?,phone=?,address=?,city=?,state=?,plan_id=?,join_date=?,expiry_date=?,emergency_contact=?,status=? WHERE id=?");
    $stmt->execute([clean($_POST['full_name']), $email, $photo, $_POST['gender'] ?: null, $_POST['date_of_birth'] ?: null,
        $phone, clean($_POST['address']), clean($_POST['city']), clean($_POST['state']), $planId,
        $joinDate, $expiry, clean($_POST['emergency_contact']), $_POST['status'] ?? 'active', $id]);
    log_activity("Updated member #$id", $u['id'], 'admin');
    json_response(['ok' => true]);
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) json_response(['error' => 'Invalid token'], 419);
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) json_response(['error' => 'Invalid member'], 422);
    $stmt = db()->prepare("DELETE FROM members WHERE id=?"); $stmt->execute([$id]);
    log_activity("Deleted member #$id", $u['id'], 'admin');
    json_response(['ok' => true]);
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM members WHERE id=?"); $stmt->execute([$id]);
    json_response($stmt->fetch() ?: ['error' => 'Not found']);
}

// -------- Filters --------
$where = []; $params = [];
if (!empty($_GET['q'])) { $where[] = "(full_name LIKE ? OR email LIKE ? OR member_no LIKE ? OR phone LIKE ?)"; $q = '%'.$_GET['q'].'%'; array_push($params, $q, $q, $q, $q); }
if (!empty($_GET['status'])) { $where[] = "status=?"; $params[] = $_GET['status']; }
if (!empty($_GET['plan_id'])) { $where[] = "plan_id=?"; $params[] = (int)$_GET['plan_id']; }
if (!empty($_GET['gender'])) { $where[] = "gender=?"; $params[] = $_GET['gender']; }
if (!empty($_GET['city'])) { $where[] = "city LIKE ?"; $params[] = '%'.$_GET['city'].'%'; }
$clause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$sql = "SELECT m.*, mp.name AS plan_name FROM members m LEFT JOIN membership_plans mp ON mp.id=m.plan_id $clause ORDER BY m.created_at DESC";
$stmt = db()->prepare($sql); $stmt->execute($params); $members = $stmt->fetchAll();

$plans = db()->query("SELECT id, name FROM membership_plans WHERE status='active' ORDER BY name")->fetchAll();
$cities = db()->query("SELECT DISTINCT city FROM members WHERE city<>'' ORDER BY city")->fetchAll();
?>
<div class="page-head">
  <div class="left"><h1>Members</h1><p><?= count($members) ?> member(s) total</p></div>
  <div class="right">
    <button class="btn btn-secondary" onclick="exportMembers()"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
    <button class="btn btn-primary" data-modal-open="memberModal" onclick="openAdd()"><i class="fa-solid fa-user-plus"></i> Add Member</button>
  </div>
</div>

<div class="toolbar">
  <div class="search grow"><i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:14px;color:var(--text-muted)"></i>
    <input type="text" id="searchQ" placeholder="Search name, email, member no, phone" value="<?= e($_GET['q'] ?? '') ?>" oninput="debounceSearch()">
  </div>
  <select id="fStatus" onchange="applyFilters()">
    <option value="">All Status</option>
    <option value="active" <?= ($_GET['status']??'')==='active'?'selected':'' ?>>Active</option>
    <option value="inactive" <?= ($_GET['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
    <option value="suspended" <?= ($_GET['status']??'')==='suspended'?'selected':'' ?>>Suspended</option>
  </select>
  <select id="fPlan" onchange="applyFilters()">
    <option value="">All Plans</option>
    <?php foreach ($plans as $p): ?><option value="<?= $p['id'] ?>" <?= ($_GET['plan_id']??'')==$p['id']?'selected':'' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
  </select>
  <select id="fGender" onchange="applyFilters()">
    <option value="">All Gender</option>
    <option value="Male" <?= ($_GET['gender']??'')==='Male'?'selected':'' ?>>Male</option>
    <option value="Female" <?= ($_GET['gender']??'')==='Female'?'selected':'' ?>>Female</option>
    <option value="Other" <?= ($_GET['gender']??'')==='Other'?'selected':'' ?>>Other</option>
  </select>
  <select id="fCity" onchange="applyFilters()">
    <option value="">All Cities</option>
    <?php foreach ($cities as $c): ?><option value="<?= e($c['city']) ?>" <?= ($_GET['city']??'')===$c['city']?'selected':'' ?>><?= e($c['city']) ?></option><?php endforeach; ?>
  </select>
  <a href="<?= APP_URL ?>/admin/members.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-right"></i></a>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data" id="membersTable">
      <thead><tr><th>Member</th><th>Contact</th><th>Plan</th><th>Join</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (!$members): ?><tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-users-slash"></i><p>No members found</p></div></td></tr><?php else: foreach ($members as $m): ?>
        <tr>
          <td><div class="flex gap-1" style="align-items:center"><img class="row-photo" src="<?= e($m['photo'] ?: APP_URL.'/assets/images/avatar.png') ?>"><div><strong><?= e($m['full_name']) ?></strong><div class="muted" style="font-size:.78rem"><?= e($m['member_no']) ?></div></div></div></td>
          <td><div><?= e($m['email']) ?></div><div class="muted" style="font-size:.78rem"><?= e($m['phone'] ?: '-') ?></div></td>
          <td><?= e($m['plan_name'] ?: '-') ?></td>
          <td><?= e(fmt_date($m['join_date'])) ?></td>
          <td><?= e(fmt_date($m['expiry_date'])) ?></td>
          <td><?php $st = $m['status']; $cls = $st==='active'?'badge-success':($st==='suspended'?'badge-danger':'badge-muted'); ?><span class="badge <?= $cls ?>"><?= e($st) ?></span></td>
          <td><div class="actions">
            <button class="btn btn-icon btn-ghost" title="View" onclick="viewMember(<?= $m['id'] ?>)"><i class="fa-solid fa-eye"></i></button>
            <button class="btn btn-icon btn-ghost" title="Edit" onclick="editMember(<?= $m['id'] ?>)"><i class="fa-solid fa-pen"></i></button>
            <button class="btn btn-icon btn-ghost" title="Delete" onclick="deleteMember(<?= $m['id'] ?>,'<?= e($m['full_name']) ?>')"><i class="fa-solid fa-trash" style="color:var(--error)"></i></button>
          </div></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="pager"><div class="info"></div><div class="pages"></div></div>
</div>

<!-- Member Modal -->
<div class="modal-overlay" id="memberModal">
  <div class="modal lg">
    <div class="modal-head"><h3 id="modalTitle">Add Member</h3><button class="close" data-modal-close="memberModal">&times;</button></div>
    <form id="memberForm" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="f_id">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label>Full Name <span class="req">*</span></label><input type="text" name="full_name" required></div>
          <div class="form-group"><label>Photo</label><input type="file" name="photo" accept="image/*"></div>
        </div>
        <div class="form-row-3">
          <div class="form-group"><label>Email <span class="req">*</span></label><input type="email" name="email" required></div>
          <div class="form-group"><label>Phone</label><input type="tel" name="phone" data-phone></div>
          <div class="form-group"><label>Gender</label><select name="gender"><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option></select></div>
        </div>
        <div class="form-row-3">
          <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" max="<?= date('Y-m-d') ?>"></div>
          <div class="form-group"><label>City</label><input type="text" name="city"></div>
          <div class="form-group"><label>State</label><input type="text" name="state"></div>
        </div>
        <div class="form-group"><label>Address</label><textarea name="address"></textarea></div>
        <div class="form-row-3">
          <div class="form-group"><label>Membership Plan</label><select name="plan_id"><option value="">Select plan</option><?php foreach ($plans as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label>Join Date</label><input type="date" name="join_date" value="<?= date('Y-m-d') ?>"></div>
          <div class="form-group"><label>Expiry Date</label><input type="date" name="expiry_date"><div class="form-help">Auto-calculated if plan selected &amp; empty</div></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Emergency Contact</label><input type="text" name="emergency_contact"></div>
          <div class="form-group"><label>Status</label><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option></select></div>
        </div>
        <p class="form-help">Default password for new members: <strong>member123</strong> (change later from profile).</p>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close="memberModal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Member</button>
      </div>
    </form>
  </div>
</div>

<!-- View Modal -->
<div class="modal-overlay" id="viewModal">
  <div class="modal">
    <div class="modal-head"><h3>Member Details</h3><button class="close" data-modal-close="viewModal">&times;</button></div>
    <div class="modal-body" id="viewBody"></div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP = '<?= APP_URL ?>/admin/members.php';
const TOKEN = '<?= csrf_token() ?>';
let timer;
function debounceSearch(){ clearTimeout(timer); timer=setTimeout(applyFilters,350); }
function applyFilters(){
  const p = new URLSearchParams();
  const q=document.getElementById('searchQ').value.trim();
  if(q)p.set('q',q);
  const s=document.getElementById('fStatus').value; if(s)p.set('status',s);
  const pl=document.getElementById('fPlan').value; if(pl)p.set('plan_id',pl);
  const g=document.getElementById('fGender').value; if(g)p.set('gender',g);
  const c=document.getElementById('fCity').value; if(c)p.set('city',c);
  window.location = APP + '?' + p.toString();
}
function openAdd(){ document.getElementById('modalTitle').textContent='Add Member'; document.getElementById('memberForm').reset(); document.getElementById('f_id').value=''; App.openModal('memberModal'); }

document.getElementById('memberForm').addEventListener('submit', async function(e){
  e.preventDefault();
  if(!App.validateForm(this)){ App.toast('Please fix the highlighted fields.','error'); return; }
  App.showLoading();
  const fd = new FormData(this);
  fd.set('csrf_token', TOKEN);
  const isEdit = !!document.getElementById('f_id').value;
  fd.append('action', isEdit ? 'update' : 'store');
  try {
    const r = await fetch(APP + '?action=' + (isEdit?'update':'store'), { method:'POST', body: fd });
    const data = await r.json();
    App.hideLoading();
    if(data.error){ App.toast(data.error,'error'); }
    else { App.toast(isEdit?'Member updated':'Member added ('+data.member_no+')','success'); App.closeModal('memberModal'); setTimeout(()=>window.location.reload(),700); }
  } catch(err){ App.hideLoading(); App.toast('Request failed','error'); }
});

async function editMember(id){
  App.showLoading();
  const r = await fetch(APP + '?action=get&id='+id); const m = await r.json(); App.hideLoading();
  if(m.error){ App.toast(m.error,'error'); return; }
  document.getElementById('modalTitle').textContent='Edit Member';
  const f = document.getElementById('memberForm');
  f.id.value = m.id; f.full_name.value = m.full_name; f.email.value = m.email; f.phone.value = m.phone||'';
  f.gender.value = m.gender||''; f.date_of_birth.value = m.date_of_birth||''; f.city.value = m.city||'';
  f.state.value = m.state||''; f.address.value = m.address||''; f.plan_id.value = m.plan_id||'';
  f.join_date.value = m.join_date||''; f.expiry_date.value = m.expiry_date||''; f.emergency_contact.value = m.emergency_contact||'';
  f.status.value = m.status||'active';
  App.openModal('memberModal');
}

async function viewMember(id){
  App.showLoading();
  const r = await fetch(APP+'?action=get&id='+id); const m = await r.json(); App.hideLoading();
  if(m.error){ App.toast(m.error,'error'); return; }
  document.getElementById('viewBody').innerHTML = `
    <div class="profile-banner mb-2"><img class="avatar-lg" src="${m.photo||'<?= APP_URL ?>/assets/images/avatar.png'}"><div><h3>${m.full_name}</h3><p class="muted">${m.member_no}</p></div></div>
    <ul class="info-list">
      <li><span>Email</span><span>${m.email||'-'}</span></li>
      <li><span>Phone</span><span>${m.phone||'-'}</span></li>
      <li><span>Gender</span><span>${m.gender||'-'}</span></li>
      <li><span>Date of Birth</span><span>${m.date_of_birth||'-'}</span></li>
      <li><span>Address</span><span>${[m.address,m.city,m.state].filter(Boolean).join(', ')||'-'}</span></li>
      <li><span>Join Date</span><span>${m.join_date||'-'}</span></li>
      <li><span>Expiry Date</span><span>${m.expiry_date||'-'}</span></li>
      <li><span>Emergency Contact</span><span>${m.emergency_contact||'-'}</span></li>
      <li><span>Status</span><span><span class="badge ${m.status==='active'?'badge-success':(m.status==='suspended'?'badge-danger':'badge-muted')}">${m.status}</span></span></li>
    </ul>`;
  App.openModal('viewModal');
}

function deleteMember(id, name){
  App.confirm({ title:'Delete Member?', text:`This will permanently remove "${name}" and related records.`, confirmText:'Delete', danger:true, onConfirm: async ()=>{
    const fd = new FormData(); fd.set('csrf_token', TOKEN); fd.set('id', id); fd.set('action','delete');
    const r = await fetch(APP+'?action=delete',{method:'POST',body:fd}); const d = await r.json();
    if(d.error) App.toast(d.error,'error'); else { App.toast('Member deleted','success'); setTimeout(()=>window.location.reload(),700); }
  }});
}

function exportMembers(){
  const rows = [['Member No','Name','Email','Phone','City','Plan','Join','Expiry','Status']];
  <?php foreach ($members as $m): ?>
  rows.push([<?= json_encode($m['member_no']) ?>, <?= json_encode($m['full_name']) ?>, <?= json_encode($m['email']) ?>, <?= json_encode($m['phone']) ?>, <?= json_encode($m['city']) ?>, <?= json_encode($m['plan_name']) ?>, <?= json_encode($m['join_date']) ?>, <?= json_encode($m['expiry_date']) ?>, <?= json_encode($m['status']) ?>]);
  <?php endforeach; ?>
  App.exportCSV('members.csv', rows);
}
paginateTable('membersTable', 10);
</script>
