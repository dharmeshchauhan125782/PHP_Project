<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (!isUserLoggedIn()) { header('Location: /Frontend/pages/login.php'); exit; }
$userName = $_SESSION['user_name'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard — LuxuryStay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
<style>
  .dash-section { display: none; }
  .dash-section.active { display: block; }
  .notif-item { padding: 14px 16px; border-radius: var(--radius-sm); background: var(--white); margin-bottom: 8px; border-left: 3px solid var(--stone); }
  .notif-item.unread { border-left-color: var(--brass); background: #FBF7EF; }
  .notif-item__title { font-weight: 600; font-size: 13.5px; margin-bottom: 2px; }
  .notif-item__meta { font-size: 11.5px; color: var(--ink-soft); font-family: var(--font-mono); }
  .announce-card { padding: 20px; margin-bottom: 12px; border-left: 3px solid var(--brass); }
  .announce-type { font-family: var(--font-mono); font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brass-deep); }
</style>
</head>
<body>

<div class="dash-shell">
  <aside class="dash-sidebar" id="dash-sidebar">
    <div class="brand">
      <div class="brand-mark" style="background:var(--brass); color:var(--pine-deep);">LS</div>
      <div class="brand-text">LuxuryStay<small>GUEST PORTAL</small></div>
    </div>
    <a class="dash-link active" data-section="overview" href="#overview">🏠 Overview</a>
    <a class="dash-link" data-section="bookings" href="#bookings">🗝️ My Bookings</a>
    <a class="dash-link" data-section="notifications" href="#notifications">🔔 Notifications</a>
    <a class="dash-link" data-section="updates" href="#updates">📣 Hotel Updates</a>
    <a class="dash-link" data-section="profile" href="#profile">👤 Profile</a>
    <div style="margin-top:auto; padding-top:20px; border-top:1px solid rgba(255,255,255,0.1);">
      <a class="dash-link" href="<?= BASE_URL ?>/Frontend/pages/rooms.php">🛏️ Browse Rooms</a>
      <a class="dash-link" href="#" onclick="doLogout('<?= BASE_URL ?>/Frontend/pages/index.php'); return false;">🚪 Logout</a>
    </div>
  </aside>

  <main class="dash-main">
    <div class="dash-topbar">
      <div>
        <h2 style="margin-bottom:4px;">Welcome, <?= htmlspecialchars($userName) ?></h2>
        <p style="color:var(--ink-soft);">Here's what's happening with your stay.</p>
      </div>
      <button class="nav-toggle" style="display:none;" id="sidebar-toggle" onclick="document.getElementById('dash-sidebar').classList.toggle('open')">☰</button>
    </div>

    <!-- Overview -->
    <section class="dash-section active" id="section-overview">
      <div class="stat-grid" id="overview-stats">
        <div class="stat-card"><div class="stat-card__label">Total Bookings</div><div class="stat-card__value" id="ov-total">–</div></div>
        <div class="stat-card"><div class="stat-card__label">Pending</div><div class="stat-card__value" id="ov-pending">–</div></div>
        <div class="stat-card"><div class="stat-card__label">Approved</div><div class="stat-card__value" id="ov-approved">–</div></div>
        <div class="stat-card"><div class="stat-card__label">Rejected</div><div class="stat-card__value" id="ov-rejected">–</div></div>
      </div>
      <h3>Upcoming / Latest Booking</h3>
      <div id="upcoming-booking"><div class="skeleton" style="height:110px;"></div></div>
    </section>

    <!-- Bookings -->
    <section class="dash-section" id="section-bookings">
      <h3>My Bookings</h3>
      <div id="bookings-list"><div class="skeleton" style="height:110px; margin-bottom:12px;"></div></div>
    </section>

    <!-- Notifications -->
    <section class="dash-section" id="section-notifications">
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3>Notifications</h3>
        <button class="btn btn-ghost btn-sm" onclick="markAllNotifsRead()">Mark all as read</button>
      </div>
      <div id="notifications-list"><div class="skeleton" style="height:60px; margin-bottom:8px;"></div></div>
    </section>

    <!-- Hotel Updates -->
    <section class="dash-section" id="section-updates">
      <h3>Hotel Updates</h3>
      <div id="updates-list"><div class="skeleton" style="height:80px; margin-bottom:12px;"></div></div>
    </section>

    <!-- Profile -->
    <section class="dash-section" id="section-profile">
      <h3>My Profile</h3>
      <div class="card" style="padding:28px; max-width:480px;">
        <div id="profile-msg" class="form-msg"></div>
        <div class="form-group"><label>Full Name</label><input type="text" id="pf-name"></div>
        <div class="form-group"><label>Email</label><input type="email" id="pf-email" disabled></div>
        <div class="form-group"><label>Phone</label><input type="text" id="pf-phone"></div>
        <button class="btn btn-brass" onclick="saveProfile()">Save Changes</button>
      </div>

      <h3 style="margin-top:32px;">Change Password</h3>
      <div class="card" style="padding:28px; max-width:480px;">
        <div id="pw-msg" class="form-msg"></div>
        <div class="form-group"><label>Current Password</label><input type="password" id="pf-current-pw"></div>
        <div class="form-group"><label>New Password</label><input type="password" id="pf-new-pw"></div>
        <button class="btn btn-outline" onclick="changePassword()">Update Password</button>
      </div>
    </section>
  </main>
</div>

<!-- Booking detail modal -->
<div class="modal-overlay" id="booking-detail-modal">
  <div class="modal-box">
    <div class="modal-header"><h3>Booking Details</h3><button class="modal-close" onclick="document.getElementById('booking-detail-modal').classList.remove('open')">&times;</button></div>
    <div class="modal-body" id="booking-detail-body"></div>
  </div>
</div>

<script>window.__BASE_URL__ = <?= json_encode(BASE_URL) ?>; window.__CSRF_TOKEN__ = <?= json_encode(csrfToken()) ?>;</script>
<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
window.__IS_LOGGED_IN__ = true;
let ALL_BOOKINGS = [];
const BOOKING_REGISTRY = {};

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

// ---------------- Sidebar nav ----------------
document.querySelectorAll('.dash-link[data-section]').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    document.querySelectorAll('.dash-link[data-section]').forEach(l => l.classList.remove('active'));
    link.classList.add('active');
    document.querySelectorAll('.dash-section').forEach(s => s.classList.remove('active'));
    document.getElementById('section-' + link.dataset.section).classList.add('active');
  });
});
if (window.location.hash) {
  const target = window.location.hash.replace('#', '');
  const link = document.querySelector(`.dash-link[data-section="${target}"]`);
  if (link) link.click();
}

