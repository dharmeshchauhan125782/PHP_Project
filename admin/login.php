<?php
require_once __DIR__ . '/../includes/functions.php';
if (isAdminLoggedIn()) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Luxury Stay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-shell">
    <div class="auth-visual">
        <div class="auth-visual-content">
            <div class="crest on-dark"><span>LS</span></div>
            <h2>Luxury Stay Admin</h2>
            <p>Manage rooms, bookings, guests and the gallery from one control center.</p>
        </div>
    </div>
    <div class="auth-form-side">
        <div class="auth-card">
            <a href="../index.php" style="font-size:13px; color:var(--ink-soft);">← Back to site</a>
            <h1 style="margin-top:18px;">Admin Sign In</h1>
            <p class="sub">Authorized personnel only.</p>
            <div id="form-msg" class="form-msg"></div>
            <form id="admin-login-form">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-gold btn-block" id="submit-btn">Sign In</button>
            </form>
            <p class="auth-switch" style="font-size:12.5px;">Default: <strong>admin</strong> / <strong>Admin@123</strong> (change after first login)</p>
        </div>
    </div>
</div>

<script src="../assets/js/api.js"></script>
<script>
document.getElementById('admin-login-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    const msg = document.getElementById('form-msg');
    btn.disabled = true;
    btn.textContent = 'Signing in…';
    const fd = new FormData(this);
    const res = await apiPostJson('api/login.php', { username: fd.get('username'), password: fd.get('password') });
    if (res.success) {
        window.location.href = 'dashboard.php';
    } else {
        msg.className = 'form-msg error';
        msg.textContent = res.message;
        btn.disabled = false;
        btn.textContent = 'Sign In';
    }
});
</script>
</body>
</html>
