/**
 * LuxuryStay — shared API + UI helpers.
 * Every page includes this before its own page script.
 */

const API_BASE = (window.__BASE_URL__ || '') + '/Backend';
let CSRF_TOKEN = window.__CSRF_TOKEN__ || '';

function setCsrfToken(token) {
  if (token) CSRF_TOKEN = token;
}

async function apiGet(path) {
  const res = await fetch(`${API_BASE}${path}`, { credentials: 'same-origin' });
  return res.json();
}

async function apiPostJson(path, body) {
  const res = await fetch(`${API_BASE}${path}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
    body: JSON.stringify(body || {}),
  });
  const data = await res.json();
  if (data.data && data.data.csrf_token) setCsrfToken(data.data.csrf_token);
  return data;
}

async function apiPostForm(path, formData) {
  formData.append('csrf_token', CSRF_TOKEN);
  const res = await fetch(`${API_BASE}${path}`, {
    method: 'POST',
    credentials: 'same-origin',
    body: formData,
  });
  return res.json();
}

/* ---------------- Toasts ---------------- */
function ensureToastStack() {
  let stack = document.querySelector('.toast-stack');
  if (!stack) {
    stack = document.createElement('div');
    stack.className = 'toast-stack';
    document.body.appendChild(stack);
  }
  return stack;
}

function toast(message, type = 'default') {
  const stack = ensureToastStack();
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = message;
  stack.appendChild(el);
  setTimeout(() => {
    el.style.opacity = '0';
    el.style.transform = 'translateX(20px)';
    el.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
    setTimeout(() => el.remove(), 200);
  }, 3800);
}

/* ---------------- Currency / date formatting ---------------- */
function formatMoney(n) {
  const num = Number(n) || 0;
  return '₹' + num.toLocaleString('en-IN', { maximumFractionDigits: 0 });
}

function formatDate(dateStr) {
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

function todayISO() {
  const d = new Date();
  const tz = d.getTimezoneOffset() * 60000;
  return new Date(d - tz).toISOString().slice(0, 10);
}

function addDaysISO(dateStr, days) {
  const d = new Date(dateStr + 'T00:00:00');
  d.setDate(d.getDate() + days);
  return d.toISOString().slice(0, 10);
}

/* ---------------- Nav toggle (mobile) ---------------- */
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.nav-toggle');
  const links = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', () => links.classList.toggle('open'));
  }

  // Scroll-reveal for elements marked .reveal
  const revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealEls.forEach((el) => io.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add('in'));
  }
});

/* ---------------- Logout (shared) ---------------- */
async function doLogout(redirectTo) {
  await apiGet('/api/logout.php').catch(() => {});
  window.location.href = redirectTo || (window.__BASE_URL__ || '') + '/Frontend/pages/index.php';
}
