<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (!isAdminLoggedIn()) { header('Location: /Admin/pages/login.php'); exit; }
$activeSection = 'activity-logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Logs — LuxuryStay Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
</head>
<body>
<div class="dash-shell">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>
  <main class="dash-main">
    <div class="dash-topbar">
      <div><h2 style="margin-bottom:4px;">Activity Logs</h2><p style="color:var(--ink-soft);">A full audit trail of admin actions.</p></div>
    </div>

    <div class="table-wrap">
      <table>
        <thead><tr><th>Time</th><th>Admin</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
        <tbody id="logs-body"><tr><td colspan="5" style="text-align:center; padding:32px;">Loading…</td></tr></tbody>
      </table>
    </div>
    <div style="display:flex; justify-content:center; gap:8px; margin-top:20px;" id="pagination"></div>
  </main>
</div>

<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
function escapeHtml(str) { const d = document.createElement('div'); d.textContent = str ?? ''; return d.innerHTML; }
let CURRENT_PAGE = 1;

async function loadLogs(page = 1) {
  CURRENT_PAGE = page;
  const res = await fetch(`${window.__BASE_URL__ || ""}/Backend/api/admin_activity_logs.php?page=${page}`, { credentials: 'same-origin' }).then(r => r.json());
  if (!res.success) { toast(res.message, 'error'); return; }
  const tbody = document.getElementById('logs-body');
  if (!res.data.logs.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:32px; color:var(--ink-soft);">No activity yet.</td></tr>';
    return;
  }
  tbody.innerHTML = res.data.logs.map(l => `
    <tr>
      <td class="mono" style="font-size:12px;">${new Date(l.created_at).toLocaleString('en-IN')}</td>
      <td>${escapeHtml(l.admin_name || '—')}</td>
      <td><span class="badge badge-pending" style="text-transform:none;">${escapeHtml(l.action)}</span></td>
      <td>${escapeHtml(l.description)}</td>
      <td class="mono" style="font-size:12px; color:var(--ink-soft);">${escapeHtml(l.ip_address || '—')}</td>
    </tr>
  `).join('');

  const totalPages = Math.ceil(res.data.total / res.data.per_page);
  const pag = document.getElementById('pagination');
  pag.innerHTML = '';
  if (totalPages > 1) {
    for (let p = 1; p <= totalPages; p++) {
      const btn = document.createElement('button');
      btn.className = 'btn btn-sm ' + (p === page ? 'btn-pine' : 'btn-ghost');
      btn.textContent = p;
      btn.onclick = () => loadLogs(p);
      pag.appendChild(btn);
    }
  }
}

loadLogs();
</script>
</body>
</html>
