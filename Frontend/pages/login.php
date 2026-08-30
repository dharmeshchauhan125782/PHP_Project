<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (isUserLoggedIn()) { header('Location: /Frontend/pages/dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — LuxuryStay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
</head>
<body>
<div class="auth-shell">
    <div class="auth-visual">
        <div class="auth-visual-content">
            <div class="brand-mark">LS</div>
            <h2 style="color:#fff;">Welcome back to LuxuryStay</h2>
            <p style="color:rgba(255,255,255,0.75);">Sign in to manage your bookings, view meal preferences, and stay updated on hotel announcements.</p>
        </div>
    </div>
    <div class="auth-form-side">
        <div class="auth-card">
            <a href="<?= BASE_URL ?>/Frontend/pages/index.php" style="font-family:var(--font-mono); font-size:12px; color:var(--ink-soft);">&larr; Back to site</a>
            <h2 style="margin-top:16px;">Sign In</h2>
            <p class="sub">Enter your details to access your dashboard.</p>

            <div id="login-msg" class="form-msg"></div>

            <form id="login-form" onsubmit="event.preventDefault(); doLogin();">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="login-email" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="login-password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-brass btn-block" id="login-btn">Sign In</button>
            </form>
            <div class="auth-switch">Don't have an account? <a href="<?= BASE_URL ?>/Frontend/pages/register.php">Create one</a></div>
        </div>
    </div>
</div>

<script>window.__BASE_URL__ = <?= json_encode(BASE_URL) ?>; window.__CSRF_TOKEN__ = <?= json_encode(csrfToken()) ?>;</script>
<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
async function doLogin() {
  const btn = document.getElementById('login-btn');
  const msg = document.getElementById('login-msg');
  msg.className = 'form-msg';
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Signing in…';

  const res = await apiPostJson('/api/login.php', {
    email: document.getElementById('login-email').value.trim(),
    password: document.getElementById('login-password').value,
  });

  btn.disabled = false;
  btn.textContent = 'Sign In';

  if (!res.success) {
    msg.textContent = res.message;
    msg.className = 'form-msg error';
    return;
  }

  const params = new URLSearchParams(window.location.search);
  const redirect = params.get('redirect');
  window.location.href = redirect || '<?= BASE_URL ?>/Frontend/pages/dashboard.php';
}
</script>
</body>
</html>
