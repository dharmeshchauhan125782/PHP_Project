<?php
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDB();
    $stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM rooms');
    $row = $stmt->fetch();
    echo json_encode(['success' => true, 'rooms_count' => (int)$row['cnt']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Usage: open http://localhost:8000/db_test.php after importing the SQL and
// ensuring DB credentials in config/db.php are correct.
