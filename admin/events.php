<?php
/** Admin: Events CRUD + view registrations. */
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) json_response(['error' => 'Invalid token'], 419);
    $action = $_POST['action'] ?? '';
    if ($action === 'store') {
        $title = clean($_POST['title']); if (!$title) json_response(['error' => 'Title required'], 422);
        db()->prepare("INSERT INTO events (title,description,location,event_date,event_time,organizer,max_participants,status) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$title, clean($_POST['description']), clean($_POST['location']), $_POST['event_date'], $_POST['event_time'].':00', clean($_POST['organizer']), (int)($_POST['max_participants'] ?? 0), $_POST['status'] ?? 'upcoming']);
        log_activity("Created event $title", $u['id'], 'admin');
        json_response(['ok' => true]);
    }
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0); if (!$id) json_response(['error' => 'Invalid event'], 422);
        db()->prepare("UPDATE events SET title=?,description=?,location=?,event_date=?,event_time=?,organizer=?,max_participants=?,status=? WHERE id=?")
            ->execute([clean($_POST['title']), clean($_POST['description']), clean($_POST['location']), $_POST['event_date'], $_POST['event_time'].':00', clean($_POST['organizer']), (int)$_POST['max_participants'], $_POST['status'], $id]);
        log_activity("Updated event #$id", $u['id'], 'admin');
        json_response(['ok' => true]);
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("DELETE FROM events WHERE id=?")->execute([$id]);
        log_activity("Deleted event #$id", $u['id'], 'admin');
        json_response(['ok' => true]);
    }
}
if (($_GET['action'] ?? '') === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM events WHERE id=?"); $stmt->execute([$id]);
    $ev = $stmt->fetch(); if ($ev) $ev['event_time'] = substr($ev['event_time'], 0, 5);
    json_response($ev ?: ['error' => 'Not found']);
}
if (($_GET['action'] ?? '') === 'registrations') {
    $id = (int)($_GET['id'] ?? 0);
    $rows = db()->prepare("SELECT m.member_no, m.full_name, m.email, m.phone, r.registered_at FROM event_registration r JOIN members m ON m.id=r.member_id WHERE r.event_id=? ORDER BY r.registered_at DESC");
    $rows->execute([$id]);
    json_response(['rows' => $rows->fetchAll()]);
}

$rows = db()->query("SELECT e.*, (SELECT COUNT(*) FROM event_registration r WHERE r.event_id=e.id) AS reg_count FROM events e ORDER BY e.event_date DESC")->fetchAll();
?>
<div class="page-head">
  <div class="left"><h1>Events</h1><p><?= count($rows) ?> event(s)</p></div>
  <div class="right"><button class="btn btn-primary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Add Event</button></div>
</div>

<div class="grid grid-cols-3">
<?php if(!$rows):?><div class="card"><div class="card-body empty-state"><i class="fa-solid fa-calendar-day"></i><p>No events yet</p></div></div><?php endif;?>
<?php foreach($rows as $ev):?>
  <div class="card">
    <div class="card-body">
      <div class="flex-between mb-1"><span class="badge badge-primary"><?= e($ev['status']) ?></span>
        <span class="muted" style="font-size:.8rem"><i class="fa-solid fa-users"></i> <?= (int)$ev['reg_count'] ?><?= $ev['max_participants']>0?'/'.$ev['max_participants']:'' ?></span></div>
      <h3 style="margin-bottom:.4rem"><?= e($ev['title']) ?></h3>
      <p class="muted" style="font-size:.85rem;margin-bottom:.6rem"><?= e(substr($ev['description'] ?: '', 0, 100)) ?>...</p>
      <ul class="info-list" style="font-size:.85rem">
        <li><span><i class="fa-solid fa-calendar"></i> Date</span><span><?= e(fmt_date($ev['event_date'])) ?></span></li>
        <li><span><i class="fa-solid fa-clock"></i> Time</span><span><?= e(substr($ev['event_time'],0,5)) ?></span></li>
        <li><span><i class="fa-solid fa-location-dot"></i> Venue</span><span><?= e($ev['location']?:'-') ?></span></li>
        <li><span><i class="fa-solid fa-user"></i> Organizer</span><span><?= e($ev['organizer']?:'-') ?></span></li>
      </ul>
      <div class="flex gap-1 mt-2">
        <button class="btn btn-sm btn-secondary" onclick="viewReg(<?= $ev['id'] ?>,'<?= e($ev['title']) ?>')"><i class="fa-solid fa-users"></i> Registrations</button>
        <button class="btn btn-sm btn-secondary" onclick="editEvent(<?= $ev['id'] ?>)"><i class="fa-solid fa-pen"></i></button>
        <button class="btn btn-sm btn-ghost" onclick="delEvent(<?= $ev['id'] ?>,'<?= e($ev['title']) ?>')"><i class="fa-solid fa-trash" style="color:var(--error)"></i></button>
      </div>
    </div>
  </div>
