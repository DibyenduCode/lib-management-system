<?php
/**
 * SAYAK LIBRARY - Secure PDF Download Endpoint
 * Super Admin & Librarian ONLY. Members forbidden (HTTP 403).
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

if (!is_logged_in()) {
    header("HTTP/1.1 401 Unauthorized");
    exit("Access Denied: Please log in.");
}

// Strict Permission Enforcement: Super Admin & Librarian Only
if (!can_download_pdf()) {
    header("HTTP/1.1 403 Forbidden");
    require_once ROOT_PATH . 'errors/403.php';
    exit();
}

$bookId = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT name, pdf_file FROM books WHERE id = ? LIMIT 1");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book || empty($book['pdf_file'])) {
    header("HTTP/1.1 404 Not Found");
    exit("PDF document not found.");
}

$filePath = PRIVATE_PDF_DIR . $book['pdf_file'];

if (!file_exists($filePath)) {
    header("HTTP/1.1 404 Not Found");
    exit("File missing on server.");
}

log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'PDF File Download', 'PDF', "Downloaded Book ID: {$bookId}");

// Output attachment download headers
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . sanitize_input($book['name']) . '.pdf"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit();
