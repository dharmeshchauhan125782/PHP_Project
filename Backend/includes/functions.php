<?php
/**
 * Core bootstrap included by every Backend API endpoint.
 * Handles: secure session start, DB connection, JSON responses,
 * CSRF tokens, auth guards, and shared input sanitizers.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

// ------------------------------------------------------------
// Secure session bootstrap
// ------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,      // only sent over HTTPS when the site is served over HTTPS
        'httponly' => true,        // not accessible to JavaScript (mitigates XSS cookie theft)
        'samesite' => 'Lax',       // CSRF mitigation while still allowing normal navigation
    ]);
    session_start();

    // Idle session timeout
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT_SECONDS) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

header_remove('X-Powered-By');

// ------------------------------------------------------------
// JSON response helpers
// ------------------------------------------------------------

/** Send a consistent JSON response and stop execution. */
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => (bool)$success,
        'message' => $message,
        'data' => $data,
    ]);
    exit;
}

/** Read and decode the JSON body of a fetch() POST/PUT request. */
function getJsonInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// ------------------------------------------------------------
// Sanitizers / validators
// ------------------------------------------------------------

/**
 * Sanitize a string for SAFE STORAGE: trims whitespace and strips
 * control/null-byte characters that have no legitimate use in names,
 * descriptions, messages, etc. Does NOT HTML-escape — escaping belongs
 * at the point of OUTPUT (see e() for PHP templates, escapeHtml() in
 * Frontend/js/api.js for JS-rendered content), never at the point of
 * storage. Escaping at storage time causes data corruption (an
 * apostrophe in a guest's name becomes the literal text "&#039;"
 * permanently) and double-escaping bugs wherever the value is later
 * escaped again for display.
 */
function clean($value) {
    $value = trim((string)($value ?? ''));
    // Strip control characters (keep normal whitespace/newlines/tabs)
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
}

/** Escape a value for safe inclusion in HTML output (PHP templates). */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Validate an email address, returns normalized email or false. */
function cleanEmail($value) {
    $email = trim((string)($value ?? ''));
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/** Validate a simple phone number (7-20 digits, allows +, spaces, dashes). */
function isValidPhone($value) {
    return (bool)preg_match('/^[0-9+\-\s()]{7,20}$/', trim((string)($value ?? '')));
}

/** Validate a Y-m-d date string strictly (rejects "2026-02-31" etc). */
function parseStrictDate($value) {
    $d = DateTime::createFromFormat('Y-m-d', (string)$value);
    if (!$d) return false;
    $errors = DateTime::getLastErrors();
    if ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) return false;
    return $d;
}

/** Coerce to a positive integer within [min,max], or return null if invalid. */
function cleanInt($value, $min = null, $max = null) {
    if (!is_numeric($value)) return null;
    $n = (int)$value;
    if ($min !== null && $n < $min) return null;
    if ($max !== null && $n > $max) return null;
    return $n;
}

// ------------------------------------------------------------
// CSRF protection
// ------------------------------------------------------------

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)($token ?? ''));
}

/**
 * Require a valid CSRF token for state-changing requests.
 * Accepts the token from the X-CSRF-Token header or a csrf_token body field.
 */
function requireCsrf() {
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($headerToken && verifyCsrf($headerToken)) return;

    $input = getJsonInput();
    if (!empty($input['csrf_token']) && verifyCsrf($input['csrf_token'])) return;

    jsonResponse(false, 'Invalid or expired security token. Please refresh the page and try again.');
}

// ------------------------------------------------------------
// Auth guards
// ------------------------------------------------------------

function isUserLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return !empty($_SESSION['admin_id']);
}

function requireUserLogin() {
    if (!isUserLoggedIn()) {
        http_response_code(401);
        jsonResponse(false, 'Please log in to continue.');
    }
}

/** Every admin API endpoint must call this. Never rely on hidden UI buttons alone. */
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        http_response_code(401);
        jsonResponse(false, 'Admin authentication required.');
    }
}

function requireSuperAdmin() {
    requireAdminLogin();
    if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
        http_response_code(403);
        jsonResponse(false, 'This action requires super admin privileges.');
    }
}

// ------------------------------------------------------------
// Activity log (admin audit trail) — section 18 of the spec
// ------------------------------------------------------------

/**
 * Record an admin action. Call this after any meaningful state change:
 * logActivity('booking_approved', 'Approved booking #LS-000123', 'booking', 123);
 */
function logActivity($action, $description, $targetType = null, $targetId = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action, description, target_type, target_id, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['admin_id'] ?? null,
            $action,
            $description,
            $targetType,
            $targetId,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Exception $e) {
        error_log('logActivity failed: ' . $e->getMessage());
    }
}

// ------------------------------------------------------------
// Notifications — section 24 of the spec
// ------------------------------------------------------------

/**
 * Create an in-app notification for a customer.
 * $type must be one of the notifications.type ENUM values.
 */
function notifyUser($userId, $type, $title, $message, $bookingId = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_booking_id)
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $type, $title, $message, $bookingId]);
    } catch (Exception $e) {
        error_log('notifyUser failed: ' . $e->getMessage());
    }
}

// ------------------------------------------------------------
// Global error handling — never leak internals to the client
// ------------------------------------------------------------

set_exception_handler(function ($e) {
    error_log('Uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    jsonResponse(false, 'Something went wrong. Please try again.');
});

// Convert PHP warnings/notices into logged entries instead of HTML output
// that could corrupt JSON responses or leak file paths to the client.
set_error_handler(function ($severity, $message, $file, $line) {
    error_log("PHP error [$severity]: $message in $file:$line");
    return true; // suppress default HTML output
});
