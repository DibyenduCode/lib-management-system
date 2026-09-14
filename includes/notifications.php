<?php
/**
 * SAYAK LIBRARY - Dashboard Notifications Engine
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

function add_notification(int $userId, string $title, string $message, ?string $link = null): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, link, is_read, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        return $stmt->execute([$userId, $title, $message, $link]);
    } catch (Exception $e) {
        return false;
    }
}

function notify_role(string $roleCode, string $title, string $message, ?string $link = null): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT u.id FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE r.role_code = ? AND u.status != 'Suspended'
        ");
        $stmt->execute([$roleCode]);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $uId) {
            add_notification((int)$uId, $title, $message, $link);
        }
    } catch (Exception $e) {
        // Silently handle exception in notification dispatcher
    }
}

function get_user_notifications(int $userId, int $limit = 10): array {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function count_unread_notifications(int $userId): int {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function mark_notification_read(int $notificationId, int $userId): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $userId]);
    } catch (Exception $e) {
    }
}
