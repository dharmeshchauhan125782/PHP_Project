<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (isUserLoggedIn()) { header('Location: /Frontend/pages/dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — LuxuryStay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
</head>
<body>
<div class="auth-shell">
    <div class="auth-visual">
        <div class="auth-visual-content">
            <div class="brand-mark">LS</div>
            <h2 style="color:#fff;">Join LuxuryStay</h2>
            <p style="color:rgba(255,255,255,0.75);">Create an account to book any of our 50 rooms, track your reservations, and hear about hotel updates first.</p>
        </div>
    </div>
    <div class="auth-form-side">
        <div class="auth-card">
            <a href="<?= BASE_URL ?>/Frontend/pages/index.php" style="font-family:var(--font-mono); font-size:12px; color:var(--ink-soft);">&larr; Back to site</a>
            <h2 style="margin-top:16px;">Create Account</h2>
            <p class="sub">A few details and you're ready to book.</p>

            <div id="register-msg" class="form-msg"></div>

            <form id="register-form" onsubmit="event.preventDefault(); doRegister();">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="reg-name" required autocomplete="name">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="reg-email" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" id="reg-phone" placeholder="+91 98765 43210" autocomplete="tel">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="reg-password" required autocomplete="new-password" minlength="6">
                    <span style="font-size:12px; color:var(--ink-soft);">Minimum 6 characters.</span>
                </div>
                <button type="submit" class="btn btn-brass btn-block" id="register-btn">Create Account</button>
            </form>
            <div class="auth-switch">Already have an account? <a href="<?= BASE_URL ?>/Frontend/pages/login.php">Sign in</a></div>
        </div>
    </div>
</div>

<script>window.__BASE_URL__ = <?= json_encode(BASE_URL) ?>; window.__CSRF_TOKEN__ = <?= json_encode(csrfToken()) ?>;</script>
<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
async function doRegister() {
  const btn = document.getElementById('register-btn');
  const msg = document.getElementById('register-msg');
  msg.className = 'form-msg';
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Creating account…';

  const res = await apiPostJson('/api/register.php', {
    name: document.getElementById('reg-name').value.trim(),
    email: document.getElementById('reg-email').value.trim(),
    phone: document.getElementById('reg-phone').value.trim(),
    password: document.getElementById('reg-password').value,
  });

  btn.disabled = false;
  btn.textContent = 'Create Account';

  if (!res.success) {
    msg.textContent = res.message;
    msg.className = 'form-msg error';
    return;
  }

  window.location.href = '<?= BASE_URL ?>/Frontend/pages/dashboard.php';
}
</script>
</body>
</html>
