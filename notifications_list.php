<?php
/**
 * Customer notification center (spec section 24).
 * GET  -> list notifications for the logged-in user
 * POST -> mark one or all notifications as read
 */
require_once __DIR__ . '/../includes/functions.php';
requireUserLogin();

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();

    $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unreadStmt->execute([$_SESSION['user_id']]);
    $unreadCount = (int)$unreadStmt->fetchColumn();

    jsonResponse(true, '', ['notifications' => $notifications, 'unread_count' => $unreadCount, 'csrf_token' => csrfToken()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $input = getJsonInput();
    $notificationId = cleanInt($input['notification_id'] ?? null, 1);

    if ($notificationId) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $_SESSION['user_id']]);
    } else {
        // No id provided -> mark all as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    jsonResponse(true, 'Marked as read.');
}

jsonResponse(false, 'Invalid request method.');
