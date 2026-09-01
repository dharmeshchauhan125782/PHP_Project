<?php
/**
 * Application-wide configuration constants.
 * Centralized here so nothing is hardcoded across multiple frontend files.
 */

if (!defined('SESSION_IDLE_TIMEOUT_SECONDS')) {

// Session / security
define('SESSION_IDLE_TIMEOUT_SECONDS', 30 * 60); // 30 minutes
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 15 * 60); // 15 minutes after max attempts

// File upload rules (room / gallery images)
define('UPLOAD_MAX_BYTES', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_MIME', serialize(['image/jpeg', 'image/png', 'image/webp']));
define('UPLOAD_ALLOWED_EXT', serialize(['jpg', 'jpeg', 'png', 'webp']));
define('UPLOAD_ROOMS_DIR', __DIR__ . '/../../Frontend/images/rooms');
define('UPLOAD_GALLERY_DIR', __DIR__ . '/../../Frontend/images/gallery');

// Booking rules
define('BOOKING_REF_PREFIX', 'LS-');
define('MIN_CAPACITY_GUESTS', 1);
define('MAX_ADULTS_PER_BOOKING', 10);
define('MAX_CHILDREN_PER_BOOKING', 10);

// Currency (display only; all calculations are plain decimals)
// NOTE: named APP_CURRENCY_SYMBOL (not CURRENCY_SYMBOL) because PHP's
// "standard" extension already defines a built-in global constant
// named CURRENCY_SYMBOL (value 262145, unrelated to money). Using that
// name would silently no-op our define() and corrupt anything that
// concatenated it expecting the ₹ symbol.
define('APP_CURRENCY_SYMBOL', '₹');

}

if (!defined('BASE_URL')) {
    // Works out the URL prefix the app is served under, e.g. '' if this
    // project sits directly in htdocs, or '/LuxuryStay' if it sits in a
    // subfolder like htdocs/LuxuryStay. Every hardcoded "/Frontend/..." or
    // "/Backend/..." path in the app should be prefixed with BASE_URL so it
    // works no matter which folder name the project is placed under.
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../..'));
    $basePath = '';
    if ($docRoot && $projectRoot && strpos($projectRoot, $docRoot) === 0) {
        $basePath = rtrim(substr($projectRoot, strlen($docRoot)), '/');
    }
    define('BASE_URL', $basePath); // '' or '/LuxuryStay' (or whatever folder name you used)
}