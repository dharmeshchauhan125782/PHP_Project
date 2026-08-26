<?php
require_once __DIR__ . '/../includes/functions.php';
requireUserLogin();

$input = getJsonInput();
$bookingId = (int)($input['booking_id'] ?? 0);

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, room_id, status FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    jsonResponse(false, 'Booking not found.');
}
if ($booking['status'] === 'cancelled') {
    jsonResponse(false, 'Booking already cancelled.');
}
if ($booking['status'] === 'checked_out') {
    jsonResponse(false, 'This stay is already completed and cannot be cancelled.');
}

$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$bookingId]);
    // If the booking had already been approved, the room was marked
    // Occupied — cancelling frees it back up automatically.
    if ($booking['status'] === 'approved') {
        $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?")->execute([$booking['room_id']]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(false, 'Could not cancel booking. Please try again.');
}

jsonResponse(true, 'Booking cancelled successfully.');
