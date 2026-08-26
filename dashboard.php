<?php
require_once __DIR__ . '/includes/functions.php';
if (!isUserLoggedIn()) { header('Location: login.php'); exit; }
$userName = $_SESSION['user_name'];
$initialTab = ($_GET['tab'] ?? '') === 'bookings' ? 'bookings' : 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account — Luxury Stay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="dash-shell">
    <aside class="dash-sidebar">
        <a href="index.php" class="brand">
            <div class="crest on-dark"><span>LS</span></div>
            <div class="brand-text">Luxury Stay<small>HOTEL &amp; SUITES</small></div>
        </a>
        <a class="dash-link" id="nav-overview" href="#overview">Overview &amp; Profile</a>
        <a class="dash-link" id="nav-bookings" href="#bookings">My Bookings</a>
        <a class="dash-link" href="rooms.php">Book a New Room</a>
        <div style="flex:1;"></div>
        <a class="dash-link" href="index.php">← Back to Site</a>
        <a class="dash-link" href="#" id="logout-link">Sign Out</a>
    </aside>
    <main class="dash-main">
        <div class="dash-topbar">
            <div>
                <h1>Welcome, <?= htmlspecialchars($userName) ?></h1>
                <p>Manage your profile and view your reservation history.</p>
            </div>
        </div>
        <div id="dashboard-root"></div>
    </main>
</div>

<script>
window.LS_INITIAL_TAB = <?= json_encode($initialTab) ?>;
document.getElementById('logout-link').addEventListener('click', async function(e) {
    e.preventDefault();
    await fetch('api/logout.php', { credentials: 'same-origin' });
    window.location.href = 'index.php';
});
</script>
<script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<script src="assets/js/api.js"></script>
<script type="text/babel" src="assets/js/dashboard.js"></script>
</body>
</html>
