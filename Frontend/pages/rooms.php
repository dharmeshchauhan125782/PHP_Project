<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Rooms — LuxuryStay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
<style>
  .rooms-layout { display: grid; grid-template-columns: 260px 1fr; gap: var(--space-6); align-items: start; }
  .filters-panel { position: sticky; top: 90px; background: var(--white); border-radius: var(--radius-md); padding: var(--space-5); box-shadow: var(--shadow-sm); }
  .filters-panel h4 { font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 12px; }
  .type-chip { display: block; padding: 9px 12px; border-radius: var(--radius-sm); font-size: 13.5px; margin-bottom: 4px; cursor: pointer; }
  .type-chip:hover { background: var(--stone); }
  .type-chip.active { background: var(--brass-pale); font-weight: 600; }
  @media (max-width: 900px) { .rooms-layout { grid-template-columns: 1fr; } .filters-panel { position: static; } }
</style>
</head>
<body>
<?php include __DIR__ . '/../components/header.php'; ?>

<section class="section-tight" style="padding-top:40px;">
    <div class="container">
        <div class="eyebrow">All Accommodations</div>
        <h1 style="font-size:2.4rem;">50 Rooms, Five Floors</h1>
        <p style="max-width:560px;">Filter by type, price, or capacity — only rooms genuinely free for your dates are shown once you search.</p>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="rooms-layout">
            <aside class="filters-panel">
                <h4>Stay Dates</h4>
                <div class="form-group"><label>Check-in</label><input type="date" id="f-checkin"></div>
                <div class="form-group"><label>Check-out</label><input type="date" id="f-checkout"></div>

                <h4 style="margin-top:20px;">Guests</h4>
                <div class="form-group"><input type="number" id="f-guests" min="1" max="20" value="1"></div>

                <h4 style="margin-top:20px;">Room Type</h4>
                <div id="f-type-chips">
                    <div class="type-chip active" data-type="">All Types</div>
                    <div class="type-chip" data-type="Standard Room">Standard Room</div>
                    <div class="type-chip" data-type="Deluxe Room">Deluxe Room</div>
                    <div class="type-chip" data-type="Super Deluxe Room">Super Deluxe Room</div>
                    <div class="type-chip" data-type="Suite Room">Suite Room</div>
                </div>

                <h4 style="margin-top:20px;">Price Range (per night)</h4>
                <div class="form-row">
                    <div class="form-group"><input type="number" id="f-min-price" placeholder="Min"></div>
                    <div class="form-group"><input type="number" id="f-max-price" placeholder="Max"></div>
                </div>

                <h4 style="margin-top:20px;">Sort By</h4>
                <div class="form-group">
                    <select id="f-sort">
                        <option value="recommended">Recommended</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                    </select>
                </div>

                <button class="btn btn-brass btn-block" onclick="applyFilters()">Search Rooms</button>
            </aside>

            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <span id="results-count" class="mono" style="font-size:13px; color:var(--ink-soft);">Loading…</span>
                </div>
                <div class="grid-3" id="rooms-grid">
                    <div class="skeleton" style="height:340px;"></div>
                    <div class="skeleton" style="height:340px;"></div>
                    <div class="skeleton" style="height:340px;"></div>
                    <div class="skeleton" style="height:340px;"></div>
                    <div class="skeleton" style="height:340px;"></div>
                    <div class="skeleton" style="height:340px;"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../components/footer.php'; ?>
<?php include __DIR__ . '/../components/booking-modal.php'; ?>

<script>
window.__IS_LOGGED_IN__ = <?= $isLoggedIn ? 'true' : 'false' ?>;
let CURRENT_ROOMS = [];
const ROOM_REGISTRY = {};

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

function roomCardHtml(room) {
  ROOM_REGISTRY[room.id] = room;
  const img = room.cover_image ? `${window.__BASE_URL__ || ""}/Frontend/images/rooms/${room.cover_image}` : 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=600&q=80';
  return `
    <div class="room-card reveal in">
      <div class="room-card__media">
        <img src="${img}" alt="${escapeHtml(room.room_type)}" onerror="this.src='https://images.unsplash.com/photo-1590490360182-c33d57733427?w=600&q=80'">
        <div class="room-card__type">${escapeHtml(room.room_type)}</div>
        <div class="room-card__badge key-tag"><span class="key-tag__ring"></span>${escapeHtml(room.room_number)}</div>
      </div>
      <div class="room-card__body">
        <h3 style="margin-bottom:4px;">${escapeHtml(room.room_type)}</h3>
        <div class="room-card__meta">
          <span>👤 Up to ${room.capacity}</span>
          <span>🛏️ Floor ${room.floor}</span>
        </div>
        <div class="room-card__footer">
          <div class="room-card__price">${formatMoney(room.price_per_night)}<small> /night</small></div>
          <div style="display:flex; gap:8px;">
            <a href="<?= BASE_URL ?>/Frontend/pages/room-details.php?id=${room.id}" class="btn btn-ghost btn-sm">Details</a>
            <button class="btn btn-brass btn-sm" onclick="openBookingModal(ROOM_REGISTRY[${room.id}])">Book Now</button>
          </div>
        </div>
      </div>
    </div>
  `;
}

function readParamsFromURL() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('check_in')) document.getElementById('f-checkin').value = params.get('check_in');
  if (params.get('check_out')) document.getElementById('f-checkout').value = params.get('check_out');
  if (params.get('guests')) document.getElementById('f-guests').value = params.get('guests');
  if (params.get('room_type')) {
    document.querySelectorAll('.type-chip').forEach(c => c.classList.toggle('active', c.dataset.type === params.get('room_type')));
  }
}

document.querySelectorAll('.type-chip').forEach(chip => {
  chip.addEventListener('click', () => {
    document.querySelectorAll('.type-chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
  });
});

async function applyFilters() {
  const grid = document.getElementById('rooms-grid');
  grid.innerHTML = '<div class="skeleton" style="height:340px;"></div>'.repeat(6);

  const params = new URLSearchParams();
  const ci = document.getElementById('f-checkin').value;
  const co = document.getElementById('f-checkout').value;
  if (ci && co) { params.set('check_in', ci); params.set('check_out', co); }
  params.set('guests', document.getElementById('f-guests').value || 1);
  const activeType = document.querySelector('.type-chip.active').dataset.type;
  if (activeType) params.set('room_type', activeType);
  const minP = document.getElementById('f-min-price').value;
  const maxP = document.getElementById('f-max-price').value;
  if (minP) params.set('min_price', minP);
  if (maxP) params.set('max_price', maxP);
  params.set('sort', document.getElementById('f-sort').value);

  const res = await apiGet('/api/rooms_search.php?' + params.toString());
  if (!res.success) {
    document.getElementById('results-count').textContent = res.message;
    grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><div class="empty-state__icon">⚠️</div><p>${res.message}</p></div>`;
    return;
  }
  CURRENT_ROOMS = res.data.rooms;
  document.getElementById('results-count').textContent = `${res.data.count} room${res.data.count === 1 ? '' : 's'} found`;
  if (!res.data.rooms.length) {
    grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><div class="empty-state__icon">🔍</div><p>No rooms match your filters. Try adjusting your dates or guest count.</p></div>`;
    return;
  }
  grid.innerHTML = res.data.rooms.map(roomCardHtml).join('');
}

readParamsFromURL();
applyFilters();
</script>
</body>
</html>
