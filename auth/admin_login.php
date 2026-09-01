<?php
/**
 * Admin login (spec section 19).
 * - Brute-force protection: locks out after LOGIN_MAX_ATTEMPTS failures.
 * - Session ID regenerated on success (mitigates session fixation).
 * - Returns must_change_password so the frontend can force a password
 *   change screen after first login with the seeded default account.
 */
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$input    = getJsonInput();
$username = clean($input['username'] ?? '');
$password = (string)($input['password'] ?? '');

if (!$username || !$password) {
    jsonResponse(false, 'Username and password are required.');
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, name, password, role, must_change_password, failed_login_attempts, locked_until FROM admin WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch();

$genericFail = 'Invalid admin credentials.';

if ($admin && !empty($admin['locked_until']) && strtotime($admin['locked_until']) > time()) {
    $waitMins = ceil((strtotime($admin['locked_until']) - time()) / 60);
    logActivity('admin_login_blocked', "Login blocked for locked account '{$username}'");
    jsonResponse(false, "Too many failed attempts. Please try again in {$waitMins} minute(s).");
}

if (!$admin || !password_verify($password, $admin['password'])) {
    if ($admin) {
        $attempts = (int)$admin['failed_login_attempts'] + 1;
        if ($attempts >= LOGIN_MAX_ATTEMPTS) {
            $lockUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_SECONDS);
            $pdo->prepare("UPDATE admin SET failed_login_attempts = 0, locked_until = ? WHERE id = ?")
                ->execute([$lockUntil, $admin['id']]);
            logActivity('admin_login_locked', "Account '{$username}' locked after {$attempts} failed attempts");
            jsonResponse(false, 'Too many failed attempts. This admin account is temporarily locked for 15 minutes.');
        }
        $pdo->prepare("UPDATE admin SET failed_login_attempts = ? WHERE id = ?")->execute([$attempts, $admin['id']]);
    }
    jsonResponse(false, $genericFail);
}

$pdo->prepare("UPDATE admin SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?")
    ->execute([$admin['id']]);

session_regenerate_id(true);
$_SESSION['admin_id']   = $admin['id'];
$_SESSION['admin_name'] = $admin['name'];
$_SESSION['admin_role'] = $admin['role'];

logActivity('admin_login', "Admin '{$admin['name']}' logged in");

jsonResponse(true, 'Welcome back, ' . $admin['name'] . '!', [
    'name' => $admin['name'],
    'role' => $admin['role'],
    'must_change_password' => (bool)$admin['must_change_password'],
    'csrf_token' => csrfToken(),
]);
