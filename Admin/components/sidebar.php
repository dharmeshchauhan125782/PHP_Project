<?php
/**
 * Shared admin sidebar. Include inside a .dash-shell wrapper.
 * $activeSection should be set by the including page (e.g. 'dashboard', 'bookings').
 */
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$activeSection = $activeSection ?? '';
function navClass($section, $active) { return 'dash-link' . ($section === $active ? ' active' : ''); }
?>
<script>window.__BASE_URL__ = <?= json_encode(BASE_URL) ?>; window.__CSRF_TOKEN__ = <?= json_encode(csrfToken()) ?>;</script>
<aside class="dash-sidebar" id="admin-sidebar">
    <div class="brand">
        <div class="brand-mark" style="background:var(--brass); color:var(--pine-deep);">LS</div>
        <div class="brand-text">LuxuryStay<small>ADMIN PANEL</small></div>
    </div>
    <a class="<?= navClass('dashboard', $activeSection) ?>" href="<?= BASE_URL ?>/Admin/pages/dashboard.php">📊 Dashboard</a>
    <a class="<?= navClass('bookings', $activeSection) ?>" href="<?= BASE_URL ?>/Admin/pages/bookings.php">🗝️ Bookings</a>
    <a class="<?= navClass('rooms', $activeSection) ?>" href="<?= BASE_URL ?>/Admin/pages/rooms.php">🛏️ Rooms</a>
    <a class="<?= navClass('customers', $activeSection) ?>" href="<?= BASE_URL ?>/Admin/pages/customers.php">👤 Customers</a>
    <a class="<?= navClass('gallery', $activeSection) ?>" href="<?= BASE_URL ?>/Admin/pages/gallery.php">🖼️ Gallery</a>
    <a class="<?= navClass('meal-pricing', $activeSection) ?>" href="<?= BASE_URL ?>/Admin/pages/meal-pricing.php">🍽️ Meal Pricing</a>
    <a class="<?= navClass('announcements', $activeSection) ?>" href="<?= BASE_URL ?>/Admin/pages/announcements.php">📣 Announcements</a>
    <a class="<?= navClass('activity-logs', $activeSection) ?>" href="<?= BASE_URL ?>/Admin/pages/activity-logs.php">📜 Activity Logs</a>
    <a class="<?= navClass('settings', $activeSection) ?>" href="<?= BASE_URL ?>/Admin/pages/settings.php">⚙️ Settings</a>
    <div style="margin-top:auto; padding-top:20px; border-top:1px solid rgba(255,255,255,0.1);">
        <div style="padding:8px 14px; font-size:12.5px; color:rgba(255,255,255,0.5);">Signed in as<br><strong style="color:#fff;"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <a class="dash-link" href="#" onclick="doAdminLogout(); return false;">🚪 Logout</a>
    </div>
</aside>
<script>
async function doAdminLogout() {
  await fetch('<?= BASE_URL ?>/Backend/auth/admin_logout.php', { credentials: 'same-origin' }).catch(() => {});
  window.location.href = '<?= BASE_URL ?>/Admin/pages/login.php';
}
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('sidebar-toggle');
  if (toggle) toggle.addEventListener('click', () => document.getElementById('admin-sidebar').classList.toggle('open'));
});
</script>
