<?php
/** Member: browse & register for events via AJAX. */
require_once __DIR__ . '/../includes/header.php';
require_role('member');
$u = current_user(); $mid = (int)$u['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) json_response(['error' => 'Invalid token'], 419);
    $eid = (int)($_POST['event_id'] ?? 0);
    $ev = db()->prepare("SELECT * FROM events WHERE id=?"); $ev->execute([$eid]); $event = $ev->fetch();
    if (!$event) json_response(['error' => 'Event not found'], 404);
    if ($event['status'] !== 'upcoming') json_response(['error' => 'Event not open for registration'], 422);
    $cnt = db()->prepare("SELECT COUNT(*) FROM event_registration WHERE event_id=?"); $cnt->execute([$eid]);
    if ($event['max_participants'] > 0 && $cnt->fetchColumn() >= $event['max_participants']) json_response(['error' => 'Event is full'], 422);
    try {
        db()->prepare("INSERT INTO event_registration (event_id, member_id) VALUES (?,?)")->execute([$eid, $mid]);
        log_activity("Registered for event #$eid", $mid, 'member');
        json_response(['ok' => true]);
    } catch (PDOException $ex) { json_response(['error' => 'Already registered'], 422); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) json_response(['error' => 'Invalid token'], 419);
    $eid = (int)($_POST['event_id'] ?? 0);
    db()->prepare("DELETE FROM event_registration WHERE event_id=? AND member_id=?")->execute([$eid, $mid]);
    log_activity("Cancelled event registration #$eid", $mid, 'member');
    json_response(['ok' => true]);
}

$rows = db()->query("SELECT e.* FROM events e WHERE e.event_date >= CURDATE() ORDER BY e.event_date ASC")->fetchAll();
$regStmt = db()->prepare("SELECT event_id FROM event_registration WHERE member_id=?"); $regStmt->execute([$mid]);
$registered = array_column($regStmt->fetchAll(), 'event_id');
?>
<div class="page-head"><div class="left"><h1>Events</h1><p>Browse and register for upcoming events.</p></div></div>
<div class="grid grid-cols-3">
<?php if (!$rows): ?><div class="card"><div class="card-body empty-state"><i class="fa-solid fa-calendar-day"></i><p>No events scheduled</p></div></div><?php endif; ?>
<?php foreach ($rows as $ev):
  $cnt = db()->prepare("SELECT COUNT(*) FROM event_registration WHERE event_id=?"); $cnt->execute([$ev['id']]); $count = (int)$cnt->fetchColumn();
  $isReg = in_array($ev['id'], $registered, true);
?>
  <div class="card">
    <div class="card-body">
      <div class="flex-between mb-1"><span class="badge badge-primary"><?= e($ev['status']) ?></span>
        <span class="muted" style="font-size:.8rem"><i class="fa-solid fa-users"></i> <?= $count ?><?= $ev['max_participants']>0?'/'.$ev['max_participants']:'' ?></span></div>
      <h3 style="margin-bottom:.4rem"><?= e($ev['title']) ?></h3>
      <p class="muted" style="font-size:.85rem;margin-bottom:.6rem"><?= e(substr($ev['description'] ?: '', 0, 100)) ?>...</p>
      <ul class="info-list" style="font-size:.85rem">
        <li><span><i class="fa-solid fa-calendar"></i> Date</span><span><?= e(fmt_date($ev['event_date'])) ?></span></li>
        <li><span><i class="fa-solid fa-clock"></i> Time</span><span><?= e(substr($ev['event_time'],0,5)) ?></span></li>
        <li><span><i class="fa-solid fa-location-dot"></i> Venue</span><span><?= e($ev['location']?:'-') ?></span></li>
        <li><span><i class="fa-solid fa-user"></i> Organizer</span><span><?= e($ev['organizer']?:'-') ?></span></li>
      </ul>
      <?php if ($isReg): ?>
        <button class="btn btn-secondary btn-block mt-2" onclick="cancelReg(<?= $ev['id'] ?>)"><i class="fa-solid fa-circle-xmark"></i> Cancel Registration</button>
      <?php else: ?>
        <button class="btn btn-primary btn-block mt-2" onclick="register(<?= $ev['id'] ?>)"><i class="fa-solid fa-circle-check"></i> Register</button>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP='<?= APP_URL ?>/member/events.php'; const TOKEN='<?= csrf_token() ?>';
async function register(id){
  const fd=new FormData(); fd.set('csrf_token',TOKEN); fd.set('event_id',id); fd.set('action','register');
  const r=await fetch(APP,{method:'POST',body:fd}); const d=await r.json();
  if(d.error) App.toast(d.error,'error'); else { App.toast('Registered successfully!','success'); setTimeout(()=>location.reload(),700); }
}
function cancelReg(id){
  App.confirm({title:'Cancel registration?',text:'You will be removed from this event.',confirmText:'Cancel Registration',danger:true,onConfirm:async()=>{
    const fd=new FormData(); fd.set('csrf_token',TOKEN); fd.set('event_id',id); fd.set('action','cancel');
    const r=await fetch(APP,{method:'POST',body:fd}); const d=await r.json();
    if(d.error) App.toast(d.error,'error'); else { App.toast('Registration cancelled','success'); setTimeout(()=>location.reload(),700); }
  }});
}
</script>
