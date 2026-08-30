<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (!isAdminLoggedIn()) { header('Location: /Admin/pages/login.php'); exit; }
$activeSection = 'announcements';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcements — LuxuryStay Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
<style>.announce-row { padding:20px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }</style>
</head>
<body>
<div class="dash-shell">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>
  <main class="dash-main">
    <div class="dash-topbar">
      <div><h2 style="margin-bottom:4px;">Announcements</h2><p style="color:var(--ink-soft);">Published notices appear on the customer site and dashboard.</p></div>
      <button class="btn btn-brass" onclick="document.getElementById('announce-modal').classList.add('open')">+ New Announcement</button>
    </div>

    <div id="announce-list"><div class="skeleton" style="height:100px; margin-bottom:12px;"></div></div>
  </main>
</div>

<div class="modal-overlay" id="announce-modal">
  <div class="modal-box" style="max-width:520px;">
    <div class="modal-header"><h3>New Announcement</h3><button class="modal-close" onclick="document.getElementById('announce-modal').classList.remove('open')">&times;</button></div>
    <div class="modal-body">
      <div id="announce-msg" class="form-msg"></div>
      <div class="form-group"><label>Title</label><input type="text" id="an-title"></div>
      <div class="form-group">
        <label>Type</label>
        <select id="an-type"><option>General</option><option>Important</option><option>Booking</option><option>Room</option><option>Maintenance</option><option>Policy</option></select>
      </div>
      <div class="form-group"><label>Message</label><textarea id="an-message" rows="4"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="document.getElementById('announce-modal').classList.remove('open')">Cancel</button>
      <button class="btn btn-brass" onclick="publishAnnouncement()">Publish</button>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
function escapeHtml(str) { const d = document.createElement('div'); d.textContent = str ?? ''; return d.innerHTML; }

async function loadAnnouncements() {
  const res = await fetch('<?= BASE_URL ?>/Backend/api/admin_announcements.php', { credentials: 'same-origin' }).then(r => r.json());
  if (!res.success) { toast(res.message, 'error'); return; }
  setCsrfToken(res.data.csrf_token);
  const list = document.getElementById('announce-list');
  if (!res.data.announcements.length) {
    list.innerHTML = '<div class="empty-state"><p>No announcements yet.</p></div>';
    return;
  }
  list.innerHTML = res.data.announcements.map(a => `
    <div class="card announce-row">
      <div>
        <span class="badge ${a.is_active ? 'badge-approved' : 'badge-cancelled'}">${a.is_active ? 'Live' : 'Hidden'}</span>
        <span style="font-family:var(--font-mono); font-size:11px; color:var(--brass-deep); margin-left:8px; text-transform:uppercase;">${escapeHtml(a.type)}</span>
        <h3 style="margin:8px 0 4px;">${escapeHtml(a.title)}</h3>
        <p style="white-space:pre-line; font-size:13.5px;">${escapeHtml(a.message)}</p>
        <div style="font-family:var(--font-mono); font-size:11px; color:var(--ink-soft);">By ${escapeHtml(a.published_by)} · ${new Date(a.created_at).toLocaleDateString('en-IN')}</div>
      </div>
      <div style="display:flex; flex-direction:column; gap:6px;">
        <button class="btn btn-ghost btn-sm" onclick="toggleAnnouncement(${a.id})">${a.is_active ? 'Hide' : 'Show'}</button>
        <button class="btn btn-danger btn-sm" onclick="deleteAnnouncement(${a.id})">Delete</button>
      </div>
    </div>
  `).join('');
}

async function publishAnnouncement() {
  const msg = document.getElementById('announce-msg');
  const title = document.getElementById('an-title').value.trim();
  const message = document.getElementById('an-message').value.trim();
  const type = document.getElementById('an-type').value;
  if (!title) { msg.textContent = 'Title is required.'; msg.className = 'form-msg error'; return; }
  if (!message) { msg.textContent = 'Message is required.'; msg.className = 'form-msg error'; return; }

  const res = await apiPostJson('/api/admin_announcements.php', { action: 'create', title, message, type });
  if (!res.success) { msg.textContent = res.message; msg.className = 'form-msg error'; return; }
  toast('Announcement published.', 'success');
  document.getElementById('announce-modal').classList.remove('open');
  document.getElementById('an-title').value = '';
  document.getElementById('an-message').value = '';
  loadAnnouncements();
}

async function toggleAnnouncement(id) {
  const res = await apiPostJson('/api/admin_announcements.php', { action: 'toggle_active', id });
  if (res.success) loadAnnouncements(); else toast(res.message, 'error');
}
async function deleteAnnouncement(id) {
  if (!confirm('Delete this announcement?')) return;
  const res = await apiPostJson('/api/admin_announcements.php', { action: 'delete', id });
  if (res.success) { toast('Deleted.', 'success'); loadAnnouncements(); } else toast(res.message, 'error');
}

loadAnnouncements();
</script>
</body>
</html>
