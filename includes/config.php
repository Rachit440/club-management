<?php
/**
 * Elite Club Management Portal
 * Configuration file
 */

// Error reporting (turn off in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Database credentials (XAMPP defaults)
<?php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'club_management');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Application constants
define('APP_NAME', 'Elite Club Management Portal');
define('APP_URL', 'http://localhost/club-management');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/uploads/');
define('DEFAULT_AVATAR', APP_URL . '/assets/images/avatar.png');
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Start session once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
