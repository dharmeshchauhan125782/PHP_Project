<?php
require_once __DIR__ . '/includes/functions.php';
if (isUserLoggedIn()) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — Luxury Stay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-shell">
    <div class="auth-visual">
        <div class="auth-visual-content">
            <div class="crest on-dark"><span>LS</span></div>
            <h2>Join Luxury Stay</h2>
            <p>Create an account to book rooms, track your reservations, and enjoy a faster checkout every time.</p>
        </div>
    </div>
    <div class="auth-form-side">
        <div class="auth-card">
            <a href="index.php" style="font-size:13px; color:var(--ink-soft);">← Back to home</a>
            <h1 style="margin-top:18px;">Create Account</h1>
            <p class="sub">It only takes a minute.</p>
            <div id="form-msg" class="form-msg"></div>
            <form id="register-form">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required autofocus>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" minlength="6" required>
                </div>
                <button type="submit" class="btn btn-gold btn-block" id="submit-btn">Create Account</button>
            </form>
            <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>
</div>

<script src="assets/js/api.js"></script>
<script>
document.getElementById('register-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    const msg = document.getElementById('form-msg');
    msg.className = 'form-msg';
    btn.disabled = true;
    btn.textContent = 'Creating account…';

    const fd = new FormData(this);
    const payload = { name: fd.get('name'), email: fd.get('email'), phone: fd.get('phone'), password: fd.get('password') };
    const res = await apiPostJson('api/register.php', payload);

    if (res.success) {
        msg.className = 'form-msg success';
        msg.textContent = res.message;
        window.location.href = 'dashboard.php';
    } else {
        msg.className = 'form-msg error';
        msg.textContent = res.message;
        btn.disabled = false;
        btn.textContent = 'Create Account';
    }
});
</script>
</body>
</html>
