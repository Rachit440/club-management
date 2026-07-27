<?php
/**
 * Shared header. Pages set $pageTitle and $activeNav before including.
 */
require_once __DIR__ . '/auth.php';
$u = current_user();
$pageTitle = $pageTitle ?? 'Dashboard';
$activeNav = $activeNav ?? 'dashboard';
$clubName = get_setting('club_name', 'Elite Club');
$siteName = get_setting('site_name', 'Elite Club Management Portal');
$isAdmin = ($u['role'] ?? '') === 'admin';
$notifs = $isAdmin ? notifications_for_admin() : notifications_for_member((int)$u['id']);
$notifCount = count($notifs);
$avatar = !empty($u['avatar']) ? $u['avatar'] : APP_URL.'/assets/images/avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> - <?= e($siteName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='24' fill='%234F46E5'/><text x='50' y='68' font-size='58' text-anchor='middle' fill='white' font-family='sans-serif' font-weight='bold'>E</text></svg>">
  <script>window.APP_URL = <?= json_encode(APP_URL) ?>;</script>
</head>
<body>
<!-- Boot / loading screen -->
<div class="boot-screen" id="bootScreen">
  <div class="logo"><i class="fa-solid fa-gem"></i></div>
  <div class="bar"><i></i></div>
  <div class="name">ELITE CLUB</div>
</div>

<div class="loading-overlay"><div class="spinner"></div></div>

<svg width="0" height="0" style="position:absolute"><defs>
  <linearGradient id="gradCirc" x1="0%" y1="0%" x2="100%" y2="100%">
    <stop offset="0%" stop-color="#4F46E5"/><stop offset="100%" stop-color="#06B6D4"/>
  </linearGradient>
</defs></svg>

<div class="app">
  <?php require __DIR__ . '/sidebar.php'; ?>
  <div class="main">
    <header class="topbar">
      <button class="menu-toggle" data-menu-toggle aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title"><?= e($pageTitle) ?></div>
      <div class="search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" id="globalSearch" placeholder="Quick search...  (Ctrl+K)" aria-label="Search">
      </div>
      <div class="top-date"><i class="fa-regular fa-calendar"></i> <span id="topDate"></span></div>
      <div class="actions">
        <button class="icon-btn" data-theme-toggle title="Toggle theme (light/dark)"><i class="fa-solid fa-moon"></i></button>
        <div class="notif-dd">
          <button class="icon-btn" data-dropdown="notifPanel" title="Notifications">
            <i class="fa-solid fa-bell"></i>
            <?php if ($notifCount): ?><span class="badge"><?= $notifCount ?></span><?php endif; ?>
          </button>
          <div class="panel" id="notifPanel">
            <div class="head"><span>Notifications</span><a href="<?= APP_URL ?>/<?= $isAdmin?'admin':'member' ?>/announcements.php">View all</a></div>
            <?php if (empty($notifs)): ?>
              <div class="empty"><i class="fa-regular fa-bell-slash" style="font-size:1.6rem;color:var(--text-faint);margin-bottom:.5rem"></i><p>No new notifications</p></div>
            <?php else: foreach ($notifs as $n):
              $iconMap = ['expiry'=>'fa-clock','payment'=>'fa-credit-card','event'=>'fa-calendar-day'];
              $ic = $iconMap[$n['type']] ?? 'fa-bell';
            ?>
              <div class="item <?= e($n['type']) ?>"><div class="ic"><i class="fa-solid <?= $ic ?>"></i></div><div><div><?= e($n['text']) ?></div></div></div>
            <?php endforeach; endif; ?>
          </div>
        </div>
        <div class="notif-dd">
          <button class="icon-btn" data-dropdown="msgPanel" title="Messages"><i class="fa-solid fa-envelope"></i></button>
          <div class="panel" id="msgPanel">
            <div class="head"><span>Messages</span><a href="#">Inbox</a></div>
            <div class="empty"><i class="fa-regular fa-envelope" style="font-size:1.6rem;color:var(--text-faint);margin-bottom:.5rem"></i><p>No new messages</p></div>
          </div>
        </div>
        <div class="profile-dd">
          <img class="avatar" src="<?= e($avatar) ?>" alt="avatar" data-dropdown="profileMenu">
          <div class="menu" id="profileMenu">
            <div class="head"><strong><?= e($u['name']) ?></strong><span><?= e($u['email']) ?></span></div>
            <a href="<?= APP_URL ?>/<?= $isAdmin ? 'admin' : 'member' ?>/profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
            <?php if ($isAdmin): ?><a href="<?= APP_URL ?>/admin/settings.php"><i class="fa-solid fa-gear"></i> Settings</a><?php endif; ?>
            <?php if (!$isAdmin): ?><a href="<?= APP_URL ?>/member/change-password.php"><i class="fa-solid fa-key"></i> Change Password</a><?php endif; ?>
            <a href="<?= APP_URL ?>/logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
          </div>
        </div>
      </div>
    </header>
    <main class="content">
