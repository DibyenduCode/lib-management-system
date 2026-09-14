<?php
/**
 * SAYAK LIBRARY - Role Based Access Control (RBAC) & Authorization Helper
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

/**
 * Enforce authentication for any logged in user regardless of role
 */
function require_login(): void {
    if (!is_logged_in()) {
        set_flash_message('danger', 'Please log in to access this page.');
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

/**
 * Enforce minimum authentication & role requirements for backend scripts
 * @param array|string $allowedRoles e.g. ['SUPER_ADMIN', 'LIBRARIAN']
 */
function require_role($allowedRoles): void {
    if (!is_logged_in()) {
        set_flash_message('danger', 'Please log in to access this page.');
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }

    $allowedRoles = (array)$allowedRoles;
    $currentRole = $_SESSION['role_code'] ?? '';

    if (!in_array($currentRole, $allowedRoles, true)) {
        header('HTTP/1.1 403 Forbidden');
        require_once ROOT_PATH . 'errors/403.php';
        exit();
    }
}

/**
 * Check if current user has any of the given roles
 */
function has_role($roles): bool {
    if (!is_logged_in()) return false;
    $roles = (array)$roles;
    return in_array($_SESSION['role_code'] ?? '', $roles, true);
}

/**
 * Enforce member restriction check.
 * If Member is restricted (expired > 15 days), they cannot navigate deeper into member dashboard features.
 */
function require_unrestricted_member(): void {
    require_role('MEMBER');
    $db = getDB();
    $memberId = $_SESSION['member_id'] ?? 0;

    if ($memberId > 0) {
        $stmt = $db->prepare("SELECT membership_status FROM members WHERE id = ?");
        $stmt->execute([$memberId]);
        $status = $stmt->fetchColumn();

        if ($status === 'Restricted' || $_SESSION['membership_status'] === 'Restricted') {
            $currentPage = basename($_SERVER['SCRIPT_NAME']);
            // Allow them to visit dashboard (where restriction alert is displayed) or logout
            if ($currentPage !== 'dashboard.php' && $currentPage !== 'logout.php') {
                header('Location: ' . BASE_URL . 'member/dashboard.php');
                exit();
            }
        }
    }
}

/**
 * Strictly verify notice management permission.
 * SUPER ADMIN ONLY. LIBRARIANS AND MEMBERS ARE FORBIDDEN.
 */
function can_manage_notices(): bool {
    return has_role('SUPER_ADMIN');
}

/**
 * Strictly verify PDF download permission.
 * SUPER ADMIN & LIBRARIAN ONLY. MEMBERS ARE FORBIDDEN.
 */
function can_download_pdf(): bool {
    return has_role(['SUPER_ADMIN', 'LIBRARIAN']);
}
