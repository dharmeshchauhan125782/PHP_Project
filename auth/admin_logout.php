<?php
require_once __DIR__ . '/../includes/functions.php';

if (isAdminLoggedIn()) {
    logActivity('admin_logout', "Admin '{$_SESSION['admin_name']}' logged out");
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

jsonResponse(true, 'Logged out successfully.');
