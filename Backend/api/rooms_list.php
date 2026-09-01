<?php
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();

// List every active, non-maintenance room. Date-specific availability is
// handled by rooms_search.php — this endpoint is for general browsing
// (e.g. the homepage / room listing page without dates selected yet).
$rooms = $pdo->query("SELECT * FROM rooms WHERE is_active = 1 AND status != 'maintenance' ORDER BY price_per_night ASC")->fetchAll();

$imgStmt = $pdo->prepare("SELECT image_path FROM room_images WHERE room_id = ? ORDER BY sort_order ASC");
foreach ($rooms as &$room) {
    $room['amenities'] = json_decode($room['amenities'] ?? '[]', true) ?: [];
    $imgStmt->execute([$room['id']]);
    $room['images'] = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
}
unset($room);

jsonResponse(true, 'Rooms fetched.', ['rooms' => $rooms]);
