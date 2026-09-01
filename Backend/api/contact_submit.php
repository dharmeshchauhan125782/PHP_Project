<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$input   = getJsonInput();
$name    = clean($input['name'] ?? '');
$email   = cleanEmail($input['email'] ?? '');
$message = clean($input['message'] ?? '');

if (!$name || strlen($name) > 100) {
    jsonResponse(false, 'Please enter a valid name.');
}
if (!$email) {
    jsonResponse(false, 'Please enter a valid email address.');
}
if (!$message || strlen($message) > 5000) {
    jsonResponse(false, 'Please enter a message.');
}

$pdo = getDB();
$stmt = $pdo->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
$stmt->execute([$name, $email, $message]);

jsonResponse(true, 'Thank you! We will get back to you shortly.');
