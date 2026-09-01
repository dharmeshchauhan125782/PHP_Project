<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$input    = getJsonInput();
$email    = strtolower(clean($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');

if (!$email || !$password) {
    jsonResponse(false, 'Email and password are required.');
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, name, password, failed_login_attempts, locked_until FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Generic message for both "no such user" and "wrong password" so we
// never reveal which accounts exist (user enumeration protection).
$genericFail = 'Invalid email or password.';

if ($user && !empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
    $waitMins = ceil((strtotime($user['locked_until']) - time()) / 60);
    jsonResponse(false, "Too many failed attempts. Please try again in {$waitMins} minute(s).");
}

if (!$user || !password_verify($password, $user['password'])) {
    if ($user) {
        $attempts = (int)$user['failed_login_attempts'] + 1;
        if ($attempts >= LOGIN_MAX_ATTEMPTS) {
            $lockUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_SECONDS);
            $pdo->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = ? WHERE id = ?")
                ->execute([$lockUntil, $user['id']]);
            jsonResponse(false, 'Too many failed attempts. Your account is temporarily locked for 15 minutes.');
        }
        $pdo->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?")->execute([$attempts, $user['id']]);
    }
    jsonResponse(false, $genericFail);
}

// Successful login: reset attempts, regenerate session id (prevents session fixation)
$pdo->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?")->execute([$user['id']]);

session_regenerate_id(true);
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['name'];

jsonResponse(true, 'Welcome back, ' . $user['name'] . '!', [
    'name' => $user['name'],
    'csrf_token' => csrfToken(),
]);
