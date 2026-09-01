<?php
/**
 * Admin dashboard overview stats (spec section 14).
 * "Occupied" is computed for TODAY specifically: a room counts as
 * occupied if it has an approved booking whose date range includes
 * today. This is consistent with the date-based availability model —
 * there is no permanent "occupied" flag on the room itself.
 */
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$pdo = getDB();
$today = date('Y-m-d');

$totalRooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE is_active = 1")->fetchColumn();

$maintenanceRooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE is_active = 1 AND status = 'maintenance'")->fetchColumn();

$occupiedStmt = $pdo->prepare("SELECT COUNT(DISTINCT room_id) FROM bookings
    WHERE status = 'approved' AND check_in <= ? AND check_out > ?");
$occupiedStmt->execute([$today, $today]);
$occupiedRooms = (int)$occupiedStmt->fetchColumn();

$availableRooms = max(0, $totalRooms - $maintenanceRooms - $occupiedRooms);

$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$bookingCounts = $pdo->query("SELECT status, COUNT(*) as cnt FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$pendingBookings   = (int)($bookingCounts['pending'] ?? 0);
$approvedBookings  = (int)($bookingCounts['approved'] ?? 0);
$rejectedBookings  = (int)($bookingCounts['rejected'] ?? 0);
$checkedOutBookings = (int)($bookingCounts['checked_out'] ?? 0);
$cancelledBookings = (int)($bookingCounts['cancelled'] ?? 0);
$totalBookings = array_sum($bookingCounts);

// Revenue: sum of total_price for bookings that were actually honored
// (approved or already checked out), not pending/rejected/cancelled ones.
$revenue = (float)$pdo->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status IN ('approved','checked_out')")->fetchColumn();

// Revenue trend for the last 14 days (by check-in date) for a simple chart
$trendStmt = $pdo->query("SELECT DATE(created_at) as d, COALESCE(SUM(total_price),0) as revenue
    FROM bookings
    WHERE status IN ('approved','checked_out') AND created_at >= (CURDATE() - INTERVAL 14 DAY)
    GROUP BY DATE(created_at) ORDER BY d ASC");
$revenueTrend = $trendStmt->fetchAll();

$recentBookings = $pdo->query("SELECT b.id, b.booking_ref, b.guest_name, r.room_number, r.room_type,
    b.check_in, b.check_out, b.status, b.total_price
    FROM bookings b JOIN rooms r ON r.id = b.room_id
    ORDER BY b.created_at DESC LIMIT 8")->fetchAll();

jsonResponse(true, '', [
    'total_rooms' => $totalRooms,
    'available_rooms' => $availableRooms,
    'occupied_rooms' => $occupiedRooms,
    'maintenance_rooms' => $maintenanceRooms,
    'pending_bookings' => $pendingBookings,
    'approved_bookings' => $approvedBookings,
    'rejected_bookings' => $rejectedBookings,
    'checked_out_bookings' => $checkedOutBookings,
    'cancelled_bookings' => $cancelledBookings,
    'total_bookings' => $totalBookings,
    'total_customers' => $totalUsers,
    'revenue' => $revenue,
    'revenue_trend' => $revenueTrend,
    'recent_bookings' => $recentBookings,
]);
