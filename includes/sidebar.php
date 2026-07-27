<?php
/**
 * Animated collapsible sidebar navigation. Role-aware links.
 */
$u = current_user();
$isAdmin = ($u['role'] ?? '') === 'admin';
$activeNav = $activeNav ?? '';
$clubName = get_setting('club_name', 'Elite Club');
$siteName = get_setting('site_name', 'Elite Club Management Portal');
?>
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <span class="logo-mark"><i class="fa-solid fa-gem"></i></span>
    <span class="brand-text"><?= e($clubName) ?></span>
    <?php if ($isAdmin): ?><button class="collapse-btn" data-collapse title="Collapse sidebar"><i class="fa-solid fa-chevrons-left"></i></button><?php endif; ?>
  </div>
  <nav>
    <?php if ($isAdmin): ?>
      <div class="nav-label">Main</div>
      <?= nav_link('dashboard', APP_URL.'/dashboard.php', 'fa-gauge-high', 'Dashboard') ?>
      <div class="nav-label">Management</div>
      <?= nav_link('members', APP_URL.'/admin/members.php', 'fa-users', 'Members') ?>
      <?= nav_link('plans', APP_URL.'/admin/plans.php', 'fa-layer-group', 'Membership Plans') ?>
      <?= nav_link('payments', APP_URL.'/admin/payments.php', 'fa-money-bill-wave', 'Payments') ?>
      <?= nav_link('attendance', APP_URL.'/admin/attendance.php', 'fa-calendar-check', 'Attendance') ?>
      <?= nav_link('events', APP_URL.'/admin/events.php', 'fa-calendar-day', 'Events') ?>
      <?= nav_link('announcements', APP_URL.'/admin/announcements.php', 'fa-bullhorn', 'Announcements') ?>
      <div class="nav-label">Insights</div>
      <?= nav_link('reports', APP_URL.'/admin/reports.php', 'fa-chart-pie', 'Reports') ?>
      <?= nav_link('settings', APP_URL.'/admin/settings.php', 'fa-gear', 'Settings') ?>
    <?php else: ?>
      <div class="nav-label">My Account</div>
      <?= nav_link('dashboard', APP_URL.'/dashboard.php', 'fa-gauge-high', 'Dashboard') ?>
      <?= nav_link('profile', APP_URL.'/member/profile.php', 'fa-user', 'My Profile') ?>
      <?= nav_link('payments', APP_URL.'/member/payments.php', 'fa-money-bill-wave', 'My Payments') ?>
      <?= nav_link('attendance', APP_URL.'/member/attendance.php', 'fa-calendar-check', 'My Attendance') ?>
      <?= nav_link('events', APP_URL.'/member/events.php', 'fa-calendar-day', 'Events') ?>
      <?= nav_link('announcements', APP_URL.'/member/announcements.php', 'fa-bullhorn', 'Announcements') ?>
      <?= nav_link('change-password', APP_URL.'/member/change-password.php', 'fa-key', 'Change Password') ?>
    <?php endif; ?>
  </nav>
  <div class="side-foot">
    <a href="<?= APP_URL ?>/logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
  </div>
</aside>

<?php
function nav_link(string $key, string $url, string $icon, string $label): string
{
  global $activeNav;
  $cls = $activeNav === $key ? 'active' : '';
  return '<a href="' . $url . '" class="' . $cls . '" title="' . e($label) . '"><i class="fa-solid ' . $icon . '"></i> <span>' . e($label) . '</span></a>';
}
