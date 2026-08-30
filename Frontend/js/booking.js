/**
 * Booking modal logic. Requires api.js to be loaded first.
 * This live price preview is for UX only — the backend in
 * booking_create.php always recalculates authoritatively.
 */

let BK_ROOM = null;      // currently selected room object
let BK_STEP = 1;
let BK_MEAL_PRICES = { breakfast: 300, lunch: 500, dinner: 500 };
let BK_STATE = { adults: 2, children: 0, meals: { breakfast: false, lunch: false, dinner: false } };

function bkEscapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

async function loadMealPrices() {
  const res = await apiGet('/api/meal_pricing_get.php');
  if (res.success) {
    BK_MEAL_PRICES = res.data.meal_prices;
    document.getElementById('bk-meal-breakfast-price').textContent = `${formatMoney(BK_MEAL_PRICES.breakfast)}/person/day`;
    document.getElementById('bk-meal-lunch-price').textContent = `${formatMoney(BK_MEAL_PRICES.lunch)}/person/day`;
    document.getElementById('bk-meal-dinner-price').textContent = `${formatMoney(BK_MEAL_PRICES.dinner)}/person/day`;
  }
}

function openBookingModal(room) {
  if (!isLoggedInClient()) {
    window.location.href = (window.__BASE_URL__ || '') + '/Frontend/pages/login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
    return;
  }
  BK_ROOM = room;
  BK_STEP = 1;
  BK_STATE = { adults: Math.min(2, room.capacity), children: 0, meals: { breakfast: false, lunch: false, dinner: false } };

  document.getElementById('booking-modal-title').textContent = `Book ${room.room_type} · Room ${room.room_number}`;
  document.getElementById('bk-adults-val').textContent = BK_STATE.adults;
  document.getElementById('bk-children-val').textContent = BK_STATE.children;
  document.getElementById('bk-capacity-max').textContent = room.capacity;
  document.getElementById('bk-checkin').min = todayISO();
  document.getElementById('bk-checkin').value = todayISO();
  document.getElementById('bk-checkout').min = addDaysISO(todayISO(), 1);
  document.getElementById('bk-checkout').value = addDaysISO(todayISO(), 1);
  document.getElementById('bk-form-msg')?.classList.remove('error', 'success');

  ['breakfast', 'lunch', 'dinner'].forEach((m) => {
    document.getElementById(`bk-meal-${m}`).checked = false;
    document.getElementById(`bk-meal-${m}-row`).classList.remove('checked');
  });

  goToBookingStep(1);
  loadMealPrices().then(recalcBookingPrice);
  document.getElementById('booking-modal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeBookingModal() {
  document.getElementById('booking-modal').classList.remove('open');
  document.body.style.overflow = '';
}

function closeBookingSuccessModal() {
  document.getElementById('booking-success-modal').classList.remove('open');
  document.body.style.overflow = '';
  window.location.href = (window.__BASE_URL__ || '') + '/Frontend/pages/dashboard.php';
}

function isLoggedInClient() {
  return window.__IS_LOGGED_IN__ === true;
}

function stepGuest(type, delta) {
  const next = BK_STATE[type] + delta;
  if (type === 'adults' && (next < 1 || (next + BK_STATE.children) > BK_ROOM.capacity)) return;
  if (type === 'children' && (next < 0 || (BK_STATE.adults + next) > BK_ROOM.capacity)) return;
  BK_STATE[type] = next;
  document.getElementById(`bk-${type}-val`).textContent = next;
  recalcBookingPrice();
}

function toggleMeal(meal) {
  const checkbox = document.getElementById(`bk-meal-${meal}`);
  checkbox.checked = !checkbox.checked;
  BK_STATE.meals[meal] = checkbox.checked;
  document.getElementById(`bk-meal-${meal}-row`).classList.toggle('checked', checkbox.checked);
  recalcBookingPrice();
}

function getNights() {
  const ci = document.getElementById('bk-checkin').value;
  const co = document.getElementById('bk-checkout').value;
  if (!ci || !co) return 0;
  const diff = (new Date(co) - new Date(ci)) / (1000 * 60 * 60 * 24);
  return diff > 0 ? diff : 0;
}

function recalcBookingPrice() {
  if (!BK_ROOM) return;
  const nights = getNights();
  const totalGuests = BK_STATE.adults + BK_STATE.children;
  const roomSubtotal = BK_ROOM.price_per_night * nights;

  let mealSubtotal = 0;
  ['breakfast', 'lunch', 'dinner'].forEach((m) => {
    const row = document.getElementById(`bk-${m}-row`);
    if (BK_STATE.meals[m] && nights > 0) {
      const amt = totalGuests * BK_MEAL_PRICES[m] * nights;
      mealSubtotal += amt;
      document.getElementById(`bk-${m}-amt`).textContent = formatMoney(amt);
      row.style.display = 'flex';
    } else {
      row.style.display = 'none';
    }
  });

  document.getElementById('bk-room-line').textContent = `${formatMoney(BK_ROOM.price_per_night)} / night`;
  document.getElementById('bk-nights-line').textContent = nights || '—';
  document.getElementById('bk-room-subtotal').textContent = formatMoney(roomSubtotal);
  document.getElementById('bk-grand-total').textContent = formatMoney(roomSubtotal + mealSubtotal);
}

['bk-checkin', 'bk-checkout'].forEach((id) => {
  document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', () => {
      const ci = document.getElementById('bk-checkin');
      const co = document.getElementById('bk-checkout');
      if (ci.value) co.min = addDaysISO(ci.value, 1);
      if (co.value && co.value <= ci.value) co.value = addDaysISO(ci.value, 1);
      recalcBookingPrice();
    });
  });
});

