<?php
/**
 * SAYAK LIBRARY - Secure PDF Stream Endpoint
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

if (!is_logged_in()) {
    header("HTTP/1.1 401 Unauthorized");
    exit("Access Denied: Please log in first.");
}

$user = get_logged_in_user();
$bookId = (int)($_GET['id'] ?? 0);

if ($bookId <= 0) {
    header("HTTP/1.1 400 Bad Request");
    exit("Invalid Book ID.");
}

// Check membership status if user is MEMBER
if ($user['role_code'] === 'MEMBER') {
    if (($_SESSION['membership_status'] ?? '') === 'Restricted') {
        header("HTTP/1.1 403 Forbidden");
        exit("Account Access Restricted due to expired membership.");
    }
}

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
    exit("PDF file missing on server.");
}

log_audit_action($user['id'], $user['role_code'], 'PDF Read Stream', 'PDF', "Book ID: {$bookId}, File: {$book['pdf_file']}");

// Output PDF inline headers
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filePath);
exit();
