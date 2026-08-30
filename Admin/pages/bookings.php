<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (!isAdminLoggedIn()) { header('Location: /Admin/pages/login.php'); exit; }
$activeSection = 'bookings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bookings — LuxuryStay Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
<style>
  .filter-tabs { display: flex; gap: 8px; margin-bottom: var(--space-5); flex-wrap: wrap; }
  .filter-tab { padding: 8px 16px; border-radius: 999px; font-size: 13px; font-weight: 600; background: var(--white); cursor: pointer; border: 1.5px solid #E2DDD0; }
  .filter-tab.active { background: var(--pine); color: #fff; border-color: var(--pine); }
</style>
</head>
<body>
<div class="dash-shell">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>
  <main class="dash-main">
    <div class="dash-topbar">
      <div><h2 style="margin-bottom:4px;">Bookings</h2><p style="color:var(--ink-soft);">Review, approve, or reject guest reservations.</p></div>
      <button class="nav-toggle" style="display:none;" id="sidebar-toggle">☰</button>
    </div>

    <div class="filter-tabs" id="filter-tabs">
      <div class="filter-tab active" data-status="">All</div>
      <div class="filter-tab" data-status="pending">Pending</div>
      <div class="filter-tab" data-status="approved">Approved</div>
      <div class="filter-tab" data-status="rejected">Rejected</div>
      <div class="filter-tab" data-status="checked_out">Checked Out</div>
      <div class="filter-tab" data-status="cancelled">Cancelled</div>
    </div>

    <div class="table-wrap">
      <table>
        <thead><tr><th>Booking</th><th>Guest</th><th>Room</th><th>Dates</th><th>Guests</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="bookings-body"><tr><td colspan="8" style="text-align:center; padding:32px;">Loading…</td></tr></tbody>
      </table>
    </div>
  </main>
</div>

<!-- Reject modal -->
<div class="modal-overlay" id="reject-modal">
  <div class="modal-box" style="max-width:460px;">
    <div class="modal-header"><h3>Reason for Rejection</h3><button class="modal-close" onclick="closeRejectModal()">&times;</button></div>
    <div class="modal-body">
      <div id="reject-msg" class="form-msg"></div>
      <div class="form-group">
        <textarea id="reject-reason" rows="4" placeholder="e.g. Room maintenance is scheduled during your selected dates."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeRejectModal()">Cancel</button>
      <button class="btn btn-danger" onclick="confirmReject()">Reject Booking</button>
    </div>
  </div>
</div>

<!-- Detail modal -->
<div class="modal-overlay" id="detail-modal">
  <div class="modal-box">
    <div class="modal-header"><h3>Booking Details</h3><button class="modal-close" onclick="document.getElementById('detail-modal').classList.remove('open')">&times;</button></div>
    <div class="modal-body" id="detail-body"></div>
  </div>
</div>

<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
function escapeHtml(str) { const d = document.createElement('div'); d.textContent = str ?? ''; return d.innerHTML; }
function statusBadge(status) {
  const labels = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected', checked_out: 'Checked Out', cancelled: 'Cancelled' };
  return `<span class="badge badge-${status}">${labels[status] || status}</span>`;
}

let BOOKINGS = [];
let REGISTRY = {};
let PENDING_REJECT_ID = null;
let CURRENT_FILTER = '';

async function loadBookings() {
  const url = '<?= BASE_URL ?>/Backend/api/admin_bookings.php' + (CURRENT_FILTER ? `?status=${CURRENT_FILTER}` : '');
  const res = await fetch(url, { credentials: 'same-origin' }).then(r => r.json());
  if (!res.success) { toast(res.message, 'error'); return; }
  if (res.data.csrf_token) setCsrfToken(res.data.csrf_token);
  BOOKINGS = res.data.bookings;
  REGISTRY = {};
  BOOKINGS.forEach(b => REGISTRY[b.id] = b);
  renderBookings();
}

function renderBookings() {
  const tbody = document.getElementById('bookings-body');
  if (!BOOKINGS.length) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:32px; color:var(--ink-soft);">No bookings found.</td></tr>';
    return;
  }
  tbody.innerHTML = BOOKINGS.map(b => `
    <tr>
      <td class="mono">${escapeHtml(b.booking_ref)}</td>
      <td>${escapeHtml(b.guest_name)}</td>
      <td>${escapeHtml(b.room_type)} · ${escapeHtml(b.room_number)}</td>
      <td>${formatDate(b.check_in)} → ${formatDate(b.check_out)}</td>
      <td>${Number(b.adults) + Number(b.children)}</td>
      <td class="mono">${formatMoney(b.total_price)}</td>
      <td>${statusBadge(b.status)}</td>
      <td>${renderActions(b)}</td>
    </tr>
  `).join('');
}

function renderActions(b) {
  let html = `<button class="btn btn-ghost btn-sm" onclick="showDetail(${b.id})">View</button> `;
  if (b.status === 'pending') {
    html += `<button class="btn btn-brass btn-sm" onclick="approveBooking(${b.id})">Approve</button> `;
    html += `<button class="btn btn-danger btn-sm" onclick="openRejectModal(${b.id})">Reject</button> `;
  }
  if (b.status === 'approved') {
    html += `<button class="btn btn-outline btn-sm" onclick="checkoutBooking(${b.id})">Check Out</button> `;
  }
  if (b.status === 'pending' || b.status === 'approved') {
    html += `<button class="btn btn-ghost btn-sm" onclick="cancelBooking(${b.id})">Cancel</button>`;
  }
  return html;
}

function showDetail(id) {
  const b = REGISTRY[id];
  const mealRows = (b.meals || []).map(m => `<div class="price-row"><span>${m.meal_type[0].toUpperCase()+m.meal_type.slice(1)}</span><span>${formatMoney(m.subtotal)}</span></div>`).join('');
  let rejectionHtml = '';
  if (b.status === 'rejected' && b.rejection_reason) {
    rejectionHtml = `<div class="form-msg error" style="display:block; margin-top:16px;"><strong>Rejection Reason:</strong><br>${escapeHtml(b.rejection_reason)}</div>`;
  }
  document.getElementById('detail-body').innerHTML = `
    <div class="price-breakdown">
      <div class="price-row"><span>Booking ID</span><span>${escapeHtml(b.booking_ref)}</span></div>
      <div class="price-row"><span>Guest</span><span>${escapeHtml(b.guest_name)}</span></div>
      <div class="price-row"><span>Email</span><span>${escapeHtml(b.guest_email)}</span></div>
      <div class="price-row"><span>Phone</span><span>${escapeHtml(b.guest_phone)}</span></div>
      <div class="price-row"><span>Room</span><span>${escapeHtml(b.room_type)} · ${escapeHtml(b.room_number)}</span></div>
      <div class="price-row"><span>Check-in</span><span>${formatDate(b.check_in)}</span></div>
      <div class="price-row"><span>Check-out</span><span>${formatDate(b.check_out)}</span></div>
      <div class="price-row"><span>Guests</span><span>${b.adults} Adults, ${b.children} Children</span></div>
      <div class="price-row"><span>Room Subtotal</span><span>${formatMoney(b.room_price)}</span></div>
      ${mealRows}
      <div class="price-row total"><span>Grand Total</span><span>${formatMoney(b.total_price)}</span></div>
    </div>
    <div style="margin-top:16px;">${statusBadge(b.status)}</div>
    ${rejectionHtml}
  `;
  document.getElementById('detail-modal').classList.add('open');
}

async function approveBooking(id) {
  if (!confirm('Approve this booking?')) return;
  const res = await apiPostJson('/api/admin_bookings.php', { action: 'approve', id });
  if (res.success) { toast('Booking approved.', 'success'); loadBookings(); }
  else toast(res.message, 'error');
}

function openRejectModal(id) {
  PENDING_REJECT_ID = id;
  document.getElementById('reject-reason').value = '';
  document.getElementById('reject-msg').className = 'form-msg';
  document.getElementById('reject-modal').classList.add('open');
}
function closeRejectModal() {
  document.getElementById('reject-modal').classList.remove('open');
  PENDING_REJECT_ID = null;
}
async function confirmReject() {
  const reason = document.getElementById('reject-reason').value.trim();
  const msg = document.getElementById('reject-msg');
  if (!reason || reason.length < 5) {
    msg.textContent = 'Please enter a rejection reason (minimum 5 characters).';
    msg.className = 'form-msg error';
    return;
  }
  const res = await apiPostJson('/api/admin_bookings.php', { action: 'reject', id: PENDING_REJECT_ID, rejection_reason: reason });
  if (res.success) {
    toast('Booking rejected and guest notified.', 'success');
    closeRejectModal();
    loadBookings();
  } else {
    msg.textContent = res.message;
    msg.className = 'form-msg error';
  }
}

async function checkoutBooking(id) {
  if (!confirm('Mark this guest as checked out?')) return;
  const res = await apiPostJson('/api/admin_bookings.php', { action: 'checkout', id });
  if (res.success) { toast('Guest checked out.', 'success'); loadBookings(); }
  else toast(res.message, 'error');
}

async function cancelBooking(id) {
  if (!confirm('Cancel this booking on behalf of the guest?')) return;
  const res = await apiPostJson('/api/admin_bookings.php', { action: 'cancel', id });
  if (res.success) { toast('Booking cancelled.', 'success'); loadBookings(); }
  else toast(res.message, 'error');
}

document.querySelectorAll('.filter-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    CURRENT_FILTER = tab.dataset.status;
    loadBookings();
  });
});

loadBookings();
</script>
</body>
</html>
