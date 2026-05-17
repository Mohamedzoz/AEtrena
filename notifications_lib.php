<?php
/**
 * Aeterna Notifications Library
 */

function sendNotification($user_id, $title, $message, $link = null, $type = 'info') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link, type) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $title, $message, $link, $type]);
    } catch (Exception $e) {
        return false;
    }
}

function notifyAdmins($title, $message, $link = null, $type = 'warning') {
    global $pdo;
    try {
        // Get all admin and manager IDs
        $stmt = $pdo->query("SELECT id FROM users WHERE role IN ('Admin', 'Manager')");
        $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins as $admin_id) {
            sendNotification($admin_id, $title, $message, $link, $type);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}
