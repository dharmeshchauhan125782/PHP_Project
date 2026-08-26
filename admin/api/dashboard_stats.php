<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdminLogin();

$pdo = getDB();

// Total rooms is the fixed hotel inventory (10). Available/Occupied are
// always derived live from the current room.status column, so they can
// never drift out of sync with actual bookings/checkouts.
$totalRooms     = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$availableRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn();
$occupiedRooms  = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pendingBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$revenue = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status = 'approved'")->fetchColumn();

$recentBookings = $pdo->query("SELECT b.id, u.name AS guest, r.room_number, r.room_type, b.check_in, b.check_out, b.status, b.total_price
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    ORDER BY b.created_at DESC LIMIT 8")->fetchAll();

jsonResponse(true, '', [
    'total_rooms' => (int)$totalRooms,
    'available_rooms' => (int)$availableRooms,
    'occupied_rooms' => (int)$occupiedRooms,
    'total_users' => (int)$totalUsers,
    'total_bookings' => (int)$totalBookings,
    'pending_bookings' => (int)$pendingBookings,
    'revenue' => (float)$revenue,
    'recent_bookings' => $recentBookings,
]);
