<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (!isAdminLoggedIn()) { header('Location: /Admin/pages/login.php'); exit; }
$activeSection = 'rooms';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rooms — LuxuryStay Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
<style>
  .room-thumb { width: 46px; height: 46px; border-radius: 8px; object-fit: cover; background: var(--stone); }
  .filter-tabs { display: flex; gap: 8px; margin-bottom: var(--space-5); flex-wrap: wrap; }
  .filter-tab { padding: 8px 16px; border-radius: 999px; font-size: 13px; font-weight: 600; background: var(--white); cursor: pointer; border: 1.5px solid #E2DDD0; }
  .filter-tab.active { background: var(--pine); color: #fff; border-color: var(--pine); }
  .amenity-input-row { display: flex; gap: 8px; margin-bottom: 8px; }
  .amenity-input-row input { flex: 1; }
</style>
</head>
<body>
<div class="dash-shell">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>
  <main class="dash-main">
    <div class="dash-topbar">
      <div><h2 style="margin-bottom:4px;">Rooms</h2><p style="color:var(--ink-soft);">Manage all 50 rooms — pricing, capacity, images, and status.</p></div>
      <button class="btn btn-brass" onclick="openRoomModal()">+ Add Room</button>
    </div>

    <div class="filter-tabs" id="filter-tabs">
      <div class="filter-tab active" data-type="">All Types</div>
      <div class="filter-tab" data-type="Standard Room">Standard</div>
      <div class="filter-tab" data-type="Deluxe Room">Deluxe</div>
      <div class="filter-tab" data-type="Super Deluxe Room">Super Deluxe</div>
      <div class="filter-tab" data-type="Suite Room">Suite</div>
    </div>

    <div class="table-wrap">
      <table>
        <thead><tr><th></th><th>Room</th><th>Type</th><th>Price/Night</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="rooms-body"><tr><td colspan="7" style="text-align:center; padding:32px;">Loading…</td></tr></tbody>
      </table>
    </div>
  </main>
</div>

<!-- Add/Edit room modal -->
<div class="modal-overlay" id="room-modal">
  <div class="modal-box">
    <div class="modal-header"><h3 id="room-modal-title">Add Room</h3><button class="modal-close" onclick="closeRoomModal()">&times;</button></div>
    <div class="modal-body">
      <div id="room-form-msg" class="form-msg"></div>
      <form id="room-form">
        <input type="hidden" id="rm-id" value="0">
        <div class="form-row">
          <div class="form-group"><label>Room Number</label><input type="text" id="rm-number" placeholder="e.g. 601"></div>
          <div class="form-group"><label>Floor</label><input type="number" id="rm-floor" min="1" max="50" value="1"></div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Room Type</label>
            <select id="rm-type">
              <option>Standard Room</option><option>Deluxe Room</option><option>Super Deluxe Room</option><option>Suite Room</option>
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select id="rm-status"><option value="available">Available</option><option value="maintenance">Maintenance</option></select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Price per Night (₹)</label><input type="number" id="rm-price" min="1"></div>
          <div class="form-group"><label>Capacity (guests)</label><input type="number" id="rm-capacity" min="1" max="20"></div>
        </div>
        <div class="form-group"><label>Description</label><textarea id="rm-description" rows="3"></textarea></div>
        <div class="form-group">
          <label>Amenities</label>
          <div id="amenity-rows"></div>
          <button type="button" class="btn btn-ghost btn-sm" onclick="addAmenityRow()">+ Add Amenity</button>
        </div>
        <div class="form-group"><label>Cover Image <span style="font-weight:400; color:var(--ink-soft);">(JPG/PNG/WEBP, max 5MB)</span></label><input type="file" id="rm-cover" accept="image/jpeg,image/png,image/webp"></div>
        <div class="form-group"><label>Additional Gallery Images</label><input type="file" id="rm-gallery" accept="image/jpeg,image/png,image/webp" multiple></div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeRoomModal()">Cancel</button>
      <button class="btn btn-brass" id="room-save-btn" onclick="saveRoom()">Save Room</button>
    </div>
  </div>
</div>

<!-- Booking history modal -->
<div class="modal-overlay" id="history-modal">
  <div class="modal-box">
    <div class="modal-header"><h3>Room Booking History</h3><button class="modal-close" onclick="document.getElementById('history-modal').classList.remove('open')">&times;</button></div>
    <div class="modal-body" id="history-body"></div>
  </div>
</div>

<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
function escapeHtml(str) { const d = document.createElement('div'); d.textContent = str ?? ''; return d.innerHTML; }
let ROOMS = [];
let CURRENT_TYPE_FILTER = '';

async function loadRooms() {
  const res = await fetch('<?= BASE_URL ?>/Backend/api/admin_rooms.php', { credentials: 'same-origin' }).then(r => r.json());
  if (!res.success) { toast(res.message, 'error'); return; }
  setCsrfToken(res.data.csrf_token);
  ROOMS = res.data.rooms;
  renderRooms();
}

function renderRooms() {
  const tbody = document.getElementById('rooms-body');
  const filtered = CURRENT_TYPE_FILTER ? ROOMS.filter(r => r.room_type === CURRENT_TYPE_FILTER) : ROOMS;
  if (!filtered.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:32px; color:var(--ink-soft);">No rooms found.</td></tr>';
    return;
  }
  tbody.innerHTML = filtered.map(r => {
    const img = r.cover_image ? `${window.__BASE_URL__ || ""}/Frontend/images/rooms/${r.cover_image}` : 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=100&q=80';
    const statusBadge = r.status === 'maintenance'
      ? '<span class="badge badge-rejected">Maintenance</span>'
      : (r.is_active ? '<span class="badge badge-approved">Available</span>' : '<span class="badge badge-cancelled">Inactive</span>');
    return `
      <tr>
        <td><img class="room-thumb" src="${img}" onerror="this.src='https://images.unsplash.com/photo-1590490360182-c33d57733427?w=100&q=80'"></td>
        <td class="mono">${escapeHtml(r.room_number)} <span style="color:var(--ink-soft);">· Fl ${r.floor}</span></td>
        <td>${escapeHtml(r.room_type)}</td>
        <td class="mono">${formatMoney(r.price_per_night)}</td>
        <td>${r.capacity}</td>
        <td>${statusBadge}</td>
        <td>
          <button class="btn btn-ghost btn-sm" onclick="editRoom(${r.id})">Edit</button>
          <button class="btn btn-ghost btn-sm" onclick="showHistory(${r.id})">History</button>
          <button class="btn btn-outline btn-sm" onclick="toggleMaintenance(${r.id}, '${r.status}')">${r.status === 'maintenance' ? 'Set Available' : 'Maintenance'}</button>
          <button class="btn btn-danger btn-sm" onclick="deleteRoom(${r.id})">Remove</button>
        </td>
      </tr>
    `;
  }).join('');
}

document.querySelectorAll('.filter-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    CURRENT_TYPE_FILTER = tab.dataset.type;
    renderRooms();
  });
});