// ---------------- Bookings ----------------
function statusBadge(status) {
  const labels = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected', checked_out: 'Checked Out', cancelled: 'Cancelled' };
  return `<span class="badge badge-${status}">${labels[status] || status}</span>`;
}

function bookingCardHtml(b) {
  BOOKING_REGISTRY[b.id] = b;
  const img = b.cover_image ? `${window.__BASE_URL__ || ""}/Frontend/images/rooms/${b.cover_image}` : 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=200&q=80';
  return `
    <div class="booking-card">
      <img class="booking-card__thumb" src="${img}" onerror="this.src='https://images.unsplash.com/photo-1590490360182-c33d57733427?w=200&q=80'">
      <div>
        <div class="booking-card__ref">${escapeHtml(b.booking_ref)}</div>
        <h3 style="margin:2px 0 6px; font-size:16px;">${escapeHtml(b.room_type)} · Room ${escapeHtml(b.room_number)}</h3>
        <div style="font-size:13px; color:var(--ink-soft);">${formatDate(b.check_in)} → ${formatDate(b.check_out)} · ${b.adults} Adults, ${b.children} Children</div>
        <div style="margin-top:6px;">${statusBadge(b.status)}</div>
      </div>
      <div style="text-align:right;">
        <div class="room-card__price" style="font-size:17px;">${formatMoney(b.total_price)}</div>
        <button class="btn btn-ghost btn-sm" onclick="showBookingDetail(BOOKING_REGISTRY[${b.id}])">View</button>
        ${(b.status === 'pending' || b.status === 'approved') ? `<button class="btn btn-danger btn-sm" onclick="cancelBooking(${b.id})">Cancel</button>` : ''}
      </div>
    </div>
  `;
}

