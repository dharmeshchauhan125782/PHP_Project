<?php
require_once __DIR__ . '/includes/functions.php';
$loggedIn = isUserLoggedIn();
$userName = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Luxury Stay — A Royal Hotel Experience</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ============ NAVBAR ============ -->
<nav class="navbar">
    <div class="container">
        <a href="index.php" class="brand">
            <div class="crest on-dark"><span>LS</span></div>
            <div class="brand-text">Luxury Stay<small>HOTEL &amp; SUITES</small></div>
        </a>
        <ul class="nav-links">
            <li><a href="index.php#rooms">Rooms</a></li>
            <li><a href="index.php#gallery">Gallery</a></li>
            <li><a href="index.php#testimonials">Testimonials</a></li>
            <li><a href="index.php#contact">Contact</a></li>
        </ul>
        <div class="nav-cta">
            <?php if ($loggedIn): ?>
                <a href="dashboard.php" class="btn btn-outline btn-sm">My Account</a>
                <a href="rooms.php" class="btn btn-gold btn-sm">Book Now</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline btn-sm">Sign In</a>
                <a href="register.php" class="btn btn-gold btn-sm">Book Now</a>
            <?php endif; ?>
        </div>
        <button class="hamburger" onclick="document.querySelector('.nav-links').classList.toggle('mobile-open')">☰</button>
    </div>
</nav>

<!-- ============ HERO ============ -->
<header class="hero">
    <div class="container hero-inner">
        <div>
            <div class="eyebrow">Est. Excellence in Hospitality</div>
            <h1>An address for those who expect <em>the extraordinary.</em></h1>
            <p class="lead">From the marble lobby to the last thread count, Luxury Stay is built around one idea: your stay should feel considered, not standard. Search live availability and reserve your suite in minutes.</p>
            <div class="hero-actions">
                <a href="#search" class="btn btn-gold">Check Availability</a>
                <a href="#rooms" class="btn btn-outline">Explore Rooms</a>
            </div>
            <div class="hero-stats">
                <div><strong>6</strong><span>Room Categories</span></div>
                <div><strong>24/7</strong><span>Concierge Service</span></div>
                <div><strong>4.9★</strong><span>Guest Rating</span></div>
            </div>
        </div>
        <div class="hero-visual">
            <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1200&auto=format&fit=crop" alt="Luxury Stay presidential suite interior">
            <div class="hero-visual-badge">
                <div class="crest on-dark" style="width:44px;height:44px;"><span style="font-size:14px;">LS</span></div>
                <div>
                    <strong>Royal Suite</strong>
                    <span>Panoramic views &amp; private butler</span>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ============ LIVE SEARCH (React) ============ -->
<div class="container" id="search">
    <div class="search-widget">
        <div id="search-root"></div>
    </div>
</div>

<!-- ============ FEATURED ROOMS (React) ============ -->
<section class="section" id="rooms">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Accommodation</div>
            <h2>Rooms &amp; Suites</h2>
            <p>Six distinct categories, each designed with its own character — chosen by capacity, view, and occasion.</p>
        </div>
        <div id="rooms-root"></div>
    </div>
</section>

<!-- ============ GALLERY (React) ============ -->
<section class="section" id="gallery" style="background:var(--white); border-top:1px solid var(--line); border-bottom:1px solid var(--line);">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Around The Property</div>
            <h2>Gallery</h2>
            <p>A glimpse of the spaces, textures and details guests remember.</p>
        </div>
        <div id="gallery-root"></div>
    </div>
</section>

<!-- ============ TESTIMONIALS (React) ============ -->
<section class="section section-navy" id="testimonials">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Guest Voices</div>
            <h2>What Our Guests Say</h2>
        </div>
        <div id="testimonials-root"></div>
    </div>
</section>

<!-- ============ CONTACT (React) ============ -->
<section class="section" id="contact">
    <div class="container" style="max-width:640px;">
        <div class="section-head">
            <div class="eyebrow">Get In Touch</div>
            <h2>Questions Before You Book?</h2>
            <p>Send a message and our concierge team will respond within the day.</p>
        </div>
        <div id="contact-root"></div>
    </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="brand" style="margin-bottom:16px;">
                    <div class="crest on-dark"><span>LS</span></div>
                    <div class="brand-text">Luxury Stay<small>HOTEL &amp; SUITES</small></div>
                </div>
                <p style="font-size:14px; max-width:280px;">A full-stack hotel reservation platform built with PHP, MySQL and React — designed for guests who expect more.</p>
            </div>
            <div>
                <h4>Explore</h4>
                <ul>
                    <li><a href="index.php#rooms">Rooms</a></li>
                    <li><a href="index.php#gallery">Gallery</a></li>
                    <li><a href="index.php#testimonials">Testimonials</a></li>
                </ul>
            </div>
            <div>
                <h4>Account</h4>
                <ul>
                    <li><a href="login.php">Sign In</a></li>
                    <li><a href="register.php">Create Account</a></li>
                    <li><a href="rooms.php">Book a Room</a></li>
                </ul>
            </div>
            <div>
                <h4>Contact</h4>
                <ul>
                    <li>concierge@luxurystay.example</li>
                    <li>+91 98765 43210</li>
                    <li>Rajkot, Gujarat, India</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">© <?= date('Y') ?> Luxury Stay Hotel &amp; Suites. All rights reserved.</div>
    </div>
</footer>

<script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<script src="assets/js/api.js"></script>
<script type="text/babel" src="assets/js/home.js"></script>
</body>
</html>
