<?php
require_once __DIR__ . '/../includes/functions.php';
requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}
requireCsrf();

$input = getJsonInput();
$bookingId = cleanInt($input['booking_id'] ?? null, 1);
if (!$bookingId) {
    jsonResponse(false, 'A valid booking is required.');
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, room_id, status, booking_ref FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    jsonResponse(false, 'Booking not found.');
}
if ($booking['status'] === 'cancelled') {
    jsonResponse(false, 'This booking is already cancelled.');
}
if (in_array($booking['status'], ['checked_out', 'rejected'], true)) {
    jsonResponse(false, 'This booking can no longer be cancelled.');
}

// Availability is derived from booking status, so simply flipping this
// booking to 'cancelled' automatically frees the room for those dates —
// no separate rooms.status update needed.
$stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
$stmt->execute([$bookingId]);

notifyUser($_SESSION['user_id'], 'booking_cancelled', 'Booking Cancelled', "Your booking {$booking['booking_ref']} has been cancelled.", $bookingId);

jsonResponse(true, 'Booking cancelled successfully.');
