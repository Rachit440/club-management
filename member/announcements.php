<?php
/** Member: read announcements. */
require_once __DIR__ . '/../includes/header.php';
require_role('member');
$rows = db()->query("SELECT a.*, ad.name AS author FROM announcements a LEFT JOIN admins ad ON ad.id=a.created_by WHERE a.status='published' AND a.audience IN ('all','members') ORDER BY a.created_at DESC")->fetchAll();
?>
<div class="page-head"><div class="left"><h1>Announcements</h1><p>Latest news and updates from the club.</p></div></div>
<div class="grid grid-cols-2">
<?php if(!$rows):?><div class="card"><div class="card-body empty-state"><i class="fa-solid fa-bullhorn"></i><p>No announcements yet</p></div></div><?php endif;?>
<?php foreach($rows as $a):?>
  <div class="card"><div class="card-body">
    <div class="flex-between mb-1"><span class="badge badge-info"><?= e($a['audience']) ?></span><span class="muted" style="font-size:.8rem"><i class="fa-solid fa-clock"></i> <?= e(relative_time($a['created_at'])) ?></span></div>
    <h3 style="margin-bottom:.4rem"><?= e($a['title']) ?></h3>
    <p style="white-space:pre-wrap;font-size:.92rem"><?= e($a['body']) ?></p>
    <p class="muted mt-2" style="font-size:.8rem">— <?= e($a['author'] ?: 'Club Admin') ?></p>
  </div></div>
<?php endforeach;?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
