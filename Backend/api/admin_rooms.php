<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/UploadService.php';
requireAdminLogin();

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

const ROOM_TYPES = ['Standard Room', 'Deluxe Room', 'Super Deluxe Room', 'Suite Room'];
const ROOM_STATUSES = ['available', 'maintenance'];

// LIST all rooms (including maintenance / inactive, for admin visibility)
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'history') {
        $roomId = cleanInt($_GET['room_id'] ?? null, 1);
        if (!$roomId) jsonResponse(false, 'A valid room id is required.');
        $stmt = $pdo->prepare("SELECT id, booking_ref, guest_name, check_in, check_out, status, total_price, created_at
            FROM bookings WHERE room_id = ? ORDER BY created_at DESC LIMIT 100");
        $stmt->execute([$roomId]);
        jsonResponse(true, '', ['history' => $stmt->fetchAll()]);
    }

    $rooms = $pdo->query("SELECT * FROM rooms ORDER BY room_number ASC")->fetchAll();
    $imgStmt = $pdo->prepare("SELECT id, image_path FROM room_images WHERE room_id = ? ORDER BY sort_order ASC");
    foreach ($rooms as &$room) {
        $room['amenities'] = json_decode($room['amenities'] ?? '[]', true) ?: [];
        $imgStmt->execute([$room['id']]);
        $room['images'] = $imgStmt->fetchAll();
    }
    unset($room);
    jsonResponse(true, '', ['rooms' => $rooms, 'csrf_token' => csrfToken()]);
}

