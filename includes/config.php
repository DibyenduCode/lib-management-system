<?php
ob_start();
/**
 * SAYAK LIBRARY - Central Configuration
 * Core PHP 8.x Compatible for XAMPP & cPanel Shared Hosting
 */

// Prevent direct file access
if (count(get_included_files()) == 1) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden.");
}

// Set Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting & Logging Configuration
define('APP_ENV', 'production'); // 'development' or 'production'

// Set dedicated error log file for debugging and production monitoring
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('log_errors', 1);
ini_set('error_log', $logDir . '/error.log');

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // In production: capture all errors in log file while suppressing public display
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
}

// Load Detailed Error & Exception Logger
require_once __DIR__ . '/logger.php';

// Database Credentials
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'sayak_library');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// Dynamic Base URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Detect subfolder path dynamically
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$dirName = dirname($scriptName);

// Check if running under /sayak-library or /lib
if (strpos($dirName, '/sayak-library') === 0) {
    $basePath = '/sayak-library/';
} elseif (strpos($dirName, '/lib') === 0) {
    $basePath = '/lib/';
} else {
    $basePath = rtrim($dirName, '/') . '/';
    if ($basePath === '//') $basePath = '/';
}

define('BASE_URL', $protocol . $domainName . $basePath);
define('ROOT_PATH', __DIR__ . '/../');
define('LOG_DIR', ROOT_PATH . 'logs/');
define('ERROR_LOG_PATH', LOG_DIR . 'error.log');
define('PRIVATE_PDF_DIR', ROOT_PATH . 'private_pdfs/');
define('UPLOAD_COVER_DIR', ROOT_PATH . 'uploads/covers/');
define('UPLOAD_GALLERY_DIR', ROOT_PATH . 'uploads/gallery/');
define('UPLOAD_MEMBER_DIR', ROOT_PATH . 'uploads/members/');

// Start Secure PHP Session & Output Buffering
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

if (ob_get_level() === 0) {
    ob_start();
}

// Helper constant for site title
define('SITE_NAME', 'SAYAK LIBRARY');