// ---------------- Amenity rows ----------------
function addAmenityRow(value = '') {
  const wrap = document.getElementById('amenity-rows');
  const row = document.createElement('div');
  row.className = 'amenity-input-row';
  row.innerHTML = `<input type="text" value="${escapeHtml(value)}" placeholder="e.g. Free WiFi"><button type="button" class="btn btn-ghost btn-sm" onclick="this.parentElement.remove()">✕</button>`;
  wrap.appendChild(row);
}
function getAmenities() {
  return Array.from(document.querySelectorAll('#amenity-rows input')).map(i => i.value.trim()).filter(Boolean);
}

// ---------------- Room modal ----------------
function openRoomModal() {
  document.getElementById('room-modal-title').textContent = 'Add Room';
  document.getElementById('rm-id').value = '0';
  document.getElementById('rm-number').value = '';
  document.getElementById('rm-floor').value = '1';
  document.getElementById('rm-type').value = 'Standard Room';
  document.getElementById('rm-status').value = 'available';
  document.getElementById('rm-price').value = '';
  document.getElementById('rm-capacity').value = '';
  document.getElementById('rm-description').value = '';
  document.getElementById('amenity-rows').innerHTML = '';
  document.getElementById('rm-cover').value = '';
  document.getElementById('rm-gallery').value = '';
  document.getElementById('room-form-msg').className = 'form-msg';
  document.getElementById('room-modal').classList.add('open');
}
function editRoom(id) {
  const r = ROOMS.find(x => x.id === id);
  if (!r) return;
  document.getElementById('room-modal-title').textContent = `Edit Room ${r.room_number}`;
  document.getElementById('rm-id').value = r.id;
  document.getElementById('rm-number').value = r.room_number;
  document.getElementById('rm-floor').value = r.floor;
  document.getElementById('rm-type').value = r.room_type;
  document.getElementById('rm-status').value = r.status;
  document.getElementById('rm-price').value = r.price_per_night;
  document.getElementById('rm-capacity').value = r.capacity;
  document.getElementById('rm-description').value = r.description || '';
  document.getElementById('amenity-rows').innerHTML = '';
  (r.amenities || []).forEach(a => addAmenityRow(a));
  document.getElementById('rm-cover').value = '';
  document.getElementById('rm-gallery').value = '';
  document.getElementById('room-form-msg').className = 'form-msg';
  document.getElementById('room-modal').classList.add('open');
}
function closeRoomModal() { document.getElementById('room-modal').classList.remove('open'); }

