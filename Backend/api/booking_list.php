<?php
require_once __DIR__ . '/../includes/functions.php';
requireUserLogin();

$pdo = getDB();

$stmt = $pdo->prepare("SELECT b.*, r.room_number, r.room_type, r.price_per_night, r.cover_image
    FROM bookings b JOIN rooms r ON b.room_id = r.id
    WHERE b.user_id = ? ORDER BY b.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

if ($bookings) {
    $mealStmt = $pdo->prepare("SELECT meal_type, price_per_person, total_guests, days, subtotal FROM booking_meals WHERE booking_id = ?");
    foreach ($bookings as &$booking) {
        $mealStmt->execute([$booking['id']]);
        $booking['meals'] = $mealStmt->fetchAll();
        $booking['total_guests'] = (int)$booking['adults'] + (int)$booking['children'];
    }
    unset($booking);
}

jsonResponse(true, 'Bookings fetched.', ['bookings' => $bookings]);
