<?php
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$input    = getJsonInput();
$username = clean($input['username'] ?? '');
$password = $input['password'] ?? '';

if (!$username || !$password) {
    jsonResponse(false, 'Username and password are required.');
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, name, password FROM admin WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password'])) {
    jsonResponse(false, 'Invalid admin credentials.');
}

session_regenerate_id(true);
$_SESSION['admin_id']   = $admin['id'];
$_SESSION['admin_name'] = $admin['name'];

jsonResponse(true, 'Welcome back, ' . $admin['name'] . '!');