function showBookingError(msg) {
  const el = document.getElementById('booking-form-msg');
  el.textContent = msg;
  el.className = 'form-msg error';
}

function clearBookingError() {
  const el = document.getElementById('booking-form-msg');
  el.className = 'form-msg';
  el.textContent = '';
}

function goToBookingStep(step) {
  BK_STEP = step;
  document.querySelectorAll('.booking-step').forEach((el) => {
    el.style.display = Number(el.dataset.step) === step ? 'block' : 'none';
  });
  document.querySelectorAll('.steps-nav__item').forEach((el) => {
    const s = Number(el.dataset.step);
    el.classList.toggle('active', s === step);
    el.classList.toggle('done', s < step);
  });
  document.getElementById('bk-back-btn').style.visibility = step === 1 ? 'hidden' : 'visible';
  document.getElementById('bk-next-btn').textContent = step === 3 ? 'Submit Booking Request' : 'Continue →';

  if (step === 3) renderBookingConfirmSummary();
  clearBookingError();
}

function bookingPrevStep() {
  if (BK_STEP > 1) goToBookingStep(BK_STEP - 1);
}

function bookingNextStep() {
  clearBookingError();
  if (BK_STEP === 1) {
    const nights = getNights();
    if (nights <= 0) { showBookingError('Please select a valid check-out date after check-in.'); return; }
    if (BK_STATE.adults + BK_STATE.children > BK_ROOM.capacity) {
      showBookingError(`This room can accommodate a maximum of ${BK_ROOM.capacity} guests.`);
      return;
    }
    goToBookingStep(2);
    return;
  }
  if (BK_STEP === 2) {
    const name = document.getElementById('bk-guest-name').value.trim();
    const email = document.getElementById('bk-guest-email').value.trim();
    const phone = document.getElementById('bk-guest-phone').value.trim();
    if (!name) { showBookingError('Please enter the guest name.'); return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showBookingError('Please enter a valid email address.'); return; }
    if (!/^[0-9+\-\s()]{7,20}$/.test(phone)) { showBookingError('Please enter a valid phone number.'); return; }
    goToBookingStep(3);
    return;
  }
  if (BK_STEP === 3) {
    submitBooking();
  }
}

function renderBookingConfirmSummary() {
  const nights = getNights();
  const totalGuests = BK_STATE.adults + BK_STATE.children;
  const meals = ['breakfast', 'lunch', 'dinner'].filter((m) => BK_STATE.meals[m]);
  document.getElementById('bk-confirm-summary').innerHTML = `
    <div class="price-breakdown" style="font-family: var(--font-body);">
      <div class="price-row"><span>Room</span><span>${bkEscapeHtml(BK_ROOM.room_type)} · Room ${bkEscapeHtml(BK_ROOM.room_number)}</span></div>
      <div class="price-row"><span>Check-in</span><span>${formatDate(document.getElementById('bk-checkin').value)}</span></div>
      <div class="price-row"><span>Check-out</span><span>${formatDate(document.getElementById('bk-checkout').value)}</span></div>
      <div class="price-row"><span>Guests</span><span>${BK_STATE.adults} Adults, ${BK_STATE.children} Children</span></div>
      <div class="price-row"><span>Meals</span><span>${meals.length ? meals.map(m => m[0].toUpperCase()+m.slice(1)).join(', ') : 'None selected'}</span></div>
      <div class="price-row"><span>Guest</span><span>${bkEscapeHtml(document.getElementById('bk-guest-name').value)}</span></div>
      <div class="price-row total"><span>Grand Total</span><span id="bk-confirm-total">${document.getElementById('bk-grand-total').textContent}</span></div>
    </div>
  `;
}

async function submitBooking() {
  const btn = document.getElementById('bk-next-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner spinner-dark"></span> Submitting…';

  const payload = {
    room_id: BK_ROOM.id,
    check_in: document.getElementById('bk-checkin').value,
    check_out: document.getElementById('bk-checkout').value,
    adults: BK_STATE.adults,
    children: BK_STATE.children,
    guest_name: document.getElementById('bk-guest-name').value.trim(),
    guest_email: document.getElementById('bk-guest-email').value.trim(),
    guest_phone: document.getElementById('bk-guest-phone').value.trim(),
    meals: BK_STATE.meals,
  };

  const res = await apiPostJson('/api/booking_create.php', payload);
  btn.disabled = false;
  btn.textContent = 'Submit Booking Request';

  if (!res.success) {
    showBookingError(res.message || 'Could not complete booking. Please try again.');
    return;
  }

  closeBookingModal();
  const d = res.data;
  const mealLines = Object.entries(d.meals || {}).map(([type, m]) =>
    `<div class="price-row"><span>${type[0].toUpperCase()+type.slice(1)}</span><span>${formatMoney(m.subtotal)}</span></div>`
  ).join('');

  document.getElementById('bk-success-details').innerHTML = `
    <div class="price-row"><span>Booking ID</span><span>${bkEscapeHtml(d.booking_ref)}</span></div>
    <div class="price-row"><span>Room</span><span>${bkEscapeHtml(d.room_type)} · ${bkEscapeHtml(d.room_number)}</span></div>
    <div class="price-row"><span>Check-in</span><span>${formatDate(d.check_in)}</span></div>
    <div class="price-row"><span>Check-out</span><span>${formatDate(d.check_out)}</span></div>
    <div class="price-row"><span>Room subtotal</span><span>${formatMoney(d.room_subtotal)}</span></div>
    ${mealLines}
    <div class="price-row total"><span>Grand Total</span><span>${formatMoney(d.grand_total)}</span></div>
  `;
  document.getElementById('booking-success-modal').classList.add('open');
  toast('Booking request submitted!', 'success');
}
