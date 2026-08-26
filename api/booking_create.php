<?php
require_once __DIR__ . '/../includes/functions.php';
requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$input    = getJsonInput();
$roomId   = (int)($input['room_id'] ?? 0);
$checkIn  = $input['check_in'] ?? '';
$checkOut = $input['check_out'] ?? '';
$guests   = (int)($input['guests'] ?? 1);

$ci = DateTime::createFromFormat('Y-m-d', $checkIn);
$co = DateTime::createFromFormat('Y-m-d', $checkOut);
$today = new DateTime('today');

if (!$roomId || !$ci || !$co) {
    jsonResponse(false, 'Please provide room and valid dates.');
}
if ($ci < $today) {
    jsonResponse(false, 'Check-in date cannot be in the past.');
}
if ($co <= $ci) {
    jsonResponse(false, 'Check-out date must be after check-in date.');
}

$pdo = getDB();

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND status = 'available'");
$stmt->execute([$roomId]);
$room = $stmt->fetch();
if (!$room) {
    jsonResponse(false, 'Selected room is not available.');
}
if ($guests > $room['capacity']) {
    jsonResponse(false, 'This room supports up to ' . $room['capacity'] . ' guests.');
}

// Prevent double-booking (overlap check) - locked via transaction
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings
        WHERE room_id = ? AND status IN ('pending','approved')
        AND NOT (check_out <= ? OR check_in >= ?)
        FOR UPDATE");
    $stmt->execute([$roomId, $checkIn, $checkOut]);
    if ($stmt->fetchColumn() > 0) {
        $pdo->rollBack();
        jsonResponse(false, 'This room is already booked for the selected dates.');
    }

    $nights = $ci->diff($co)->days;
    $totalPrice = $nights * (float)$room['price_per_night'];

    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_price, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$_SESSION['user_id'], $roomId, $checkIn, $checkOut, $guests, $totalPrice]);

    $pdo->commit();
    jsonResponse(true, 'Booking request submitted! Awaiting confirmation.', [
        'booking_id' => $pdo->lastInsertId(),
        'total_price' => $totalPrice,
        'nights' => $nights,
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(false, 'Could not complete booking. Please try again.');
}
