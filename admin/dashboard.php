<?php
require_once __DIR__ . '/../includes/functions.php';
if (!isAdminLoggedIn()) { header('Location: login.php'); exit; }
$adminName = $_SESSION['admin_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Luxury Stay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-shell">
    <aside class="dash-sidebar">
        <a href="../index.php" class="brand">
            <div class="crest on-dark"><span>LS</span></div>
            <div class="brand-text">Luxury Stay<small>ADMIN PANEL</small></div>
        </a>
        <a class="dash-link" data-tab="overview" href="#overview">Overview</a>
        <a class="dash-link" data-tab="bookings" href="#bookings">Bookings</a>
        <a class="dash-link" data-tab="rooms" href="#rooms">Rooms</a>
        <a class="dash-link" data-tab="users" href="#users">Users</a>
        <a class="dash-link" data-tab="gallery" href="#gallery">Gallery</a>
        <div style="flex:1;"></div>
        <a class="dash-link" href="../index.php">← Back to Site</a>
        <a class="dash-link" href="#" id="logout-link">Sign Out</a>
    </aside>
    <main class="dash-main">
        <div class="dash-topbar">
            <div>
                <h1 id="page-title">Overview</h1>
                <p>Signed in as <?= htmlspecialchars($adminName) ?></p>
            </div>
        </div>
        <div id="admin-root"></div>
    </main>
</div>

<script>
document.getElementById('logout-link').addEventListener('click', async function(e) {
    e.preventDefault();
    await fetch('../api/logout.php', { credentials: 'same-origin' });
    window.location.href = 'login.php';
});
</script>
<script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<script src="../assets/js/api.js"></script>
<script type="text/babel" src="../assets/js/admin.js"></script>
</body>
</html>
