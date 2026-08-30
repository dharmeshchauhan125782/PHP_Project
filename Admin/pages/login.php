<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (isAdminLoggedIn()) { header('Location: /Admin/pages/dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Sign In — LuxuryStay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
</head>
<body>
<div class="auth-shell">
    <div class="auth-visual">
        <div class="auth-visual-content">
            <div class="brand-mark">LS</div>
            <h2 style="color:#fff;">LuxuryStay Admin</h2>
            <p style="color:rgba(255,255,255,0.75);">Manage rooms, bookings, pricing, and hotel announcements from one place.</p>
        </div>
    </div>
    <div class="auth-form-side">
        <div class="auth-card">
            <h2>Admin Sign In</h2>
            <p class="sub">Authorized staff only.</p>

            <div id="login-msg" class="form-msg"></div>

            <form id="login-form" onsubmit="event.preventDefault(); doAdminLogin();">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="login-username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="login-password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-brass btn-block" id="login-btn">Sign In</button>
            </form>
        </div>
    </div>
</div>

<!-- Forced password change modal (shown after first login with default password) -->
<div class="modal-overlay" id="force-pw-modal">
    <div class="modal-box" style="max-width:440px;">
        <div class="modal-header"><h3>Set a New Password</h3></div>
        <div class="modal-body">
            <p style="font-size:13.5px;">For security, you must set a new password before continuing.</p>
            <div id="force-pw-msg" class="form-msg"></div>
            <div class="form-group"><label>Current Password</label><input type="password" id="fp-current"></div>
            <div class="form-group"><label>New Password (min 8 characters)</label><input type="password" id="fp-new"></div>
            <button class="btn btn-brass btn-block" onclick="doForcePasswordChange()">Update Password &amp; Continue</button>
        </div>
    </div>
</div>

<script>window.__BASE_URL__ = <?= json_encode(BASE_URL) ?>; window.__CSRF_TOKEN__ = <?= json_encode(csrfToken()) ?>;</script>
<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
let PENDING_ADMIN_PASSWORD = '';

async function doAdminLogin() {
  const btn = document.getElementById('login-btn');
  const msg = document.getElementById('login-msg');
  msg.className = 'form-msg';
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Signing in…';

  const username = document.getElementById('login-username').value.trim();
  const password = document.getElementById('login-password').value;

  const res = await fetch('<?= BASE_URL ?>/Backend/auth/admin_login.php', {
    method: 'POST', credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password }),
  }).then(r => r.json());

  btn.disabled = false;
  btn.textContent = 'Sign In';

  if (!res.success) {
    msg.textContent = res.message;
    msg.className = 'form-msg error';
    return;
  }

  setCsrfToken(res.data.csrf_token);

  if (res.data.must_change_password) {
    PENDING_ADMIN_PASSWORD = password;
    document.getElementById('force-pw-modal').classList.add('open');
    return;
  }

  window.location.href = '<?= BASE_URL ?>/Admin/pages/dashboard.php';
}

async function doForcePasswordChange() {
  const msg = document.getElementById('force-pw-msg');
  const current = document.getElementById('fp-current').value || PENDING_ADMIN_PASSWORD;
  const newPw = document.getElementById('fp-new').value;

  const res = await apiPostJson('/auth/admin_change_password.php', { current_password: current, new_password: newPw });
  msg.textContent = res.message;
  msg.className = 'form-msg ' + (res.success ? 'success' : 'error');
  if (res.success) {
    setTimeout(() => { window.location.href = '<?= BASE_URL ?>/Admin/pages/dashboard.php'; }, 800);
  }
}

document.getElementById('fp-current').addEventListener('focus', function() {
  if (!this.value && PENDING_ADMIN_PASSWORD) this.value = PENDING_ADMIN_PASSWORD;
});
</script>
</body>
</html>
