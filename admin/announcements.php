<?php
/** Admin: Announcements CRUD. */
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) json_response(['error' => 'Invalid token'], 419);
    $action = $_POST['action'] ?? '';
    if ($action === 'store') {
        $title = clean($_POST['title']); if (!$title) json_response(['error' => 'Title required'], 422);
        $body = clean($_POST['body']); if (!$body) json_response(['error' => 'Body required'], 422);
        db()->prepare("INSERT INTO announcements (title,body,audience,created_by,status) VALUES (?,?,?,?,?)")
            ->execute([$title, $body, $_POST['audience'] ?? 'all', $u['id'], $_POST['status'] ?? 'published']);
        log_activity("Published announcement $title", $u['id'], 'admin');
        json_response(['ok' => true]);
    }
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0); if (!$id) json_response(['error' => 'Invalid announcement'], 422);
        db()->prepare("UPDATE announcements SET title=?,body=?,audience=?,status=? WHERE id=?")
            ->execute([clean($_POST['title']), clean($_POST['body']), $_POST['audience'], $_POST['status'], $id]);
        log_activity("Updated announcement #$id", $u['id'], 'admin');
        json_response(['ok' => true]);
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("DELETE FROM announcements WHERE id=?")->execute([$id]);
        json_response(['ok' => true]);
    }
}
if (($_GET['action'] ?? '') === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM announcements WHERE id=?"); $stmt->execute([$id]);
    json_response($stmt->fetch() ?: ['error' => 'Not found']);
}

$rows = db()->query("SELECT a.*, ad.name AS author FROM announcements a LEFT JOIN admins ad ON ad.id=a.created_by ORDER BY a.created_at DESC")->fetchAll();
?>
<div class="page-head">
  <div class="left"><h1>Announcements</h1><p><?= count($rows) ?> announcement(s)</p></div>
  <div class="right"><button class="btn btn-primary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> New Announcement</button></div>
</div>

<div class="grid grid-cols-2">
<?php if(!$rows):?><div class="card"><div class="card-body empty-state"><i class="fa-solid fa-bullhorn"></i><p>No announcements yet</p></div></div><?php endif;?>
<?php foreach($rows as $a):?>
  <div class="card"><div class="card-body">
    <div class="flex-between mb-1"><span class="badge badge-info"><?= e($a['audience']) ?></span>
      <span class="muted" style="font-size:.8rem"><i class="fa-solid fa-clock"></i> <?= e(relative_time($a['created_at'])) ?></span></div>
    <h3 style="margin-bottom:.4rem"><?= e($a['title']) ?></h3>
    <p style="white-space:pre-wrap;font-size:.9rem"><?= e($a['body']) ?></p>
    <p class="muted mt-1" style="font-size:.8rem">— <?= e($a['author'] ?: 'Admin') ?> &middot; <span class="badge <?= $a['status']==='published'?'badge-success':'badge-muted' ?>"><?= e($a['status']) ?></span></p>
    <div class="flex gap-1 mt-2">
      <button class="btn btn-sm btn-secondary" onclick="editAnn(<?= $a['id'] ?>)"><i class="fa-solid fa-pen"></i> Edit</button>
      <button class="btn btn-sm btn-ghost" onclick="delAnn(<?= $a['id'] ?>,'<?= e($a['title']) ?>')"><i class="fa-solid fa-trash" style="color:var(--error)"></i></button>
    </div>
  </div></div>
<?php endforeach;?>
</div>

<div class="modal-overlay" id="annModal">
  <div class="modal">
    <div class="modal-head"><h3 id="annTitle">New Announcement</h3><button class="close" data-modal-close="annModal">&times;</button></div>
    <form id="annForm">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="ann_id">
      <div class="modal-body">
        <div class="form-group"><label>Title <span class="req">*</span></label><input type="text" name="title" required></div>
        <div class="form-group"><label>Body <span class="req">*</span></label><textarea name="body" required style="min-height:120px"></textarea></div>
        <div class="form-row">
          <div class="form-group"><label>Audience</label><select name="audience"><option value="all">All</option><option value="members">Members only</option><option value="admins">Admins only</option></select></div>
          <div class="form-group"><label>Status</label><select name="status"><option value="published">Published</option><option value="draft">Draft</option></select></div>
        </div>
      </div>
      <div class="modal-foot"><button type="button" class="btn btn-secondary" data-modal-close="annModal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-bullhorn"></i> Publish</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP='<?= APP_URL ?>/admin/announcements.php'; const TOKEN='<?= csrf_token() ?>';
function openAdd(){document.getElementById('annTitle').textContent='New Announcement';document.getElementById('annForm').reset();document.getElementById('ann_id').value='';App.openModal('annModal');}
document.getElementById('annForm').addEventListener('submit',async e=>{e.preventDefault();if(!App.validateForm(e.target)){App.toast('Fix highlighted fields.','error');return;}App.showLoading();const fd=new FormData(e.target);fd.set('csrf_token',TOKEN);const edit=!!document.getElementById('ann_id').value;fd.set('action',edit?'update':'store');const r=await fetch(APP,{method:'POST',body:fd});const d=await r.json();App.hideLoading();if(d.error)App.toast(d.error,'error');else{App.toast(edit?'Announcement updated':'Announcement published','success');App.closeModal('annModal');setTimeout(()=>location.reload(),700);}});
async function editAnn(id){App.showLoading();const r=await fetch(APP+'?action=get&id='+id);const a=await r.json();App.hideLoading();if(a.error){App.toast(a.error,'error');return;}document.getElementById('annTitle').textContent='Edit Announcement';const f=document.getElementById('annForm');f.id.value=a.id;f.title.value=a.title;f.body.value=a.body;f.audience.value=a.audience;f.status.value=a.status;App.openModal('annModal');}
function delAnn(id,title){App.confirm({title:'Delete announcement?',text:`Delete "${title}"?`,danger:true,confirmText:'Delete',onConfirm:async()=>{const fd=new FormData();fd.set('csrf_token',TOKEN);fd.set('id',id);fd.set('action','delete');const r=await fetch(APP,{method:'POST',body:fd});const d=await r.json();if(d.error)App.toast(d.error,'error');else{App.toast('Deleted','success');setTimeout(()=>location.reload(),700);}}});}
</script>