if ($method === 'POST') {
    // Multipart form uploads can't send a JSON body, so accept the CSRF
    // token from a regular POST field here instead of getJsonInput().
    $csrfFromForm = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($csrfFromForm)) {
        jsonResponse(false, 'Invalid or expired security token. Please refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = cleanInt($_POST['id'] ?? null, 1);
        if (!$id) jsonResponse(false, 'A valid room id is required.');

        // Soft delete: preserve booking history integrity (rooms.id is a FK
        // target from bookings), just hide the room from customer-facing views.
        $stmt = $pdo->prepare("UPDATE rooms SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        logActivity('room_deleted', "Deactivated room #{$id}", 'room', $id);
        jsonResponse(true, 'Room removed from listings.');
    }

    if ($action === 'set_status') {
        $id = cleanInt($_POST['id'] ?? null, 1);
        $status = $_POST['status'] ?? '';
        if (!$id || !in_array($status, ROOM_STATUSES, true)) {
            jsonResponse(false, 'A valid room and status are required.');
        }
        $pdo->prepare("UPDATE rooms SET status = ? WHERE id = ?")->execute([$status, $id]);
        logActivity('room_status_changed', "Room #{$id} status set to {$status}", 'room', $id);
        jsonResponse(true, 'Room status updated.');
    }

    if ($action === 'delete_image') {
        $imgId = cleanInt($_POST['image_id'] ?? null, 1);
        if (!$imgId) jsonResponse(false, 'A valid image id is required.');
        $pdo->prepare("DELETE FROM room_images WHERE id = ?")->execute([$imgId]);
        jsonResponse(true, 'Image removed.');
    }

    if ($action === 'save') {
        $id       = cleanInt($_POST['id'] ?? 0, 0) ?? 0;
        $number   = clean($_POST['room_number'] ?? '');
        $type     = clean($_POST['room_type'] ?? '');
        $floor    = cleanInt($_POST['floor'] ?? 1, 1, 50) ?? 1;
        $desc     = clean($_POST['description'] ?? '');
        $price    = isset($_POST['price_per_night']) ? (float)$_POST['price_per_night'] : 0;
        $capacity = cleanInt($_POST['capacity'] ?? null, 1, 20);
        $status   = in_array($_POST['status'] ?? '', ROOM_STATUSES, true) ? $_POST['status'] : 'available';
        $amenitiesRaw = $_POST['amenities'] ?? '[]';
        $amenitiesArr = json_decode($amenitiesRaw, true);
        if (!is_array($amenitiesArr)) $amenitiesArr = [];
        $amenitiesArr = array_slice(array_map('clean', $amenitiesArr), 0, 30);

        if (!$number || strlen($number) > 20) {
            jsonResponse(false, 'A valid room number is required.');
        }
        if (!in_array($type, ROOM_TYPES, true)) {
            jsonResponse(false, 'Room type must be one of: ' . implode(', ', ROOM_TYPES) . '.');
        }
        if ($price <= 0 || $price > 1000000) {
            jsonResponse(false, 'Please enter a valid price per night.');
        }
        if (!$capacity) {
            jsonResponse(false, 'Please enter a valid capacity (1-20 guests).');
        }

        // Uniqueness check on room_number (excluding self when editing)
        $checkStmt = $pdo->prepare("SELECT id FROM rooms WHERE room_number = ? AND id != ?");
        $checkStmt->execute([$number, $id]);
        if ($checkStmt->fetch()) {
            jsonResponse(false, "Room number {$number} is already in use.");
        }

        $coverPath = null;
        if (!empty($_FILES['cover_image']['name'])) {
            $result = handleSecureImageUpload($_FILES['cover_image'], UPLOAD_ROOMS_DIR, '<?= BASE_URL ?>/Frontend/images/rooms');
            if (!$result['success']) {
                jsonResponse(false, $result['error']);
            }
            $coverPath = $result['path'];
        }

        $amenitiesJson = json_encode($amenitiesArr);

        if ($id > 0) {
            $existing = $pdo->prepare("SELECT id FROM rooms WHERE id = ?");
            $existing->execute([$id]);
            if (!$existing->fetch()) {
                jsonResponse(false, 'Room not found.');
            }
            if ($coverPath) {
                $stmt = $pdo->prepare("UPDATE rooms SET room_number=?, room_type=?, floor=?, description=?, amenities=?, price_per_night=?, capacity=?, status=?, cover_image=? WHERE id=?");
                $stmt->execute([$number, $type, $floor, $desc, $amenitiesJson, $price, $capacity, $status, $coverPath, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE rooms SET room_number=?, room_type=?, floor=?, description=?, amenities=?, price_per_night=?, capacity=?, status=? WHERE id=?");
                $stmt->execute([$number, $type, $floor, $desc, $amenitiesJson, $price, $capacity, $status, $id]);
            }
            $roomId = $id;
            $msg = 'Room updated successfully.';
            logActivity('room_updated', "Updated room {$number}", 'room', $roomId);
        } else {
            $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type, floor, description, amenities, price_per_night, capacity, status, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$number, $type, $floor, $desc, $amenitiesJson, $price, $capacity, $status, $coverPath]);
            $roomId = $pdo->lastInsertId();
            $msg = 'Room created successfully.';
            logActivity('room_added', "Added room {$number}", 'room', $roomId);
        }

        // Additional gallery images
        if (!empty($_FILES['gallery_images']['name'][0])) {
            $maxOrderStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order),-1) FROM room_images WHERE room_id = ?");
            $maxOrderStmt->execute([$roomId]);
            $nextOrder = (int)$maxOrderStmt->fetchColumn() + 1;

            foreach ($_FILES['gallery_images']['name'] as $i => $name) {
                if (!$name) continue;
                $file = [
                    'name' => $_FILES['gallery_images']['name'][$i],
                    'type' => $_FILES['gallery_images']['type'][$i],
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                    'error' => $_FILES['gallery_images']['error'][$i],
                    'size' => $_FILES['gallery_images']['size'][$i],
                ];
                $result = handleSecureImageUpload($file, UPLOAD_ROOMS_DIR, '<?= BASE_URL ?>/Frontend/images/rooms');
                if ($result['success']) {
                    $pdo->prepare("INSERT INTO room_images (room_id, image_path, sort_order) VALUES (?, ?, ?)")
                        ->execute([$roomId, $result['path'], $nextOrder++]);
                }
            }
        }

        jsonResponse(true, $msg, ['room_id' => $roomId]);
    }

    jsonResponse(false, 'Unknown action.');
}

jsonResponse(false, 'Invalid request method.');
