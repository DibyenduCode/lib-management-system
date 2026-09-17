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

// Optional Local/cPanel Environment Override
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Database Credentials
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'sayak_library');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_PORT')) define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Dynamic Base URL detection (if not defined in config.local.php)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Detect base path dynamically based on DOCUMENT_ROOT and ROOT_PATH
    $projectDir = str_replace('\\', '/', realpath(__DIR__ . '/../'));
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';

    if (!empty($docRoot) && !empty($projectDir) && strpos($projectDir, $docRoot) === 0) {
        $relPath = substr($projectDir, strlen($docRoot));
        $basePath = '/' . trim($relPath, '/') . '/';
        if ($basePath === '//') {
            $basePath = '/';
        }
    } else {
        // Fallback detection
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $dirName = dirname($scriptName);
        if (strpos($dirName, '/sayak-library') === 0) {
            $basePath = '/sayak-library/';
        } elseif (strpos($dirName, '/lib') === 0) {
            $basePath = '/lib/';
        } else {
            $basePath = '/';
        }
    }

    define('BASE_URL', $protocol . $domainName . $basePath);
}

define('ROOT_PATH', __DIR__ . '/../');
define('LOG_DIR', ROOT_PATH . 'logs/');
define('ERROR_LOG_PATH', LOG_DIR . 'error.log');
define('PRIVATE_PDF_DIR', ROOT_PATH . 'private_pdfs/');
define('UPLOAD_COVER_DIR', ROOT_PATH . 'uploads/covers/');
define('UPLOAD_GALLERY_DIR', ROOT_PATH . 'uploads/gallery/');
define('UPLOAD_MEMBER_DIR', ROOT_PATH . 'uploads/members/');

// Default Cron Secret Key if not overridden
if (!defined('CRON_SECRET_KEY')) {
    define('CRON_SECRET_KEY', 'sayak_library_cron_' . md5(__DIR__));
}

// Start Secure PHP Session & Output Buffering
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

if (ob_get_level() === 0) {
    ob_start();
}

// Helper constant for site title
define('SITE_NAME', 'DAKSHINESWAR SHAYAK LIBRARY');

