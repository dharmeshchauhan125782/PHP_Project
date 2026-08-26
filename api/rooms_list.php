<?php
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
// Only show rooms currently Available — status is kept live by the booking
// (approve/checkout/cancel) flow, so this list never shows an Occupied room.
$rooms = $pdo->query("SELECT * FROM rooms WHERE status = 'available' ORDER BY price_per_night ASC")->fetchAll();

foreach ($rooms as &$room) {
    $stmt = $pdo->prepare("SELECT image_path FROM room_images WHERE room_id = ?");
    $stmt->execute([$room['id']]);
    $room['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

jsonResponse(true, 'Rooms fetched.', ['rooms' => $rooms]);
