<?php
/**
 * Room search & filter (spec section 31).
 * Filters: room_type, min_price, max_price, capacity, check_in/check_out.
 * Sort: price_asc, price_desc, recommended (default = price_asc).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/AvailabilityService.php';

$checkIn   = $_GET['check_in']  ?? null;
$checkOut  = $_GET['check_out'] ?? null;
$guests    = cleanInt($_GET['guests'] ?? 1, 1, 50) ?? 1;
$roomType  = $_GET['room_type'] ?? null;
$minPrice  = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice  = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$sort      = $_GET['sort'] ?? 'recommended';

$validTypes = ['Standard Room', 'Deluxe Room', 'Super Deluxe Room', 'Suite Room'];
if ($roomType !== null && !in_array($roomType, $validTypes, true)) {
    $roomType = null;
}

$pdo = getDB();

$sql = "SELECT * FROM rooms WHERE is_active = 1 AND status != 'maintenance' AND capacity >= :guests";
$params = [':guests' => $guests];

if ($roomType) {
    $sql .= " AND room_type = :room_type";
    $params[':room_type'] = $roomType;
}
if ($minPrice !== null) {
    $sql .= " AND price_per_night >= :min_price";
    $params[':min_price'] = $minPrice;
}
if ($maxPrice !== null) {
    $sql .= " AND price_per_night <= :max_price";
    $params[':max_price'] = $maxPrice;
}

$checkInDate = $checkOutDate = null;
if ($checkIn && $checkOut) {
    $checkInDate = parseStrictDate($checkIn);
    $checkOutDate = parseStrictDate($checkOut);
    if (!$checkInDate || !$checkOutDate || $checkOutDate <= $checkInDate) {
        jsonResponse(false, 'Please provide a valid date range.');
    }
    $sql .= " AND id NOT IN (
                SELECT room_id FROM bookings
                WHERE status IN ('pending','approved')
                AND NOT (check_out <= :check_in OR check_in >= :check_out)
              )";
    $params[':check_in']  = $checkIn;
    $params[':check_out'] = $checkOut;
}

switch ($sort) {
    case 'price_desc':
        $sql .= " ORDER BY price_per_night DESC";
        break;
    case 'price_asc':
        $sql .= " ORDER BY price_per_night ASC";
        break;
    default: // recommended: Suite/Super Deluxe first, then price
        $sql .= " ORDER BY FIELD(room_type, 'Suite Room','Super Deluxe Room','Deluxe Room','Standard Room'), price_per_night ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$imgStmt = $pdo->prepare("SELECT image_path FROM room_images WHERE room_id = ? ORDER BY sort_order ASC");
foreach ($rooms as &$room) {
    $room['amenities'] = json_decode($room['amenities'] ?? '[]', true) ?: [];
    $imgStmt->execute([$room['id']]);
    $room['images'] = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
}
unset($room);

jsonResponse(true, 'Rooms fetched.', ['rooms' => $rooms, 'count' => count($rooms)]);
