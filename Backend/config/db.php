<?php
/**
 * Database configuration - LuxuryStay
 * Update these credentials to match your local XAMPP / WAMP / MySQL setup.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'luxurystay');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            // Never leak DB credentials or raw driver errors to the client.
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.', 'data' => []]));
        }
    }
    return $pdo;
}
