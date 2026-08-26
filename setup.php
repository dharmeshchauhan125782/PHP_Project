<?php
/**
 * ONE-TIME SETUP SCRIPT
 * Run this once in your browser after importing sql/database.sql
 * (e.g. http://localhost:8000/setup.php) to correctly hash the
 * default admin password using PHP's own password_hash() function.
 * You can delete this file after running it.
 */
require_once __DIR__ . '/config/db.php';

$pdo = getDB();
$defaultPassword = 'Admin@123';

$stmt = $pdo->prepare("SELECT id, password FROM admin WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

$message = '';
$isError = false;

if (!$admin) {
    $hash = password_hash($defaultPassword, PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO admin (username, password, name) VALUES ('admin', ?, 'Hotel Administrator')")->execute([$hash]);
    $message = "Admin account created. Username: admin | Password: $defaultPassword";
} elseif (!password_verify($defaultPassword, $admin['password']) && strpos($admin['password'], '$2y$') !== 0) {
    $hash = password_hash($defaultPassword, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?")->execute([$hash, $admin['id']]);
    $message = "Admin password hash generated successfully. Username: admin | Password: $defaultPassword";
} else {
    $message = "Admin account is already set up correctly. You can delete this file.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Luxury Stay — Setup</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=Jost:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--navy-950);">
    <div class="card" style="max-width:480px; text-align:center;">
        <div class="crest" style="margin:0 auto 18px;"><span>LS</span></div>
        <h2>Setup Complete</h2>
        <p style="color:var(--ink-soft); margin-top:10px;"><?= htmlspecialchars($message) ?></p>
        <a href="admin/login.php" class="btn btn-gold" style="margin-top:20px; display:inline-flex;">Go to Admin Login</a>
        <p style="font-size:12.5px; color:var(--ink-soft); margin-top:18px;">For security, delete setup.php from your server after use.</p>
    </div>
</body>
</html>
