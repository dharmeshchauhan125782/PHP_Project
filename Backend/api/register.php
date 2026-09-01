<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$input    = getJsonInput();
$name     = clean($input['name'] ?? '');
$email    = cleanEmail($input['email'] ?? '');
$phone    = clean($input['phone'] ?? '');
$password = (string)($input['password'] ?? '');

if (!$name || strlen($name) > 100) {
    jsonResponse(false, 'Please enter a valid name.');
}
if (!$email) {
    jsonResponse(false, 'Please enter a valid email address.');
}
if ($phone !== '' && !isValidPhone($phone)) {
    jsonResponse(false, 'Please enter a valid phone number.');
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

jsonResponse(true, 'Account created successfully!', [
    'name' => $name,
    'csrf_token' => csrfToken(),
]);
