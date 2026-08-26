<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$input   = getJsonInput();
$name    = clean($input['name'] ?? '');
$email   = clean($input['email'] ?? '');
$message = clean($input['message'] ?? '');

if (!$name || !$email || !$message) {
    jsonResponse(false, 'All fields are required.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Please enter a valid email address.');
}

$pdo = getDB();
$stmt = $pdo->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
$stmt->execute([$name, $email, $message]);

jsonResponse(true, 'Thank you! We will get back to you shortly.');
