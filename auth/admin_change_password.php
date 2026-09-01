<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}
requireCsrf();

$input = getJsonInput();
$current = (string)($input['current_password'] ?? '');
$new     = (string)($input['new_password'] ?? '');

if (strlen($new) < 8) {
    jsonResponse(false, 'New password must be at least 8 characters.');
}
if ($new === $current) {
    jsonResponse(false, 'New password must be different from the current password.');
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT password FROM admin WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$row = $stmt->fetch();

if (!$row || !password_verify($current, $row['password'])) {
    jsonResponse(false, 'Current password is incorrect.');
}

$hash = password_hash($new, PASSWORD_BCRYPT);
$pdo->prepare("UPDATE admin SET password = ?, must_change_password = 0 WHERE id = ?")
    ->execute([$hash, $_SESSION['admin_id']]);

logActivity('admin_password_changed', "Admin '{$_SESSION['admin_name']}' changed their password");

jsonResponse(true, 'Password changed successfully.');
