<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (!isAdminLoggedIn()) { header('Location: /Admin/pages/login.php'); exit; }
$activeSection = 'meal-pricing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meal Pricing — LuxuryStay Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
</head>
<body>
<div class="dash-shell">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>
  <main class="dash-main">
    <div class="dash-topbar">
      <div><h2 style="margin-bottom:4px;">Meal Pricing</h2><p style="color:var(--ink-soft);">Changes apply immediately to all new bookings.</p></div>
    </div>

    <div class="grid-3" id="meal-cards">
      <div class="skeleton" style="height:180px;"></div>
      <div class="skeleton" style="height:180px;"></div>
      <div class="skeleton" style="height:180px;"></div>
    </div>
  </main>
</div>

<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
const MEAL_ICONS = { breakfast: '🥐', lunch: '🍲', dinner: '🍽️' };

async function loadMealPricing() {
  const res = await fetch('<?= BASE_URL ?>/Backend/api/admin_meal_pricing.php', { credentials: 'same-origin' }).then(r => r.json());
  if (!res.success) { toast(res.message, 'error'); return; }
  setCsrfToken(res.data.csrf_token);
  document.getElementById('meal-cards').innerHTML = res.data.meal_prices.map(m => `
    <div class="card" style="padding:28px;">
      <div style="font-size:32px; margin-bottom:8px;">${MEAL_ICONS[m.meal_type]}</div>
      <h3 style="text-transform:capitalize;">${m.meal_type}</h3>
      <p style="font-size:12.5px;">Per person, per day.</p>
      <div class="form-group">
        <label>Price (₹)</label>
        <input type="number" min="0" step="1" id="price-${m.meal_type}" value="${Number(m.price_per_person)}">
      </div>
      <button class="btn btn-brass btn-block" onclick="updateMealPrice('${m.meal_type}')">Update Price</button>
    </div>
  `).join('');
}

async function updateMealPrice(mealType) {
  const price = document.getElementById(`price-${mealType}`).value;
  const res = await apiPostJson('/api/admin_meal_pricing.php', { meal_type: mealType, price_per_person: Number(price) });
  if (res.success) toast(res.message, 'success');
  else toast(res.message, 'error');
}

loadMealPricing();
</script>
</body>
</html>
