<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Room Details — LuxuryStay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
<style>
  .rd-gallery { display: grid; grid-template-columns: 2fr 1fr; gap: 10px; border-radius: var(--radius-lg); overflow: hidden; margin-bottom: var(--space-6); }
  .rd-gallery img { width: 100%; height: 100%; object-fit: cover; }
  .rd-gallery-main { aspect-ratio: 4/3; }
  .rd-gallery-side { display: grid; grid-template-rows: 1fr 1fr; gap: 10px; }
  .rd-layout { display: grid; grid-template-columns: 1.6fr 1fr; gap: var(--space-7); align-items: start; }
  .rd-sticky-card { position: sticky; top: 90px; }
  .amenity-pill { display:inline-flex; align-items:center; gap:6px; background:var(--stone); padding:8px 14px; border-radius:999px; font-size:13px; margin:0 6px 6px 0; }
  @media (max-width: 900px) { .rd-layout { grid-template-columns: 1fr; } .rd-gallery { grid-template-columns: 1fr; } .rd-gallery-side { grid-template-rows: 1fr 1fr; grid-auto-flow: column; grid-template-columns: 1fr 1fr; } }
</style>
</head>
<body>
<?php include __DIR__ . '/../components/header.php'; ?>

<section class="section-tight" style="padding-top:32px;">
    <div class="container" id="room-content">
        <div class="skeleton" style="height:420px; border-radius:20px; margin-bottom:32px;"></div>
        <div class="skeleton" style="height:200px; border-radius:12px;"></div>
    </div>
</section>

<?php include __DIR__ . '/../components/footer.php'; ?>
<?php include __DIR__ . '/../components/booking-modal.php'; ?>

<script>
window.__IS_LOGGED_IN__ = <?= $isLoggedIn ? 'true' : 'false' ?>;
let CURRENT_ROOM = null;

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

const roomId = new URLSearchParams(window.location.search).get('id');

async function loadRoom() {
  if (!roomId) { renderNotFound(); return; }
  const res = await apiGet(`/api/room_details.php?id=${encodeURIComponent(roomId)}`);
  if (!res.success) { renderNotFound(); return; }
  renderRoom(res.data.room);
}

function renderNotFound() {
  document.getElementById('room-content').innerHTML = `<div class="empty-state"><div class="empty-state__icon">🔍</div><h3>Room not found</h3><p>It may have been removed or renumbered.</p><a href="<?= BASE_URL ?>/Frontend/pages/rooms.php" class="btn btn-outline">Back to All Rooms</a></div>`;
}

function renderRoom(room) {
  CURRENT_ROOM = room;
  const cover = room.cover_image ? `${window.__BASE_URL__ || ""}/Frontend/images/rooms/${room.cover_image}` : 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=1000&q=80';
  const gallery = (room.images && room.images.length) ? room.images.slice(0, 2) : [];
  while (gallery.length < 2) gallery.push(null);

  const amenities = (room.amenities || []).map(a => `<span class="amenity-pill">✓ ${escapeHtml(a)}</span>`).join('');

  document.getElementById('room-content').innerHTML = `
    <div class="rd-gallery">
      <div class="rd-gallery-main"><img src="${cover}" onerror="this.src='https://images.unsplash.com/photo-1590490360182-c33d57733427?w=1000&q=80'"></div>
      <div class="rd-gallery-side">
        <img src="${gallery[0] ? '<?= BASE_URL ?>/Frontend/images/rooms/' + gallery[0] : cover}" onerror="this.src='${cover}'">
        <img src="${gallery[1] ? '<?= BASE_URL ?>/Frontend/images/rooms/' + gallery[1] : cover}" onerror="this.src='${cover}'">
      </div>
    </div>

    <div class="rd-layout">
      <div>
        <div class="key-tag" style="margin-bottom:16px;"><span class="key-tag__ring"></span>Room ${escapeHtml(room.room_number)} · Floor ${room.floor}</div>
        <h1 style="font-size:2.2rem;">${escapeHtml(room.room_type)}</h1>
        <div class="room-card__meta" style="margin-bottom:20px;">
          <span>👤 Up to ${room.capacity} guests</span>
          <span>💰 ${formatMoney(room.price_per_night)} / night</span>
        </div>
        <p style="font-size:15.5px; line-height:1.7;">${escapeHtml(room.description || '')}</p>

        <h3 style="margin-top:32px;">Amenities</h3>
        <div>${amenities || '<p>Standard hotel amenities included.</p>'}</div>
      </div>

      <div class="rd-sticky-card">
        <div class="card" style="padding:28px;">
          <div class="room-card__price" style="font-size:28px; margin-bottom:4px;">${formatMoney(room.price_per_night)}<small> /night</small></div>
          <p style="font-size:13px; margin-bottom:20px;">Taxes and meal charges calculated at booking.</p>
          <button class="btn btn-brass btn-block" onclick="openBookingModal(CURRENT_ROOM)">Book This Room</button>
          <a href="<?= BASE_URL ?>/Frontend/pages/rooms.php" class="btn btn-ghost btn-block" style="margin-top:8px;">&larr; Back to All Rooms</a>
        </div>
      </div>
    </div>
  `;
}

loadRoom();
</script>
</body>
</html>
