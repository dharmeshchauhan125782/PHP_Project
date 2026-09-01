<?php
/**
 * Room details endpoint (spec section 32).
 * GET Backend/api/room_details.php?id=123[&check_in=...&check_out=...]
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/AvailabilityService.php';

$roomId = cleanInt($_GET['id'] ?? null, 1);
if (!$roomId) {
    jsonResponse(false, 'A valid room id is required.');
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND is_active = 1");
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room) {
    jsonResponse(false, 'Room not found.');
}

$room['amenities'] = json_decode($room['amenities'] ?? '[]', true) ?: [];

$imgStmt = $pdo->prepare("SELECT image_path FROM room_images WHERE room_id = ? ORDER BY sort_order ASC");
$imgStmt->execute([$roomId]);
$room['images'] = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

$checkIn  = $_GET['check_in']  ?? null;
$checkOut = $_GET['check_out'] ?? null;
if ($checkIn && $checkOut && parseStrictDate($checkIn) && parseStrictDate($checkOut)) {
    $room['available_for_dates'] = isRoomAvailable($pdo, $roomId, $checkIn, $checkOut);
} else {
    $room['available_for_dates'] = null; // no dates specified yet
}

jsonResponse(true, 'Room fetched.', ['room' => $room]);
