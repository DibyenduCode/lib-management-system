<?php
/**
 * SAYAK LIBRARY - Daily Automated Cron Process
 * Schedule via cPanel Cron Job: 0 0 * * * php /path/to/sayak-library/cron/daily_cron.php
 */

// Handle CLI or Secret Key execution
require_once __DIR__ . '/../includes/config.php';

// Execution Security Check: Allow CLI execution or token-authorized web execution
$isCli = (php_sapi_name() === 'cli' || empty($_SERVER['REMOTE_ADDR']));
if (!$isCli) {
    $providedKey = $_GET['key'] ?? '';
    $configuredKey = defined('CRON_SECRET_KEY') ? CRON_SECRET_KEY : '';
    if (empty($providedKey) || empty($configuredKey) || !hash_equals($configuredKey, $providedKey)) {
        header("HTTP/1.1 403 Forbidden");
        header("Content-Type: text/plain; charset=UTF-8");
        exit("403 Forbidden: Invalid or missing cron authorization key.\n");
    }
    header("Content-Type: text/plain; charset=UTF-8");
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/email.php';

$db = getDB();
$todayStr = date('Y-m-d');

echo "=== SAYAK LIBRARY CRON START [{$todayStr}] ===\n";

// 1. Process 15-Day Expiry Restriction Rule (#43 & #64)
$expiredMembersStmt = $db->query("
    SELECT m.id AS member_id, m.user_id, u.full_name, u.email, m.member_code,
           (SELECT MAX(expiry_date) FROM memberships WHERE member_id = m.id) AS last_expiry
    FROM members m
    JOIN users u ON m.user_id = u.id
    WHERE m.membership_status != 'Restricted' AND m.membership_status != 'Suspended'
");
$allMembers = $expiredMembersStmt->fetchAll();

$restrictedCount = 0;
foreach ($allMembers as $m) {
    if (!empty($m['last_expiry'])) {
        $expiryDate = new DateTime($m['last_expiry']);
        $today = new DateTime('today');
        $cutoff = clone $expiryDate;
        $cutoff->modify('+15 days');

        if ($today > $cutoff) {
            $restrictionDate = clone $expiryDate;
            $restrictionDate->modify('+16 days');
            $restrStr = $restrictionDate->format('Y-m-d');

            $db->prepare("UPDATE members SET membership_status = 'Restricted', restriction_date = ? WHERE id = ?")->execute([$restrStr, $m['member_id']]);
            $db->prepare("UPDATE users SET status = 'Restricted' WHERE id = ?")->execute([$m['user_id']]);

            add_notification($m['user_id'], "Account Access Restricted", "Your membership expired over 15 days ago on {$m['last_expiry']}. Please renew at library desk.");
            send_library_email($m['email'], "Account Access Restricted - Sayak Library", "<p>Dear {$m['full_name']},</p><p>Your library membership expired on <strong>{$m['last_expiry']}</strong> (over 15 days ago). Your account access is now restricted.</p>");
            
            $restrictedCount++;
        }
    }
}
echo "Applied 15-Day Restriction to {$restrictedCount} member(s).\n";

// 2. Process Overdue Books & Calculate Fines
$issuesStmt = $db->query("
    SELECT bi.*, b.name AS book_name, m.user_id, u.full_name, u.email
    FROM book_issues bi
    JOIN books b ON bi.book_id = b.id
    JOIN members m ON bi.member_id = m.id
    JOIN users u ON m.user_id = u.id
    WHERE bi.status = 'Issued' AND bi.due_date < CURDATE()
");
$overdueIssues = $issuesStmt->fetchAll();

foreach ($overdueIssues as $iss) {
    // Update issue status -> Overdue
    $db->prepare("UPDATE book_issues SET status = 'Overdue' WHERE id = ?")->execute([$iss['id']]);

    // Recalculate Fine
    $fineCalc = calculate_fine($iss['due_date'], date('Y-m-d'));
    if ($fineCalc['fine_amount'] > 0) {
        $fCheck = $db->prepare("SELECT id FROM fines WHERE issue_id = ?");
        $fCheck->execute([$iss['id']]);
        $fId = $fCheck->fetchColumn();

        if ($fId) {
            $db->prepare("UPDATE fines SET late_days = ?, fine_amount = ? WHERE id = ?")->execute([$fineCalc['late_days'], $fineCalc['fine_amount'], $fId]);
        } else {
            $db->prepare("INSERT INTO fines (issue_id, member_id, book_id, copy_id, late_days, fine_amount, paid_amount, status) VALUES (?, ?, ?, ?, ?, ?, 0.00, 'Unpaid')")
               ->execute([$iss['id'], $iss['member_id'], $iss['book_id'], $iss['copy_id'], $fineCalc['late_days'], $fineCalc['fine_amount']]);
        }
    }
}
echo "Updated fine calculations for " . count($overdueIssues) . " overdue issue(s).\n";

// 3. Log Cron Last Run Time
$db->prepare("UPDATE system_settings SET setting_value = NOW() WHERE setting_key = 'cron_last_run'")->execute();
echo "=== CRON RUN COMPLETED SUCCESSFULLY ===\n";
