<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (!isAdminLoggedIn()) { header('Location: /Admin/pages/login.php'); exit; }
$activeSection = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — LuxuryStay Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
<style>
  .chart-wrap { background: var(--white); border-radius: var(--radius-md); padding: var(--space-5); box-shadow: var(--shadow-sm); margin-bottom: var(--space-6); }
  .bar-chart { display: flex; align-items: flex-end; gap: 6px; height: 160px; }
  .bar-chart__col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
  .bar-chart__bar { width: 100%; background: var(--brass); border-radius: 4px 4px 0 0; min-height: 2px; transition: background 0.15s ease; }
  .bar-chart__bar:hover { background: var(--brass-deep); }
  .bar-chart__label { font-size: 10px; font-family: var(--font-mono); color: var(--ink-soft); margin-top: 6px; }
</style>
</head>
<body>
<div class="dash-shell">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>
  <main class="dash-main">
    <div class="dash-topbar">
      <div><h2 style="margin-bottom:4px;">Dashboard</h2><p style="color:var(--ink-soft);">Live overview of rooms, bookings, and revenue.</p></div>
      <button class="nav-toggle" style="display:none;" id="sidebar-toggle">☰</button>
    </div>

    <div class="stat-grid" id="stat-grid">
      <div class="skeleton" style="height:90px;"></div><div class="skeleton" style="height:90px;"></div>
      <div class="skeleton" style="height:90px;"></div><div class="skeleton" style="height:90px;"></div>
    </div>

    <div class="stat-grid">
      <div class="stat-card"><div class="stat-card__label">Pending Bookings</div><div class="stat-card__value" id="st-pending">–</div></div>
      <div class="stat-card"><div class="stat-card__label">Approved Bookings</div><div class="stat-card__value" id="st-approved">–</div></div>
      <div class="stat-card"><div class="stat-card__label">Rejected Bookings</div><div class="stat-card__value" id="st-rejected">–</div></div>
      <div class="stat-card"><div class="stat-card__label">Total Customers</div><div class="stat-card__value" id="st-customers">–</div></div>
    </div>

    <div class="chart-wrap">
      <h3>Revenue — Last 14 Days</h3>
      <div class="bar-chart" id="revenue-chart"></div>
    </div>

    <div class="table-wrap">
      <table>
        <thead><tr><th>Booking</th><th>Guest</th><th>Room</th><th>Dates</th><th>Status</th><th>Total</th></tr></thead>
        <tbody id="recent-bookings-body"><tr><td colspan="6" style="text-align:center; padding:32px;">Loading…</td></tr></tbody>
      </table>
    </div>
  </main>
</div>

<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
function statusBadge(status) {
  const labels = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected', checked_out: 'Checked Out', cancelled: 'Cancelled' };
  return `<span class="badge badge-${status}">${labels[status] || status}</span>`;
}
function escapeHtml(str) { const d = document.createElement('div'); d.textContent = str ?? ''; return d.innerHTML; }

async function loadDashboard() {
  const res = await fetch('<?= BASE_URL ?>/Backend/api/admin_dashboard_stats.php', { credentials: 'same-origin' }).then(r => r.json());
  if (!res.success) { toast(res.message, 'error'); return; }
  const d = res.data;

  document.getElementById('stat-grid').innerHTML = `
    <div class="stat-card"><div class="stat-card__label">Total Rooms</div><div class="stat-card__value">${d.total_rooms}</div></div>
    <div class="stat-card"><div class="stat-card__label">Available Rooms</div><div class="stat-card__value">${d.available_rooms}</div></div>
    <div class="stat-card"><div class="stat-card__label">Occupied (Today)</div><div class="stat-card__value">${d.occupied_rooms}</div></div>
    <div class="stat-card"><div class="stat-card__label">Revenue</div><div class="stat-card__value">${formatMoney(d.revenue)}</div></div>
  `;
  document.getElementById('st-pending').textContent = d.pending_bookings;
  document.getElementById('st-approved').textContent = d.approved_bookings;
  document.getElementById('st-rejected').textContent = d.rejected_bookings;
  document.getElementById('st-customers').textContent = d.total_customers;

  renderRevenueChart(d.revenue_trend);

  const tbody = document.getElementById('recent-bookings-body');
  if (!d.recent_bookings.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:32px; color:var(--ink-soft);">No bookings yet.</td></tr>';
    return;
  }
  tbody.innerHTML = d.recent_bookings.map(b => `
    <tr>
      <td class="mono">${escapeHtml(b.booking_ref)}</td>
      <td>${escapeHtml(b.guest_name)}</td>
      <td>${escapeHtml(b.room_type)} · ${escapeHtml(b.room_number)}</td>
      <td>${formatDate(b.check_in)} → ${formatDate(b.check_out)}</td>
      <td>${statusBadge(b.status)}</td>
      <td class="mono">${formatMoney(b.total_price)}</td>
    </tr>
  `).join('');
}

function renderRevenueChart(trend) {
  const chart = document.getElementById('revenue-chart');
  if (!trend.length) { chart.innerHTML = '<p style="color:var(--ink-soft); font-size:13px;">No revenue data yet for this period.</p>'; return; }
  const max = Math.max(...trend.map(t => Number(t.revenue)), 1);
  chart.innerHTML = trend.map(t => {
    const h = Math.max(4, (Number(t.revenue) / max) * 100);
    const label = new Date(t.d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
    return `<div class="bar-chart__col" title="${formatMoney(t.revenue)}"><div class="bar-chart__bar" style="height:${h}%;"></div><div class="bar-chart__label">${label}</div></div>`;
  }).join('');
}

loadDashboard();
</script>
</body>
</html>
