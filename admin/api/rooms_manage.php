<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdminLogin();

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

const TOTAL_ROOMS_LIMIT = 10;
const ROOM_TYPES = ['Standard Room', 'Deluxe Room', 'Super Deluxe Room', 'Suite Room'];
const ROOM_STATUSES = ['available', 'occupied', 'maintenance'];

// LIST
if ($method === 'GET') {
    $rooms = $pdo->query("SELECT * FROM rooms ORDER BY id DESC")->fetchAll();
    foreach ($rooms as &$room) {
        $stmt = $pdo->prepare("SELECT id, image_path FROM room_images WHERE room_id = ?");
        $stmt->execute([$room['id']]);
        $room['images'] = $stmt->fetchAll();
    }
    jsonResponse(true, '', ['rooms' => $rooms]);
}

// CREATE / UPDATE (multipart form for image upload support)
if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, 'Room deleted successfully.');
    }

    if ($action === 'save') {
        $id       = (int)($_POST['id'] ?? 0);
        $number   = clean($_POST['room_number'] ?? '');
        $type     = clean($_POST['room_type'] ?? '');
        $desc     = clean($_POST['description'] ?? '');
        $price    = (float)($_POST['price_per_night'] ?? 0);
        $capacity = (int)($_POST['capacity'] ?? 1);
        $status   = in_array($_POST['status'] ?? '', ROOM_STATUSES) ? $_POST['status'] : 'available';

        if (!$number || !$type || $price <= 0) {
            jsonResponse(false, 'Room number, type and a valid price are required.');
        }
        if (!in_array($type, ROOM_TYPES)) {
            jsonResponse(false, 'Room type must be one of: ' . implode(', ', ROOM_TYPES) . '.');
        }
        // The hotel has a fixed inventory of 10 rooms; block creating more.
        if ($id === 0) {
            $totalRooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
            if ($totalRooms >= TOTAL_ROOMS_LIMIT) {
                jsonResponse(false, 'Room limit reached. This hotel is configured for a maximum of ' . TOTAL_ROOMS_LIMIT . ' rooms.');
            }
        }

        $coverPath = null;
        if (!empty($_FILES['cover_image']['name'])) {
            $coverPath = handleUpload($_FILES['cover_image'], 'rooms');
        }

        if ($id > 0) {
            if ($coverPath) {
                $stmt = $pdo->prepare("UPDATE rooms SET room_number=?, room_type=?, description=?, price_per_night=?, capacity=?, status=?, cover_image=? WHERE id=?");
                $stmt->execute([$number, $type, $desc, $price, $capacity, $status, $coverPath, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE rooms SET room_number=?, room_type=?, description=?, price_per_night=?, capacity=?, status=? WHERE id=?");
                $stmt->execute([$number, $type, $desc, $price, $capacity, $status, $id]);
            }
            $roomId = $id;
            $msg = 'Room updated successfully.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type, description, price_per_night, capacity, status, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$number, $type, $desc, $price, $capacity, $status, $coverPath]);
            $roomId = $pdo->lastInsertId();
            $msg = 'Room created successfully.';
        }

        // Additional gallery images for this room
        if (!empty($_FILES['gallery_images']['name'][0])) {
            foreach ($_FILES['gallery_images']['name'] as $i => $name) {
                if (!$name) continue;
                $file = [
                    'name' => $_FILES['gallery_images']['name'][$i],
                    'type' => $_FILES['gallery_images']['type'][$i],
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                    'error' => $_FILES['gallery_images']['error'][$i],
                    'size' => $_FILES['gallery_images']['size'][$i],
                ];
                $path = handleUpload($file, 'rooms');
                if ($path) {
                    $stmt = $pdo->prepare("INSERT INTO room_images (room_id, image_path) VALUES (?, ?)");
                    $stmt->execute([$roomId, $path]);
                }
            }
        }

        jsonResponse(true, $msg, ['room_id' => $roomId]);
    }

    if ($action === 'delete_image') {
        $imgId = (int)($_POST['image_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM room_images WHERE id = ?");
        $stmt->execute([$imgId]);
        jsonResponse(true, 'Image removed.');
    }

    jsonResponse(false, 'Unknown action.');
}

jsonResponse(false, 'Invalid request method.');

/** Handle a single file upload, returns relative path or null */
function handleUpload($file, $folder) {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null; // 5MB limit

    $destDir = __DIR__ . '/../../assets/uploads/' . $folder . '/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $filename = uniqid($folder . '_', true) . '.' . $ext;
    $destPath = $destDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        return 'assets/uploads/' . $folder . '/' . $filename;
    }
    return null;
}
