<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (!isAdminLoggedIn()) { header('Location: /Admin/pages/login.php'); exit; }
$activeSection = 'settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings — LuxuryStay Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
</head>
<body>
<div class="dash-shell">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>
  <main class="dash-main">
    <div class="dash-topbar">
      <div><h2 style="margin-bottom:4px;">Settings</h2><p style="color:var(--ink-soft);">Manage your admin account.</p></div>
    </div>

    <h3>Change Password</h3>
    <div class="card" style="padding:28px; max-width:440px;">
      <div id="pw-msg" class="form-msg"></div>
      <div class="form-group"><label>Current Password</label><input type="password" id="current-pw"></div>
      <div class="form-group"><label>New Password (min 8 characters)</label><input type="password" id="new-pw"></div>
      <button class="btn btn-brass" onclick="changePassword()">Update Password</button>
    </div>
  </main>
</div>

<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
// Prime CSRF token via a lightweight GET first
fetch('<?= BASE_URL ?>/Backend/api/admin_dashboard_stats.php', { credentials: 'same-origin' });

async function changePassword() {
  const msg = document.getElementById('pw-msg');
  const current = document.getElementById('current-pw').value;
  const newPw = document.getElementById('new-pw').value;

  // Need a CSRF token — fetch a lightweight endpoint that returns one
  const primed = await fetch('<?= BASE_URL ?>/Backend/api/admin_rooms.php', { credentials: 'same-origin' }).then(r => r.json());
  if (primed.data && primed.data.csrf_token) setCsrfToken(primed.data.csrf_token);

  const res = await apiPostJson('/auth/admin_change_password.php', { current_password: current, new_password: newPw });
  msg.textContent = res.message;
  msg.className = 'form-msg ' + (res.success ? 'success' : 'error');
  if (res.success) {
    document.getElementById('current-pw').value = '';
    document.getElementById('new-pw').value = '';
    toast('Password updated.', 'success');
  }
}
</script>
</body>
</html>
