<?php
require_once __DIR__ . '/../includes/functions.php';
requireUserLogin();

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT id, name, email, phone, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    jsonResponse(true, '', ['user' => $stmt->fetch()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();
    $action = $input['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $name  = clean($input['name'] ?? '');
        $phone = clean($input['phone'] ?? '');
        if (!$name) {
            jsonResponse(false, 'Name is required.');
        }
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $_SESSION['user_id']]);
        $_SESSION['user_name'] = $name;
        jsonResponse(true, 'Profile updated successfully.');
    }

    if ($action === 'change_password') {
        $current = $input['current_password'] ?? '';
        $new     = $input['new_password'] ?? '';
        if (strlen($new) < 6) {
            jsonResponse(false, 'New password must be at least 6 characters.');
        }
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($current, $row['password'])) {
            jsonResponse(false, 'Current password is incorrect.');
        }
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $_SESSION['user_id']]);
        jsonResponse(true, 'Password changed successfully.');
    }

    jsonResponse(false, 'Unknown action.');
}

jsonResponse(false, 'Invalid request method.');
