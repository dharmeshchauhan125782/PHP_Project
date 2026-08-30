<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
$heroHeader = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LuxuryStay — A Grand Hotel, Reimagined</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../components/header.php'; ?>

<section class="hero" id="hero" style="background-image: linear-gradient(180deg, rgba(10,29,23,0.5) 0%, rgba(10,29,23,0.85) 100%), url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1600&q=80');">
    <div class="container">
        <div class="hero-content">
            <div class="eyebrow">Est. LuxuryStay — 50 Rooms, 5 Floors</div>
            <h1>Every room has a key.<br>Every key, a story.</h1>
            <p class="lede">A heritage grand hotel on the Marina Promenade — where brass fittings, quiet service, and a room for every kind of traveller await.</p>
            <div class="hero-actions">
                <a href="<?= BASE_URL ?>/Frontend/pages/rooms.php" class="btn btn-brass">Browse All Rooms</a>
                <a href="#featured" class="btn btn-outline-light">See Featured Suites</a>
            </div>
        </div>

        <form class="hero-search" id="hero-search-form" onsubmit="event.preventDefault(); submitHeroSearch();">
            <div class="form-group">
                <label>Check-in</label>
                <input type="date" id="hs-checkin">
            </div>
            <div class="form-group">
                <label>Check-out</label>
                <input type="date" id="hs-checkout">
            </div>
            <div class="form-group">
                <label>Guests</label>
                <select id="hs-guests">
                    <option value="1">1 Guest</option>
                    <option value="2" selected>2 Guests</option>
                    <option value="3">3 Guests</option>
                    <option value="4">4 Guests</option>
                    <option value="6">6+ Guests</option>
                </select>
            </div>
            <div class="form-group">
                <label>Room Type</label>
                <select id="hs-type">
                    <option value="">Any Type</option>
                    <option>Standard Room</option>
                    <option>Deluxe Room</option>
                    <option>Super Deluxe Room</option>
                    <option>Suite Room</option>
                </select>
            </div>
            <button type="submit" class="btn btn-pine">Check Availability</button>
        </form>
    </div>
</section>

<section class="section" id="featured">
    <div class="container">
        <div class="section-head center reveal">
            <div class="eyebrow" style="justify-content:center;">Featured Rooms</div>
            <h2>Four categories, one standard of care</h2>
            <p>From a quiet Standard Room to the panoramic Suite, every space is designed around comfort, light, and detail.</p>
        </div>
        <div class="grid-4" id="featured-rooms-grid">
            <div class="skeleton" style="height:340px;"></div>
            <div class="skeleton" style="height:340px;"></div>
            <div class="skeleton" style="height:340px;"></div>
            <div class="skeleton" style="height:340px;"></div>
        </div>
        <div style="text-align:center; margin-top:40px;">
            <a href="<?= BASE_URL ?>/Frontend/pages/rooms.php" class="btn btn-outline">View All 50 Rooms</a>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head reveal">
            <div class="eyebrow">Why LuxuryStay</div>
            <h2>Details a guest actually notices</h2>
        </div>
        <div class="grid-3">
            <div class="card feature-tile reveal">
                <div class="feature-tile__icon">🗝️</div>
                <h3>Real Availability</h3>
                <p>Every room's calendar is checked date-by-date — never a false "sold out," never a double-booking.</p>
            </div>
            <div class="card feature-tile reveal">
                <div class="feature-tile__icon">🍽️</div>
                <h3>Meals, Your Way</h3>
                <p>Add breakfast, lunch, or dinner per stay with transparent per-person, per-day pricing.</p>
            </div>
            <div class="card feature-tile reveal">
                <div class="feature-tile__icon">🛎️</div>
                <h3>Attentive Service</h3>
                <p>From check-in to check-out, our team keeps you informed — including exactly why, if a request can't be met.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section-pine">
    <div class="container">
        <div class="section-head center reveal">
            <div class="eyebrow" style="justify-content:center;">Guest Notes</div>
            <h2>What our guests are saying</h2>
        </div>
        <div class="grid-3" id="testimonials-grid">
            <div class="skeleton" style="height:160px;"></div>
            <div class="skeleton" style="height:160px;"></div>
            <div class="skeleton" style="height:160px;"></div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../components/footer.php'; ?>
<?php include __DIR__ . '/../components/booking-modal.php'; ?>

<script>
window.__IS_LOGGED_IN__ = <?= $isLoggedIn ? 'true' : 'false' ?>;

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

async function loadFeaturedRooms() {
  const res = await apiGet('/api/rooms_list.php');
  const grid = document.getElementById('featured-rooms-grid');
  if (!res.success || !res.data.rooms.length) {
    grid.innerHTML = '<div class="empty-state"><div class="empty-state__icon">🏨</div><p>Rooms are being prepared. Check back shortly.</p></div>';
    return;
  }
  // Pick one room per category as "featured"
  const types = ['Suite Room', 'Super Deluxe Room', 'Deluxe Room', 'Standard Room'];
  const featured = types.map(t => res.data.rooms.find(r => r.room_type === t)).filter(Boolean);
  grid.innerHTML = featured.map(roomCardHtml).join('');
}

async function loadTestimonials() {
  const res = await apiGet('/api/gallery_list.php');
  const grid = document.getElementById('testimonials-grid');
  if (!res.success || !res.data.testimonials.length) { grid.innerHTML = ''; return; }
  grid.innerHTML = res.data.testimonials.slice(0, 3).map(t => `
    <div class="testimonial-card reveal" style="background:rgba(255,255,255,0.05); border-radius:12px;">
      <p style="color:#fff;">&ldquo;${escapeHtml(t.testimonial_text)}&rdquo;</p>
      <div class="testimonial-card__author">${escapeHtml(t.testimonial_author)}</div>
    </div>
  `).join('');
  document.querySelectorAll('.reveal').forEach(el => el.classList.add('in'));
}

function roomCardHtml(room) {
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
          <a href="<?= BASE_URL ?>/Frontend/pages/room-details.php?id=${room.id}" class="btn btn-outline btn-sm">View Room</a>
        </div>
      </div>
    </div>
  `;
}

function submitHeroSearch() {
  const params = new URLSearchParams();
  const ci = document.getElementById('hs-checkin').value;
  const co = document.getElementById('hs-checkout').value;
  if (ci) params.set('check_in', ci);
  if (co) params.set('check_out', co);
  params.set('guests', document.getElementById('hs-guests').value);
  const type = document.getElementById('hs-type').value;
  if (type) params.set('room_type', type);
  window.location.href = '<?= BASE_URL ?>/Frontend/pages/rooms.php?' + params.toString();
}

document.getElementById('hs-checkin').min = todayISO();
document.getElementById('hs-checkin').value = todayISO();
document.getElementById('hs-checkout').min = addDaysISO(todayISO(), 1);
document.getElementById('hs-checkout').value = addDaysISO(todayISO(), 1);
document.getElementById('hs-checkin').addEventListener('change', function() {
  document.getElementById('hs-checkout').min = addDaysISO(this.value, 1);
});

window.addEventListener('scroll', () => {
  const header = document.getElementById('site-header');
  if (window.scrollY > 60) header.classList.remove('on-hero'); else header.classList.add('on-hero');
});

loadFeaturedRooms();
loadTestimonials();
</script>
</body>
</html>
