<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (!isAdminLoggedIn()) { header('Location: /Admin/pages/login.php'); exit; }
$activeSection = 'customers';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers — LuxuryStay Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
</head>
<body>
<div class="dash-shell">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>
  <main class="dash-main">
    <div class="dash-topbar">
      <div><h2 style="margin-bottom:4px;">Customers</h2><p style="color:var(--ink-soft);">All registered guests.</p></div>
      <input type="text" id="search-input" placeholder="Search by name or email…" style="padding:10px 14px; border-radius:8px; border:1.5px solid #E2DDD0; width:260px;">
    </div>

    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th>Joined</th><th></th></tr></thead>
        <tbody id="customers-body"><tr><td colspan="6" style="text-align:center; padding:32px;">Loading…</td></tr></tbody>
      </table>
    </div>
  </main>
</div>

<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
function escapeHtml(str) { const d = document.createElement('div'); d.textContent = str ?? ''; return d.innerHTML; }
let searchTimer;

async function loadCustomers(search = '') {
  const url = '<?= BASE_URL ?>/Backend/api/admin_users.php' + (search ? `?search=${encodeURIComponent(search)}` : '');
  const res = await fetch(url, { credentials: 'same-origin' }).then(r => r.json());
  if (!res.success) { toast(res.message, 'error'); return; }
  setCsrfToken(res.data.csrf_token);
  const tbody = document.getElementById('customers-body');
  if (!res.data.users.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:32px; color:var(--ink-soft);">No customers found.</td></tr>';
    return;
  }
  tbody.innerHTML = res.data.users.map(u => `
    <tr>
      <td>${escapeHtml(u.name)}</td>
      <td>${escapeHtml(u.email)}</td>
      <td>${escapeHtml(u.phone || '—')}</td>
      <td class="mono">${u.booking_count}</td>
      <td class="mono" style="font-size:12px;">${new Date(u.created_at).toLocaleDateString('en-IN')}</td>
      <td><button class="btn btn-danger btn-sm" onclick="deleteCustomer(${u.id})">Remove</button></td>
    </tr>
  `).join('');
}

async function deleteCustomer(id) {
  if (!confirm('Remove this customer account? This cannot be undone.')) return;
  const res = await apiPostJson('/api/admin_users.php', { action: 'delete', id });
  if (res.success) { toast('Customer removed.', 'success'); loadCustomers(); } else toast(res.message, 'error');
}

document.getElementById('search-input').addEventListener('input', function() {
  clearTimeout(searchTimer);
  const val = this.value;
  searchTimer = setTimeout(() => loadCustomers(val), 300);
});

loadCustomers();
</script>
</body>
</html>
