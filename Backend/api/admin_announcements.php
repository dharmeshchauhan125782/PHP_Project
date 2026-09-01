<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];
const ANNOUNCEMENT_TYPES = ['General', 'Important', 'Booking', 'Room', 'Maintenance', 'Policy'];

if ($method === 'GET') {
    $rows = $pdo->query("SELECT a.*, ad.name AS published_by FROM announcements a
        JOIN admin ad ON ad.id = a.created_by ORDER BY a.created_at DESC")->fetchAll();
    jsonResponse(true, '', ['announcements' => $rows, 'csrf_token' => csrfToken()]);
}

if ($method === 'POST') {
    requireCsrf();
    $input = getJsonInput();
    $action = $input['action'] ?? 'create';

    if ($action === 'create') {
        $title = clean($input['title'] ?? '');
        $message = clean($input['message'] ?? '');
        $type = $input['type'] ?? 'General';

        if (!$title || strlen($title) > 200) {
            jsonResponse(false, 'Please enter a valid title.');
        }
        if (!$message || strlen($message) > 5000) {
            jsonResponse(false, 'Please enter a message.');
        }
        if (!in_array($type, ANNOUNCEMENT_TYPES, true)) {
            $type = 'General';
        }

        $stmt = $pdo->prepare("INSERT INTO announcements (title, message, type, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $message, $type, $_SESSION['admin_id']]);
        $announcementId = $pdo->lastInsertId();

        logActivity('announcement_published', "Published announcement: {$title}", 'announcement', $announcementId);

        // Notify every customer with an in-app notification
        $userIds = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($userIds as $userId) {
            notifyUser($userId, 'announcement', $title, $message);
        }

        jsonResponse(true, 'Announcement published.', ['id' => $announcementId]);
    }

    if ($action === 'toggle_active') {
        $id = cleanInt($input['id'] ?? null, 1);
        if (!$id) jsonResponse(false, 'A valid announcement id is required.');
        $stmt = $pdo->prepare("UPDATE announcements SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        logActivity('announcement_toggled', "Toggled visibility for announcement #{$id}", 'announcement', $id);
        jsonResponse(true, 'Announcement visibility updated.');
    }

    if ($action === 'delete') {
        $id = cleanInt($input['id'] ?? null, 1);
        if (!$id) jsonResponse(false, 'A valid announcement id is required.');
        $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
        logActivity('announcement_deleted', "Deleted announcement #{$id}", 'announcement', $id);
        jsonResponse(true, 'Announcement deleted.');
    }

    jsonResponse(false, 'Unknown action.');
}

jsonResponse(false, 'Invalid request method.');
