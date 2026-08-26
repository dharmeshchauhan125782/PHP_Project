<?php
/**
 * Live room availability search.
 * In the original plan this was a Node.js companion microservice;
 * here it is implemented natively in PHP so the whole stack is PHP/MySQL only.
 */
require_once __DIR__ . '/../includes/functions.php';

$checkIn  = $_GET['check_in']  ?? null;
$checkOut = $_GET['check_out'] ?? null;
$guests   = (int)($_GET['guests'] ?? 1);

$pdo = getDB();

$sql = "SELECT r.*,
        (SELECT image_path FROM room_images WHERE room_id = r.id LIMIT 1) AS extra_image
        FROM rooms r
        WHERE r.status = 'available' AND r.capacity >= :guests";
$params = [':guests' => max($guests, 1)];

if ($checkIn && $checkOut) {
    $ci = DateTime::createFromFormat('Y-m-d', $checkIn);
    $co = DateTime::createFromFormat('Y-m-d', $checkOut);
    if (!$ci || !$co || $co <= $ci) {
        jsonResponse(false, 'Please provide a valid date range.');
    }
    $sql .= " AND r.id NOT IN (
                SELECT room_id FROM bookings
                WHERE status IN ('pending','approved')
                AND NOT (check_out <= :check_in OR check_in >= :check_out)
              )";
    $params[':check_in']  = $checkIn;
    $params[':check_out'] = $checkOut;
}

$sql .= " ORDER BY r.price_per_night ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

jsonResponse(true, 'Rooms fetched.', ['rooms' => $rooms]);
