<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$input = getJsonInput();
$email    = strtolower(clean($input['email'] ?? ''));
$password = $input['password'] ?? '';

if (!$email || !$password) {
    jsonResponse(false, 'Email and password are required.');
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse(false, 'Invalid email or password.');
}

session_regenerate_id(true);
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['name'];

jsonResponse(true, 'Welcome back, ' . $user['name'] . '!', ['name' => $user['name']]);
