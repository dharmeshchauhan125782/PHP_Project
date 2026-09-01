<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/UploadService.php';
requireAdminLogin();

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    jsonResponse(true, '', ['items' => $pdo->query("SELECT * FROM gallery ORDER BY created_at DESC")->fetchAll(), 'csrf_token' => csrfToken()]);
}

if ($method === 'POST') {
    $csrfFromForm = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($csrfFromForm)) {
        jsonResponse(false, 'Invalid or expired security token. Please refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = cleanInt($_POST['id'] ?? null, 1);
        if (!$id) jsonResponse(false, 'A valid item id is required.');
        $pdo->prepare("DELETE FROM gallery WHERE id = ?")->execute([$id]);
        logActivity('gallery_item_deleted', "Deleted gallery item #{$id}", 'gallery', $id);
        jsonResponse(true, 'Item deleted.');
    }

    if ($action === 'save') {
        $title    = clean($_POST['title'] ?? '');
        $category = clean($_POST['category'] ?? 'general');
        $testimonialText   = clean($_POST['testimonial_text'] ?? '');
        $testimonialAuthor = clean($_POST['testimonial_author'] ?? '');

        if (!$title || strlen($title) > 150) {
            jsonResponse(false, 'Please enter a valid title.');
        }

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $result = handleSecureImageUpload($_FILES['image'], UPLOAD_GALLERY_DIR, '<?= BASE_URL ?>/Frontend/images/gallery');
            if (!$result['success']) {
                jsonResponse(false, $result['error']);
            }
            $imagePath = $result['path'];
        }

        $stmt = $pdo->prepare("INSERT INTO gallery (title, image_path, category, testimonial_text, testimonial_author) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $imagePath, $category, $testimonialText ?: null, $testimonialAuthor ?: null]);
        logActivity('gallery_item_added', "Added gallery item: {$title}", 'gallery', $pdo->lastInsertId());
        jsonResponse(true, 'Gallery item added.');
    }

    jsonResponse(false, 'Unknown action.');
}

jsonResponse(false, 'Invalid request method.');
