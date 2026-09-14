<?php
/**
 * SAYAK LIBRARY - Audit Logging System
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

function log_audit_action(?int $userId, string $role, string $action, ?string $target = null, ?string $details = null): bool {
    try {
        $db = getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, role, action, target, ip_address, details, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $userId,
            $role,
            $action,
            $target,
            $ip,
            $details
        ]);
    } catch (Exception $e) {
        return false;
    }
}
