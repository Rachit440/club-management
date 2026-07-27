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
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'club_management');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Application constants
define('APP_NAME', 'Elite Club Management Portal');
