<?php
/**
 * Admin booking workflow (spec sections 12, 13, 23).
 *
 * Valid state transitions (enforced strictly):
 *   pending  -> approved
 *   pending  -> rejected   (requires a non-empty rejection_reason)
 *   approved -> checked_out
 *   pending | approved -> cancelled (admin-initiated cancellation)
 *
 * All other transitions (e.g. rejected -> approved, checked_out -> approved)
 * are blocked unless explicitly handled as an admin correction feature,
 * which this endpoint does NOT implement — those states are final.
 */
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $filter = clean($_GET['status'] ?? '');
    $validStatuses = ['pending', 'approved', 'rejected', 'checked_out', 'cancelled'];

    $sql = "SELECT b.*, r.room_number, r.room_type, r.price_per_night, r.cover_image
            FROM bookings b JOIN rooms r ON b.room_id = r.id";
    $params = [];
    if ($filter && in_array($filter, $validStatuses, true)) {
        $sql .= " WHERE b.status = ?";
        $params[] = $filter;
    }
    $sql .= " ORDER BY b.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();

    if ($bookings) {
        $mealStmt = $pdo->prepare("SELECT meal_type, price_per_person, total_guests, days, subtotal FROM booking_meals WHERE booking_id = ?");
        foreach ($bookings as &$b) {
            $mealStmt->execute([$b['id']]);
            $b['meals'] = $mealStmt->fetchAll();
        }
        unset($b);
    }

    jsonResponse(true, '', ['bookings' => $bookings, 'csrf_token' => csrfToken()]);
}

if ($method === 'POST') {
    requireCsrf();
    $input = getJsonInput();
    $action = $input['action'] ?? '';
    $id = cleanInt($input['id'] ?? null, 1);

    if (!$id) {
        jsonResponse(false, 'A valid booking is required.');
    }

    $stmt = $pdo->prepare("SELECT id, room_id, status, booking_ref, user_id FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();
    if (!$booking) {
        jsonResponse(false, 'Booking not found.');
    }

    if ($action === 'approve') {
        if ($booking['status'] !== 'pending') {
            jsonResponse(false, 'Only pending bookings can be approved.');
        }
        // Re-verify no overlapping booking has been approved for this room
        // in the meantime (e.g. two pending requests for the same dates).
        require_once __DIR__ . '/../services/AvailabilityService.php';
        $bStmt = $pdo->prepare("SELECT check_in, check_out FROM bookings WHERE id = ?");
        $bStmt->execute([$id]);
        $dates = $bStmt->fetch();
        if (!isRoomAvailable($pdo, $booking['room_id'], $dates['check_in'], $dates['check_out'], $id)) {
            jsonResponse(false, 'This room now has a conflicting approved booking for overlapping dates. Please reject this request or resolve the conflict manually.');
        }

        $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?")->execute([$id]);
        logActivity('booking_approved', "Approved booking {$booking['booking_ref']}", 'booking', $id);
        notifyUser($booking['user_id'], 'booking_approved', 'Booking Approved', "Your booking {$booking['booking_ref']} has been approved!", $id);
        jsonResponse(true, 'Booking approved.');
    }

    if ($action === 'reject') {
        if ($booking['status'] !== 'pending') {
            jsonResponse(false, 'Only pending bookings can be rejected.');
        }
        $reason = clean($input['rejection_reason'] ?? '');
        if (!$reason || strlen($reason) < 5) {
            jsonResponse(false, 'A rejection reason is required (minimum 5 characters).');
        }
        if (strlen($reason) > 1000) {
            jsonResponse(false, 'Rejection reason is too long (max 1000 characters).');
        }

        $pdo->prepare("UPDATE bookings SET status = 'rejected', rejection_reason = ? WHERE id = ?")->execute([$reason, $id]);
        logActivity('booking_rejected', "Rejected booking {$booking['booking_ref']}: {$reason}", 'booking', $id);
        notifyUser($booking['user_id'], 'booking_rejected', 'Booking Rejected', "Your booking {$booking['booking_ref']} was rejected.\n\nReason: {$reason}", $id);
        jsonResponse(true, 'Booking rejected and guest notified.');
    }

    if ($action === 'checkout') {
        if ($booking['status'] !== 'approved') {
            jsonResponse(false, 'Only approved bookings can be checked out.');
        }
        $pdo->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?")->execute([$id]);
        logActivity('booking_checked_out', "Checked out booking {$booking['booking_ref']}", 'booking', $id);
        jsonResponse(true, 'Guest checked out successfully.');
    }

    if ($action === 'cancel') {
        if (!in_array($booking['status'], ['pending', 'approved'], true)) {
            jsonResponse(false, 'Only pending or approved bookings can be cancelled.');
        }
        $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        logActivity('booking_cancelled', "Admin cancelled booking {$booking['booking_ref']}", 'booking', $id);
        notifyUser($booking['user_id'], 'booking_cancelled', 'Booking Cancelled', "Your booking {$booking['booking_ref']} was cancelled by hotel staff.", $id);
        jsonResponse(true, 'Booking cancelled.');
    }

    jsonResponse(false, 'Unknown or unsupported action.');
}

jsonResponse(false, 'Invalid request method.');
