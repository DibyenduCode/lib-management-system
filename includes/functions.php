<?php
/**
 * SAYAK LIBRARY - Core Helper Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/email.php';


/**
 * Sanitize input string
 */
function sanitize_input($data): string {
    if (is_null($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Escape HTML output for XSS Protection
 */
function escape(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency
 */
function format_currency($amount): string {
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Format Date nicely
 */
function format_date(?string $dateStr, string $format = 'd M Y'): string {
    if (empty($dateStr) || $dateStr === '0000-00-00') return 'N/A';
    return date($format, strtotime($dateStr));
}

/**
 * Flash Message Handler
 */
function set_flash_message(string $type, string $message): void {
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash_messages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

function display_flash_messages(): void {
    $messages = get_flash_messages();
    if (!empty($messages)) {
        foreach ($messages as $msg) {
            $typeClass = match($msg['type']) {
                'success' => 'alert-success',
                'danger', 'error' => 'alert-danger',
                'warning' => 'alert-warning',
                default => 'alert-info',
            };
            echo '<div class="alert ' . $typeClass . ' alert-dismissible fade show my-3" role="alert">';
            echo '  <i class="fas me-2 ' . ($msg['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') . '"></i>';
            echo escape($msg['message']);
            echo '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
    }
}

/**
 * Get setting from system_settings table
 */
function get_setting(string $key, string $default = ''): string {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Generate CSRF Token
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ID Generator Helpers
 */
function generate_member_id(PDO $db): string {
    $stmt = $db->query("SELECT MAX(id) FROM members");
    $maxId = (int)$stmt->fetchColumn();
    $next = $maxId + 1;
    return 'SL-MEM-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
}

function generate_book_code(PDO $db): string {
    $stmt = $db->query("SELECT MAX(id) FROM books");
    $maxId = (int)$stmt->fetchColumn();
    $next = $maxId + 1;
    return 'SL-BK-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
}

function generate_copy_code(PDO $db): string {
    $stmt = $db->query("SELECT MAX(id) FROM book_copies");
    $maxId = (int)$stmt->fetchColumn();
    $next = $maxId + 1;
    return 'SL-COPY-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
}

function generate_employee_code(PDO $db): string {
    $stmt = $db->query("SELECT MAX(id) FROM librarians");
    $maxId = (int)$stmt->fetchColumn();
    $next = $maxId + 1;
    return 'SL-EMP-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}

function generate_transaction_code(PDO $db): string {
    $stmt = $db->query("SELECT MAX(id) FROM membership_payments");
    $maxId = (int)$stmt->fetchColumn();
    $next = $maxId + 1;
    return 'SL-TXN-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
}

function generate_receipt_code(PDO $db): string {
    $stmt = $db->query("SELECT MAX(id) FROM fine_payments");
    $maxId = (int)$stmt->fetchColumn();
    $next = $maxId + 1;
    return 'SL-RCP-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
}

function generate_request_code(PDO $db): string {
    $stmt = $db->query("SELECT MAX(id) FROM book_requests");
    $maxId = (int)$stmt->fetchColumn();
    $next = $maxId + 1;
    return 'SL-REQ-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
}

function generate_issue_code(PDO $db): string {
    $stmt = $db->query("SELECT MAX(id) FROM book_issues");
    $maxId = (int)$stmt->fetchColumn();
    $next = $maxId + 1;
    return 'SL-ISS-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
}

/**
 * Calculate late fine based on due date, return date, fine rate, and grace period
 */
function calculate_fine(string $dueDateStr, ?string $returnDateStr = null): array {
    $dueDate = new DateTime($dueDateStr);
    $returnDate = $returnDateStr ? new DateTime($returnDateStr) : new DateTime('today');

    $fineRate = (float)get_setting('fine_per_day', '5.00');
    $graceDays = (int)get_setting('grace_period_days', '2');

    if ($returnDate <= $dueDate) {
        return ['late_days' => 0, 'fine_amount' => 0.00];
    }

    $interval = $dueDate->diff($returnDate);
    $totalLateDays = (int)$interval->days;

    // Apply grace period
    $chargeableDays = max(0, $totalLateDays - $graceDays);
    $fineAmount = $chargeableDays * $fineRate;

    return [
        'late_days' => $totalLateDays,
        'chargeable_days' => $chargeableDays,
        'fine_amount' => round($fineAmount, 2)
    ];
}

/**
 * Count active alerts: Expired members (>15 days) holding unreturned books
 */
function get_expired_unreturned_alerts_count(): int {
    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT COUNT(*)
            FROM book_issues bi
            JOIN members m ON bi.member_id = m.id
            LEFT JOIN memberships ms ON m.id = ms.member_id
            WHERE bi.status IN ('Issued', 'Overdue')
              AND (m.membership_status = 'Restricted' OR ms.expiry_date < DATE_SUB(CURDATE(), INTERVAL 15 DAY))
        ");
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Convert Google Sheet or external spreadsheet link into embeddable URL
 */
function get_catalog_sheet_embed_url(string $url): string {
    $url = trim($url);
    if (empty($url)) return '';

    // If already has pubhtml or preview, return as is
    if (strpos($url, '/pubhtml') !== false || strpos($url, '/preview') !== false) {
        return $url;
    }

    // Google Sheets: https://docs.google.com/spreadsheets/d/{ID}/edit... -> /preview?widget=true&headers=false
    if (preg_match('#docs\.google\.com/spreadsheets/d/([a-zA-Z0-9-_]+)#', $url, $matches)) {
        $sheetId = $matches[1];
        // Check if specific gid is present
        $gidParam = '';
        if (preg_match('#[#&?]gid=([0-9]+)#', $url, $gidMatches)) {
            $gidParam = '&gid=' . $gidMatches[1];
        }
        return "https://docs.google.com/spreadsheets/d/{$sheetId}/preview?widget=true&headers=false" . $gidParam;
    }

    return $url;
}

/**
 * Get active membership application PDF URL (local file or external URL)
 */
function get_membership_form_url(): string {
    $file = get_setting('membership_pdf_file', '');
    if (!empty($file) && file_exists(ROOT_PATH . 'uploads/forms/' . $file)) {
        return BASE_URL . 'uploads/forms/' . $file;
    }
    $url = get_setting('membership_pdf_url', '');
    if (!empty($url)) {
        return $url;
    }
    return '';
}

