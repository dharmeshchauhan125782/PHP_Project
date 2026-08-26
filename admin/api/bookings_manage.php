<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdminLogin();

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $filter = clean($_GET['status'] ?? '');
    $sql = "SELECT b.*, u.name AS guest_name, u.email AS guest_email, r.room_number, r.room_type
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN rooms r ON b.room_id = r.id";
    $params = [];
    if ($filter && in_array($filter, ['pending','approved','checked_out','rejected','cancelled'])) {
        $sql .= " WHERE b.status = ?";
        $params[] = $filter;
    }
    $sql .= " ORDER BY b.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    jsonResponse(true, '', ['bookings' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $input = getJsonInput();
    $action = $input['action'] ?? '';
    $id = (int)($input['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT id, room_id, status FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();
    if (!$booking) {
        jsonResponse(false, 'Booking not found.');
    }

    // Room availability is always driven off room.status, so every action
    // that changes who is occupying a room updates it in the same transaction.
    if ($action === 'approve') {
        if ($booking['status'] !== 'pending') {
            jsonResponse(false, 'Only pending bookings can be approved.');
        }
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?")->execute([$id]);
            $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?")->execute([$booking['room_id']]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Could not approve booking. Please try again.');
        }
        jsonResponse(true, 'Booking approved. Room marked as Occupied.');
    }

    if ($action === 'reject') {
        if ($booking['status'] !== 'pending') {
            jsonResponse(false, 'Only pending bookings can be rejected.');
        }
        $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?")->execute([$id]);
        jsonResponse(true, 'Booking rejected.');
    }

    if ($action === 'checkout') {
        if ($booking['status'] !== 'approved') {
            jsonResponse(false, 'Only approved (checked-in) bookings can be checked out.');
        }
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?")->execute([$id]);
            $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?")->execute([$booking['room_id']]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Could not check out booking. Please try again.');
        }
        jsonResponse(true, 'Guest checked out. Room marked as Available.');
    }

    if ($action === 'delete') {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM bookings WHERE id = ?")->execute([$id]);
            // If this booking was currently holding the room occupied, free it up.
            if ($booking['status'] === 'approved') {
                $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?")->execute([$booking['room_id']]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Could not delete booking. Please try again.');
        }
        jsonResponse(true, 'Booking deleted.');
    }

    jsonResponse(false, 'Unknown action.');
}

jsonResponse(false, 'Invalid request method.');
