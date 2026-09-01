<?php
/**
 * ONE-TIME admin password reset.
 * Place this file at: C:\xampp\htdocs\LuxuryStay\Backend\config\reset_admin_password.php
 * Visit it once in your browser: http://localhost/LuxuryStay/Backend/config/reset_admin_password.php
 * Then DELETE this file immediately — it is not safe to leave on a live site.
 */

require_once __DIR__ . '/db.php';

$newPassword = 'Admin@123'; // change this to whatever you want your new password to be
$username    = 'admin';

$pdo = getDB();

$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE admin SET password = ?, must_change_password = 1, failed_login_attempts = 0, locked_until = NULL WHERE username = ?");
$stmt->execute([$hash, $username]);

if ($stmt->rowCount() > 0) {
    echo "Password reset successfully for '{$username}'.\n";
    echo "New password: {$newPassword}\n";
    echo "You will be asked to change it again on next login.\n";
    echo "\nNOW DELETE THIS FILE.";
} else {
    echo "No admin user found with username '{$username}'. Check the username in the admin table and try again.";
}