<?php endforeach;?>
</div>

<div class="modal-overlay" id="evModal">
  <div class="modal lg">
    <div class="modal-head"><h3 id="evTitle">Add Event</h3><button class="close" data-modal-close="evModal">&times;</button></div>
    <form id="evForm">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="ev_id">
      <div class="modal-body">
        <div class="form-group"><label>Title <span class="req">*</span></label><input type="text" name="title" required></div>
        <div class="form-group"><label>Description</label><textarea name="description"></textarea></div>
        <div class="form-row-3">
          <div class="form-group"><label>Date <span class="req">*</span></label><input type="date" name="event_date" required></div>
          <div class="form-group"><label>Time <span class="req">*</span></label><input type="time" name="event_time" required></div>
          <div class="form-group"><label>Max Participants</label><input type="number" name="max_participants" min="0" value="0"><div class="form-help">0 = unlimited</div></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Location</label><input type="text" name="location"></div>
          <div class="form-group"><label>Organizer</label><input type="text" name="organizer"></div>
        </div>
        <div class="form-group"><label>Status</label><select name="status"><option value="upcoming">Upcoming</option><option value="ongoing">Ongoing</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
      </div>
      <div class="modal-foot"><button type="button" class="btn btn-secondary" data-modal-close="evModal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="regModal">
  <div class="modal">
    <div class="modal-head"><h3 id="regTitle">Registrations</h3><button class="close" data-modal-close="regModal">&times;</button></div>
    <div class="modal-body" id="regBody"></div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP='<?= APP_URL ?>/admin/events.php'; const TOKEN='<?= csrf_token() ?>';
function openAdd(){document.getElementById('evTitle').textContent='Add Event';document.getElementById('evForm').reset();document.getElementById('ev_id').value='';App.openModal('evModal');}
document.getElementById('evForm').addEventListener('submit',async e=>{e.preventDefault();if(!App.validateForm(e.target)){App.toast('Fix highlighted fields.','error');return;}App.showLoading();const fd=new FormData(e.target);fd.set('csrf_token',TOKEN);const edit=!!document.getElementById('ev_id').value;fd.set('action',edit?'update':'store');const r=await fetch(APP,{method:'POST',body:fd});const d=await r.json();App.hideLoading();if(d.error)App.toast(d.error,'error');else{App.toast(edit?'Event updated':'Event created','success');App.closeModal('evModal');setTimeout(()=>location.reload(),700);}});
async function editEvent(id){App.showLoading();const r=await fetch(APP+'?action=get&id='+id);const ev=await r.json();App.hideLoading();if(ev.error){App.toast(ev.error,'error');return;}document.getElementById('evTitle').textContent='Edit Event';const f=document.getElementById('evForm');f.id.value=ev.id;f.title.value=ev.title;f.description.value=ev.description;f.event_date.value=ev.event_date;f.event_time.value=ev.event_time;f.location.value=ev.location;f.organizer.value=ev.organizer;f.max_participants.value=ev.max_participants;f.status.value=ev.status;App.openModal('evModal');}
function delEvent(id,name){App.confirm({title:'Delete event?',text:`Delete "${name}" and all its registrations?`,danger:true,confirmText:'Delete',onConfirm:async()=>{const fd=new FormData();fd.set('csrf_token',TOKEN);fd.set('id',id);fd.set('action','delete');const r=await fetch(APP,{method:'POST',body:fd});const d=await r.json();if(d.error)App.toast(d.error,'error');else{App.toast('Event deleted','success');setTimeout(()=>location.reload(),700);}}});}
async function viewReg(id,title){App.showLoading();const r=await fetch(APP+'?action=registrations&id='+id);const d=await r.json();App.hideLoading();document.getElementById('regTitle').textContent='Registrations - '+title;const rows=d.rows||[];document.getElementById('regBody').innerHTML = rows.length? '<div class="table-wrap"><table class="data"><thead><tr><th>Member No</th><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th></tr></thead><tbody>'+rows.map(r=>`<tr><td>${r.member_no}</td><td>${r.full_name}</td><td>${r.email}</td><td>${r.phone||'-'}</td><td>${r.registered_at}</td></tr>`).join('')+'</tbody></table></div>' : '<div class="empty-state"><i class="fa-solid fa-users-slash"></i><p>No registrations yet</p></div>';App.openModal('regModal');}
</script>
