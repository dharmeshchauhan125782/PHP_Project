<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$input = getJsonInput();
$name     = clean($input['name'] ?? '');
$email    = strtolower(clean($input['email'] ?? ''));
$phone    = clean($input['phone'] ?? '');
$password = $input['password'] ?? '';

if (!$name || !$email || !$password) {
    jsonResponse(false, 'Name, email and password are required.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Please enter a valid email address.');
}
if (strlen($password) < 6) {
    jsonResponse(false, 'Password must be at least 6 characters.');
}

$pdo = getDB();

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonResponse(false, 'An account with this email already exists.');
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
$stmt->execute([$name, $email, $hash, $phone]);

$newUserId = $pdo->lastInsertId();
session_regenerate_id(true);
$_SESSION['user_id']   = $newUserId;
$_SESSION['user_name'] = $name;

jsonResponse(true, 'Account created successfully!', ['name' => $name]);
