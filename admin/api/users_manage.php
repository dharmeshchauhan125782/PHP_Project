<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdminLogin();

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $search = clean($_GET['search'] ?? '');
    if ($search) {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, created_at FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
        $like = "%$search%";
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $pdo->query("SELECT id, name, email, phone, created_at FROM users ORDER BY created_at DESC");
    }
    jsonResponse(true, '', ['users' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $input = getJsonInput();
    if (($input['action'] ?? '') === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, 'User removed successfully.');
    }
    jsonResponse(false, 'Unknown action.');
}

jsonResponse(false, 'Invalid request method.');
