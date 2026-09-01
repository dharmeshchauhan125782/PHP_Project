<?php
/**
 * One-time setup script.
 *
 * The seed data in Database/database.sql inserts a placeholder string
 * ('CHANGE_ME_RUN_SETUP_PHP') into admin.password because SQL cannot
 * call PHP's password_hash(). Run this script ONCE in your browser
 * after importing the database to generate a real bcrypt hash for the
 * default admin account (username: admin / password: Admin@123).
 *
 * The script refuses to run again once a real hash is already in place,
 * and the account is flagged must_change_password=1 so you're forced to
 * pick your own password on first login anyway.
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: text/plain');

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, password FROM admin WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if (!$admin) {
    die("No default admin account found. Did you import Database/database.sql?\n");
}

if ($admin['password'] !== 'CHANGE_ME_RUN_SETUP_PHP') {
    die("Setup has already been run for this account. For security, this script will not overwrite an existing password hash.\nIf you need to reset the admin password, do so directly in the database or build an authenticated reset flow.\n");
}

$defaultPassword = 'Admin@123';
$hash = password_hash($defaultPassword, PASSWORD_BCRYPT);

$pdo->prepare("UPDATE admin SET password = ?, must_change_password = 1 WHERE id = ?")
    ->execute([$hash, $admin['id']]);

echo "Setup complete.\n\n";
echo "Default admin login:\n";
echo "  Username: admin\n";
echo "  Password: {$defaultPassword}\n\n";
echo "You will be required to change this password immediately after your first login.\n";
echo "For security, delete or restrict access to this file (Backend/config/setup.php) now that setup is complete.\n";
