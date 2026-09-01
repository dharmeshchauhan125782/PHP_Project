<?php
/**
 * AvailabilityService
 *
 * A room is unavailable for a date range ONLY if it has another
 * 'pending' or 'approved' booking whose [check_in, check_out) range
 * overlaps the requested range. Rejected/cancelled/checked_out
 * bookings never block availability. Rooms manually flagged
 * 'maintenance' are unavailable for all dates.
 *
 * This intentionally does NOT use a permanent rooms.status='occupied'
 * flag — that would incorrectly block a room forever after a single
 * booking, which is exactly the bug the spec (section 11) calls out.
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Two half-open date ranges [inA, outA) and [inB, outB) overlap iff
 * inA < outB AND inB < outA. Same-day checkout/checkin (back-to-back
 * bookings) is allowed since the ranges are half-open.
 */
function dateRangesOverlap($checkInA, $checkOutA, $checkInB, $checkOutB) {
    return ($checkInA < $checkOutB) && ($checkInB < $checkOutA);
}

/**
 * Returns true if $roomId is bookable for [$checkIn, $checkOut).
 * $excludeBookingId lets you check availability while editing an
 * existing booking without it blocking against itself.
 */
function isRoomAvailable(PDO $pdo, $roomId, $checkIn, $checkOut, $excludeBookingId = null) {
    $room = getRoomOrNull($pdo, $roomId);
    if (!$room || !$room['is_active']) return false;
    if ($room['status'] === 'maintenance') return false;

    $sql = "SELECT COUNT(*) FROM bookings
            WHERE room_id = ?
              AND status IN ('pending','approved')
              AND NOT (check_out <= ? OR check_in >= ?)";
    $params = [$roomId, $checkIn, $checkOut];

    if ($excludeBookingId) {
        $sql .= " AND id != ?";
        $params[] = $excludeBookingId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ((int)$stmt->fetchColumn()) === 0;
}

/**
 * Lock a room's overlapping-booking rows for update inside an existing
 * transaction, to prevent a race between two simultaneous bookings for
 * the same room/dates. Call this AFTER $pdo->beginTransaction().
 * Returns true if the room is available (and now locked until commit).
 */
function lockAndCheckAvailability(PDO $pdo, $roomId, $checkIn, $checkOut, $excludeBookingId = null) {
    $sql = "SELECT id FROM bookings
            WHERE room_id = ?
              AND status IN ('pending','approved')
              AND NOT (check_out <= ? OR check_in >= ?)";
    $params = [$roomId, $checkIn, $checkOut];

    if ($excludeBookingId) {
        $sql .= " AND id != ?";
        $params[] = $excludeBookingId;
    }
    $sql .= " FOR UPDATE";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() === false; // no overlapping rows locked
}

function getRoomOrNull(PDO $pdo, $roomId) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();
    return $room ?: null;
}

/**
 * Returns a list of room IDs that ARE available for the given date range,
 * out of the given candidate room IDs (or all active rooms if null).
 * Used by rooms_search to only show bookable rooms for selected dates.
 */
function filterAvailableRoomIds(PDO $pdo, $checkIn, $checkOut, array $roomIds = null) {
    $sql = "SELECT r.id FROM rooms r
            WHERE r.is_active = 1 AND r.status != 'maintenance'
              AND r.id NOT IN (
                SELECT b.room_id FROM bookings b
                WHERE b.status IN ('pending','approved')
                  AND NOT (b.check_out <= ? OR b.check_in >= ?)
              )";
    $params = [$checkIn, $checkOut];

    if ($roomIds !== null && count($roomIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
        $sql .= " AND r.id IN ($placeholders)";
        $params = array_merge($params, $roomIds);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return array_column($stmt->fetchAll(), 'id');
}
