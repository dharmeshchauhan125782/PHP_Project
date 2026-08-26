<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

header_remove('X-Powered-By');

/** Send a JSON response and stop execution */
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], ['data' => $data]));
    exit;
}

/** Basic input sanitizer */
function clean($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** CSRF token helpers */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/** Auth guards */
function isUserLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return !empty($_SESSION['admin_id']);
}

function requireUserLogin() {
    if (!isUserLoggedIn()) {
        jsonResponse(false, 'Please log in to continue.');
    }
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        jsonResponse(false, 'Admin authentication required.');
    }
}

/** Read JSON body of a request (for fetch() POSTs) */
function getJsonInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
