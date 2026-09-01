<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$pdo = getDB();

$page = cleanInt($_GET['page'] ?? 1, 1) ?? 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

$actionFilter = clean($_GET['action'] ?? '');

$sql = "SELECT l.*, a.name AS admin_name FROM activity_logs l
        LEFT JOIN admin a ON a.id = l.admin_id";
$params = [];
if ($actionFilter) {
    $sql .= " WHERE l.action = ?";
    $params[] = $actionFilter;
}
$sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
foreach ($params as $i => $p) {
    $stmt->bindValue($i + 1, $p);
}
$stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();

$logs = $stmt->fetchAll();
$total = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();

jsonResponse(true, '', ['logs' => $logs, 'total' => $total, 'page' => $page, 'per_page' => $perPage]);
