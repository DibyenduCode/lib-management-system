<?php
/**
 * SAYAK LIBRARY - Authentication & Session Engine
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/audit.php';

/**
 * Authenticate User Login
 */
function login_user(string $email, string $password): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.*, r.role_code, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.email = ? 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Invalid email address or password.'];
    }

    if (!password_verify($password, $user['password'])) {
        log_audit_action($user['id'], $user['role_code'], 'Failed Login', 'Auth', "Failed login attempt for email: {$email}");
        return ['success' => false, 'message' => 'Invalid email address or password.'];
    }

    if ($user['status'] === 'Suspended') {
        log_audit_action($user['id'], $user['role_code'], 'Blocked Login', 'Auth', "Suspended account attempted login.");
        return ['success' => false, 'message' => 'Your account is suspended. Please contact library administration.'];
    }

    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    // Set Session Variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_code'] = $user['role_code'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];

    // Additional Member metadata if applicable
    if ($user['role_code'] === 'MEMBER') {
        $mStmt = $db->prepare("
            SELECT m.id AS member_id, m.member_code, m.membership_status, m.restriction_date,
                   ms.expiry_date, ms.start_date, mp.plan_name
            FROM members m 
            LEFT JOIN memberships ms ON m.id = ms.member_id AND ms.status != 'Expired'
            LEFT JOIN membership_plans mp ON ms.plan_id = mp.id
            WHERE m.user_id = ?
            ORDER BY ms.id DESC LIMIT 1
        ");
        $mStmt->execute([$user['id']]);
        $member = $mStmt->fetch();
        if ($member) {
            $_SESSION['member_id'] = $member['member_id'];
            $_SESSION['member_code'] = $member['member_code'];
            $_SESSION['membership_status'] = $member['membership_status'];

            // Evaluate 15-Day Rule dynamically on login
            check_and_update_member_15day_rule($db, $member['member_id']);
        }
    } elseif ($user['role_code'] === 'LIBRARIAN') {
        $lStmt = $db->prepare("SELECT id AS librarian_id, employee_code FROM librarians WHERE user_id = ?");
        $lStmt->execute([$user['id']]);
        $librarian = $lStmt->fetch();
        if ($librarian) {
            $_SESSION['librarian_id'] = $librarian['librarian_id'];
            $_SESSION['employee_code'] = $librarian['employee_code'];
        }
    }

    log_audit_action($user['id'], $user['role_code'], 'Login Success', 'Auth', "User logged in successfully.");

    return ['success' => true, 'user' => $user];
}

/**
 * Check and Apply 15-Day Expiry Restriction Rule
 */
function check_and_update_member_15day_rule(PDO $db, int $memberId): bool {
    // Get latest membership expiry date
    $stmt = $db->prepare("
        SELECT expiry_date FROM memberships 
        WHERE member_id = ? 
        ORDER BY expiry_date DESC LIMIT 1
    ");
    $stmt->execute([$memberId]);
    $expiryDateStr = $stmt->fetchColumn();

    if (!$expiryDateStr) {
        return false;
    }

    $today = new DateTime('today');
    $expiryDate = new DateTime($expiryDateStr);

    // Calculate cutoff date: Expiry + 15 Days
    $restrictionCutoff = clone $expiryDate;
    $restrictionCutoff->modify('+15 days');

    if ($today > $restrictionCutoff) {
        // Membership is expired for MORE THAN 15 DAYS -> Restrict Account
        $restrictionDate = clone $expiryDate;
        $restrictionDate->modify('+16 days');
        $restrStr = $restrictionDate->format('Y-m-d');

        // Update member record
        $uStmt = $db->prepare("
            UPDATE members 
            SET membership_status = 'Restricted', restriction_date = ? 
            WHERE id = ?
        ");
        $uStmt->execute([$restrStr, $memberId]);

        // Update user status
        $u2Stmt = $db->prepare("
            UPDATE users u 
            JOIN members m ON u.id = m.user_id 
            SET u.status = 'Restricted' 
            WHERE m.id = ?
        ");
        $u2Stmt->execute([$memberId]);

        $_SESSION['membership_status'] = 'Restricted';
        return true;
    }

    return false;
}

/**
 * Get current logged-in user session array
 */
function get_logged_in_user(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'],
        'role_code' => $_SESSION['role_code'],
        'role_name' => $_SESSION['role_name'],
        'member_id' => $_SESSION['member_id'] ?? null,
        'member_code' => $_SESSION['member_code'] ?? null,
        'librarian_id' => $_SESSION['librarian_id'] ?? null,
    ];
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function logout_user(): void {
    if (isset($_SESSION['user_id'])) {
        log_audit_action($_SESSION['user_id'], $_SESSION['role_code'] ?? 'User', 'Logout', 'Auth', "User logged out.");
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
