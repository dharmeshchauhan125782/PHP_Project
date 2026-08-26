<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdminLogin();

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    jsonResponse(true, '', ['items' => $pdo->query("SELECT * FROM gallery ORDER BY created_at DESC")->fetchAll()]);
}

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, 'Item deleted.');
    }

    if ($action === 'save') {
        $title    = clean($_POST['title'] ?? '');
        $category = clean($_POST['category'] ?? 'general');
        $testimonialText   = clean($_POST['testimonial_text'] ?? '');
        $testimonialAuthor = clean($_POST['testimonial_author'] ?? '');

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $destDir = __DIR__ . '/../../assets/uploads/gallery/';
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                $filename = uniqid('gallery_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destDir . $filename)) {
                    $imagePath = 'assets/uploads/gallery/' . $filename;
                }
            }
        }

        if (!$title) {
            jsonResponse(false, 'Title is required.');
        }

        $stmt = $pdo->prepare("INSERT INTO gallery (title, image_path, category, testimonial_text, testimonial_author) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $imagePath, $category, $testimonialText ?: null, $testimonialAuthor ?: null]);
        jsonResponse(true, 'Gallery item added.');
    }

    jsonResponse(false, 'Unknown action.');
}

jsonResponse(false, 'Invalid request method.');