function showBookingDetail(b) {
  const mealRows = (b.meals || []).map(m => `<div class="price-row"><span>${m.meal_type[0].toUpperCase()+m.meal_type.slice(1)}</span><span>${formatMoney(m.subtotal)}</span></div>`).join('');
  let rejectionHtml = '';
  if (b.status === 'rejected' && b.rejection_reason) {
    rejectionHtml = `<div class="form-msg error" style="display:block; margin-top:16px;"><strong>Rejection Reason:</strong><br>${escapeHtml(b.rejection_reason)}</div>`;
  }
  document.getElementById('booking-detail-body').innerHTML = `
    <div class="price-breakdown">
      <div class="price-row"><span>Booking ID</span><span>${escapeHtml(b.booking_ref)}</span></div>
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
  document.getElementById('booking-detail-modal').classList.add('open');
}

async function cancelBooking(id) {
  if (!confirm('Cancel this booking? This cannot be undone.')) return;
  const res = await apiPostJson('/api/booking_cancel.php', { booking_id: id });
  if (res.success) { toast('Booking cancelled.', 'success'); loadBookings(); loadOverview(); }
  else toast(res.message, 'error');
}

async function loadBookings() {
  const res = await apiGet('/api/booking_list.php');
  if (!res.success) return;
  ALL_BOOKINGS = res.data.bookings;
  const list = document.getElementById('bookings-list');
  if (!ALL_BOOKINGS.length) {
    list.innerHTML = `<div class="empty-state"><div class="empty-state__icon">🗝️</div><p>No bookings yet.</p><a href="<?= BASE_URL ?>/Frontend/pages/rooms.php" class="btn btn-brass">Browse Rooms</a></div>`;
    return;
  }
  list.innerHTML = ALL_BOOKINGS.map(bookingCardHtml).join('');
}

async function loadOverview() {
  const res = await apiGet('/api/booking_list.php');
  if (!res.success) return;
  const bookings = res.data.bookings;
  document.getElementById('ov-total').textContent = bookings.length;
  document.getElementById('ov-pending').textContent = bookings.filter(b => b.status === 'pending').length;
  document.getElementById('ov-approved').textContent = bookings.filter(b => b.status === 'approved').length;
  document.getElementById('ov-rejected').textContent = bookings.filter(b => b.status === 'rejected').length;

  const upcoming = bookings.find(b => b.status === 'approved' || b.status === 'pending');
  document.getElementById('upcoming-booking').innerHTML = upcoming
    ? bookingCardHtml(upcoming)
    : '<div class="empty-state"><div class="empty-state__icon">🏨</div><p>No upcoming bookings. Ready for your next stay?</p><a href="<?= BASE_URL ?>/Frontend/pages/rooms.php" class="btn btn-brass">Browse Rooms</a></div>';
}

// ---------------- Notifications ----------------
async function loadNotifications() {
  const res = await apiGet('/api/notifications_list.php');
  if (!res.success) return;
  const list = document.getElementById('notifications-list');
  if (!res.data.notifications.length) {
    list.innerHTML = `<div class="empty-state"><div class="empty-state__icon">🔔</div><p>No notifications yet.</p></div>`;
    return;
  }
  list.innerHTML = res.data.notifications.map(n => `
    <div class="notif-item ${n.is_read ? '' : 'unread'}">
      <div class="notif-item__title">${escapeHtml(n.title)}</div>
      <div style="font-size:13px; margin:4px 0; white-space:pre-line;">${escapeHtml(n.message)}</div>
      <div class="notif-item__meta">${new Date(n.created_at).toLocaleString('en-IN')}</div>
    </div>
  `).join('');

  const dot = document.getElementById('notif-dot');
  if (dot) dot.style.display = res.data.unread_count > 0 ? 'block' : 'none';
}

async function markAllNotifsRead() {
  await apiPostJson('/api/notifications_list.php', {});
  loadNotifications();
}

// ---------------- Hotel Updates ----------------
async function loadUpdates() {
  const res = await apiGet('/api/announcements_list.php');
  const list = document.getElementById('updates-list');
  if (!res.success || !res.data.announcements.length) {
    list.innerHTML = `<div class="empty-state"><div class="empty-state__icon">📣</div><p>No announcements right now.</p></div>`;
    return;
  }
  list.innerHTML = res.data.announcements.map(a => `
    <div class="card announce-card">
      <div class="announce-type">${escapeHtml(a.type)}</div>
      <h3 style="margin:6px 0;">${escapeHtml(a.title)}</h3>
      <p style="white-space:pre-line;">${escapeHtml(a.message)}</p>
      <div class="notif-item__meta">By ${escapeHtml(a.published_by)} · ${new Date(a.created_at).toLocaleDateString('en-IN')}</div>
    </div>
  `).join('');
}

// ---------------- Profile ----------------
async function loadProfile() {
  const res = await apiGet('/api/profile.php');
  if (!res.success) return;
  const u = res.data.user;
  document.getElementById('pf-name').value = u.name;
  document.getElementById('pf-email').value = u.email;
  document.getElementById('pf-phone').value = u.phone || '';
}

async function saveProfile() {
  const msg = document.getElementById('profile-msg');
  const res = await apiPostJson('/api/profile.php', {
    action: 'update_profile',
    name: document.getElementById('pf-name').value.trim(),
    phone: document.getElementById('pf-phone').value.trim(),
  });
  msg.textContent = res.message;
  msg.className = 'form-msg ' + (res.success ? 'success' : 'error');
  if (res.success) toast('Profile updated.', 'success');
}

async function changePassword() {
  const msg = document.getElementById('pw-msg');
  const res = await apiPostJson('/api/profile.php', {
    action: 'change_password',
    current_password: document.getElementById('pf-current-pw').value,
    new_password: document.getElementById('pf-new-pw').value,
  });
  msg.textContent = res.message;
  msg.className = 'form-msg ' + (res.success ? 'success' : 'error');
  if (res.success) {
    document.getElementById('pf-current-pw').value = '';
    document.getElementById('pf-new-pw').value = '';
    toast('Password updated.', 'success');
  }
}

// Prime CSRF token
apiGet('/api/profile.php').then(res => { if (res.data && res.data.csrf_token) setCsrfToken(res.data.csrf_token); });

loadOverview();
loadBookings();
loadNotifications();
loadUpdates();
loadProfile();
</script>
</body>
</html>
