<?php
require_once __DIR__ . '/includes/functions.php';
$loggedIn = isUserLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book a Room — Luxury Stay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="index.php" class="brand">
            <div class="crest on-dark"><span>LS</span></div>
            <div class="brand-text">Luxury Stay<small>HOTEL &amp; SUITES</small></div>
        </a>
        <ul class="nav-links">
            <li><a href="index.php#rooms">Rooms</a></li>
            <li><a href="index.php#gallery">Gallery</a></li>
            <li><a href="index.php#contact">Contact</a></li>
        </ul>
        <div class="nav-cta">
            <?php if ($loggedIn): ?>
                <a href="dashboard.php" class="btn btn-outline btn-sm">My Account</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline btn-sm">Sign In</a>
                <a href="register.php" class="btn btn-gold btn-sm">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<header class="section" style="padding-top:56px; padding-bottom:0; background:var(--navy-950);">
    <div class="container">
        <div class="eyebrow">Reservations</div>
        <h1 style="color:var(--white); font-size:clamp(30px,4vw,46px);">Choose Your Room</h1>
        <p style="color:rgba(255,255,255,0.65); max-width:520px; margin-top:12px;">Select your dates, pick the room that suits your stay, and reserve instantly.</p>
    </div>
</header>

<section class="section" style="padding-top:56px;">
    <div class="container">
        <div id="rooms-root"></div>
    </div>
</section>

<div id="booking-modal-root"></div>

<footer class="footer">
    <div class="container">
        <div class="footer-bottom">© <?= date('Y') ?> Luxury Stay Hotel &amp; Suites. All rights reserved.</div>
    </div>
</footer>

<script>
    window.LS_LOGGED_IN = <?= $loggedIn ? 'true' : 'false' ?>;
    window.LS_PREFILL = {
        room_id: <?= json_encode($_GET['room_id'] ?? null) ?>,
        check_in: <?= json_encode($_GET['check_in'] ?? '') ?>,
        check_out: <?= json_encode($_GET['check_out'] ?? '') ?>,
        guests: <?= json_encode($_GET['guests'] ?? 2) ?>
    };
</script>
<script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<script src="assets/js/api.js"></script>
<script type="text/babel" src="assets/js/rooms.js"></script>
</body>
</html>
