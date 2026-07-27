<?php
/** Dashboard router: sends admin and member to their respective dashboard. */
require_once __DIR__ . '/includes/auth.php';
require_login();
$role = $_SESSION['user']['role'] ?? '';
if ($role === 'admin') {
    require __DIR__ . '/admin/dashboard.php';
} else {
    require __DIR__ . '/member/dashboard.php';
}