async function saveRoom() {
  const msg = document.getElementById('room-form-msg');
  const btn = document.getElementById('room-save-btn');
  const number = document.getElementById('rm-number').value.trim();
  const price = document.getElementById('rm-price').value;
  const capacity = document.getElementById('rm-capacity').value;

  if (!number) { msg.textContent = 'Room number is required.'; msg.className = 'form-msg error'; return; }
  if (!price || price <= 0) { msg.textContent = 'Please enter a valid price.'; msg.className = 'form-msg error'; return; }
  if (!capacity || capacity <= 0) { msg.textContent = 'Please enter a valid capacity.'; msg.className = 'form-msg error'; return; }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner spinner-dark"></span> Saving…';

  const fd = new FormData();
  fd.append('action', 'save');
  fd.append('id', document.getElementById('rm-id').value);
  fd.append('room_number', number);
  fd.append('floor', document.getElementById('rm-floor').value);
  fd.append('room_type', document.getElementById('rm-type').value);
  fd.append('status', document.getElementById('rm-status').value);
  fd.append('price_per_night', price);
  fd.append('capacity', capacity);
  fd.append('description', document.getElementById('rm-description').value);
  fd.append('amenities', JSON.stringify(getAmenities()));
  const cover = document.getElementById('rm-cover').files[0];
  if (cover) fd.append('cover_image', cover);
  const galleryFiles = document.getElementById('rm-gallery').files;
  for (let i = 0; i < galleryFiles.length; i++) fd.append('gallery_images[]', galleryFiles[i]);

  const res = await apiPostForm('/api/admin_rooms.php', fd);
  btn.disabled = false;
  btn.textContent = 'Save Room';

  if (!res.success) { msg.textContent = res.message; msg.className = 'form-msg error'; return; }
  toast(res.message, 'success');
  closeRoomModal();
  loadRooms();
}

async function toggleMaintenance(id, currentStatus) {
  const newStatus = currentStatus === 'maintenance' ? 'available' : 'maintenance';
  const fd = new FormData();
  fd.append('action', 'set_status');
  fd.append('id', id);
  fd.append('status', newStatus);
  const res = await apiPostForm('/api/admin_rooms.php', fd);
  if (res.success) { toast(res.message, 'success'); loadRooms(); }
  else toast(res.message, 'error');
}

async function deleteRoom(id) {
  if (!confirm('Remove this room from listings? Existing booking history will be preserved.')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  const res = await apiPostForm('/api/admin_rooms.php', fd);
  if (res.success) { toast(res.message, 'success'); loadRooms(); }
  else toast(res.message, 'error');
}

async function showHistory(id) {
  const res = await fetch(`${window.__BASE_URL__ || ""}/Backend/api/admin_rooms.php?action=history&room_id=${id}`, { credentials: 'same-origin' }).then(r => r.json());
  const body = document.getElementById('history-body');
  if (!res.success || !res.data.history.length) {
    body.innerHTML = '<div class="empty-state"><p>No bookings for this room yet.</p></div>';
  } else {
    body.innerHTML = `<div class="table-wrap"><table>
      <thead><tr><th>Booking</th><th>Guest</th><th>Dates</th><th>Status</th><th>Total</th></tr></thead>
      <tbody>${res.data.history.map(h => `
        <tr><td class="mono">${escapeHtml(h.booking_ref)}</td><td>${escapeHtml(h.guest_name)}</td>
        <td>${formatDate(h.check_in)} → ${formatDate(h.check_out)}</td><td>${h.status}</td><td class="mono">${formatMoney(h.total_price)}</td></tr>
      `).join('')}</tbody>
    </table></div>`;
  }
  document.getElementById('history-modal').classList.add('open');
}

loadRooms();
</script>
</body>
</html>
