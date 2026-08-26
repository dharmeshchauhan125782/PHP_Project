<?php
require_once __DIR__ . '/../includes/functions.php';
requireUserLogin();

$pdo = getDB();
$stmt = $pdo->prepare("SELECT b.*, r.room_number, r.room_type, r.price_per_night, r.cover_image
    FROM bookings b JOIN rooms r ON b.room_id = r.id
    WHERE b.user_id = ? ORDER BY b.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

jsonResponse(true, 'Bookings fetched.', ['bookings' => $bookings]);
