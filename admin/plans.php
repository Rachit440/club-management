<?php
/** Admin: Membership plans CRUD via AJAX. */
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) json_response(['error' => 'Invalid token'], 419);
    $action = $_POST['action'] ?? '';
    if ($action === 'store') {
        $name = clean($_POST['name']); if (!$name) json_response(['error' => 'Name required'], 422);
        $dur = (int)($_POST['duration_months'] ?? 0); if ($dur < 1) json_response(['error' => 'Duration must be >= 1'], 422);
        $price = (float)($_POST['price'] ?? 0);
        db()->prepare("INSERT INTO membership_plans (name,duration_months,price,benefits,status) VALUES (?,?,?,?,?)")
            ->execute([$name, $dur, $price, clean($_POST['benefits']), $_POST['status'] ?? 'active']);
        log_activity("Added plan $name", $u['id'], 'admin');
        json_response(['ok' => true]);
    }
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0); if (!$id) json_response(['error' => 'Invalid plan'], 422);
        db()->prepare("UPDATE membership_plans SET name=?,duration_months=?,price=?,benefits=?,status=? WHERE id=?")
            ->execute([clean($_POST['name']), (int)$_POST['duration_months'], (float)$_POST['price'], clean($_POST['benefits']), $_POST['status'] ?? 'active', $id]);
        log_activity("Updated plan #$id", $u['id'], 'admin');
        json_response(['ok' => true]);
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("DELETE FROM membership_plans WHERE id=?")->execute([$id]);
        log_activity("Deleted plan #$id", $u['id'], 'admin');
        json_response(['ok' => true]);
    }
}
if (($_GET['action'] ?? '') === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM membership_plans WHERE id=?"); $stmt->execute([$id]);
    json_response($stmt->fetch() ?: ['error' => 'Not found']);
}

$plans = db()->query("SELECT p.*, (SELECT COUNT(*) FROM members m WHERE m.plan_id=p.id) AS members_count FROM membership_plans p ORDER BY p.duration_months ASC")->fetchAll();
?>
<div class="page-head">
  <div class="left"><h1>Membership Plans</h1><p><?= count($plans) ?> plan(s) configured</p></div>
  <div class="right"><button class="btn btn-primary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Add Plan</button></div>
</div>

<div class="grid grid-cols-3">
<?php if(!$plans):?><div class="card"><div class="card-body empty-state"><i class="fa-solid fa-clipboard-list"></i><p>No plans yet</p></div></div><?php endif;?>
<?php foreach($plans as $p):?>
  <div class="card">
    <div class="card-body">
      <div class="flex-between mb-1"><h3><?= e($p['name']) ?></h3><span class="badge <?= $p['status']==='active'?'badge-success':'badge-muted' ?>"><?= e($p['status']) ?></span></div>
      <div style="font-size:1.8rem;font-weight:700;color:var(--primary)"><?= fmt_money($p['price']) ?></div>
      <p class="muted" style="font-size:.85rem;margin:.3rem 0 .8rem">for <?= $p['duration_months'] ?> month(s) &middot; <?= (int)$p['members_count'] ?> member(s)</p>
      <p style="font-size:.88rem;min-height:40px"><?= e($p['benefits'] ?: '-') ?></p>
      <div class="flex gap-1 mt-2">
        <button class="btn btn-sm btn-secondary" onclick="editPlan(<?= $p['id'] ?>)"><i class="fa-solid fa-pen"></i> Edit</button>
        <button class="btn btn-sm btn-ghost" onclick="delPlan(<?= $p['id'] ?>,'<?= e($p['name']) ?>')"><i class="fa-solid fa-trash" style="color:var(--error)"></i></button>
      </div>
    </div>
  </div>
<?php endforeach;?>
</div>

<div class="modal-overlay" id="planModal">
  <div class="modal">
    <div class="modal-head"><h3 id="pTitle">Add Plan</h3><button class="close" data-modal-close="planModal">&times;</button></div>
    <form id="planForm">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="p_id">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label>Plan Name <span class="req">*</span></label><input type="text" name="name" required></div>
          <div class="form-group"><label>Duration (months) <span class="req">*</span></label><input type="number" name="duration_months" min="1" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Price <span class="req">*</span></label><input type="number" name="price" step="0.01" min="0" required></div>
          <div class="form-group"><label>Status</label><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        </div>
        <div class="form-group"><label>Benefits</label><textarea name="benefits"></textarea></div>
      </div>
      <div class="modal-foot"><button type="button" class="btn btn-secondary" data-modal-close="planModal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP='<?= APP_URL ?>/admin/plans.php'; const TOKEN='<?= csrf_token() ?>';
function openAdd(){document.getElementById('pTitle').textContent='Add Plan';document.getElementById('planForm').reset();document.getElementById('p_id').value='';App.openModal('planModal');}
document.getElementById('planForm').addEventListener('submit',async e=>{e.preventDefault();if(!App.validateForm(e.target)){App.toast('Fix highlighted fields.','error');return;}App.showLoading();const fd=new FormData(e.target);fd.set('csrf_token',TOKEN);const edit=!!document.getElementById('p_id').value;fd.set('action',edit?'update':'store');const r=await fetch(APP,{method:'POST',body:fd});const d=await r.json();App.hideLoading();if(d.error)App.toast(d.error,'error');else{App.toast(edit?'Plan updated':'Plan added','success');App.closeModal('planModal');setTimeout(()=>location.reload(),700);}});
async function editPlan(id){App.showLoading();const r=await fetch(APP+'?action=get&id='+id);const p=await r.json();App.hideLoading();if(p.error){App.toast(p.error,'error');return;}document.getElementById('pTitle').textContent='Edit Plan';const f=document.getElementById('planForm');f.id.value=p.id;f.name.value=p.name;f.duration_months.value=p.duration_months;f.price.value=p.price;f.benefits.value=p.benefits;f.status.value=p.status;App.openModal('planModal');}
function delPlan(id,name){App.confirm({title:'Delete plan?',text:`Delete "${name}"? Members on this plan will keep their membership but become unassigned.`,danger:true,confirmText:'Delete',onConfirm:async()=>{const fd=new FormData();fd.set('csrf_token',TOKEN);fd.set('id',id);fd.set('action','delete');const r=await fetch(APP,{method:'POST',body:fd});const d=await r.json();if(d.error)App.toast(d.error,'error');else{App.toast('Plan deleted','success');setTimeout(()=>location.reload(),700);}}});}
</script>
