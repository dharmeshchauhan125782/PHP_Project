<?php
/**
 * Shared header. Include after starting a session (functions.php)
 * and optionally setting $heroHeader = true for a transparent-on-hero variant.
 */
$isLoggedIn = isUserLoggedIn();
$userName = $_SESSION['user_name'] ?? '';
$headerClass = !empty($heroHeader) ? 'site-header on-hero' : 'site-header';
?>
<script>window.__BASE_URL__ = <?= json_encode(BASE_URL) ?>; window.__CSRF_TOKEN__ = <?= json_encode(csrfToken()) ?>;</script>
<header class="<?= $headerClass ?>" id="site-header">
    <div class="container nav">
        <a href="<?= BASE_URL ?>/Frontend/pages/index.php" class="brand">
            <div class="brand-mark">LS</div>
            <div class="brand-text">LuxuryStay</div>
        </a>
        <nav class="nav-links" id="nav-links">
            <a href="<?= BASE_URL ?>/Frontend/pages/index.php">Home</a>
            <a href="<?= BASE_URL ?>/Frontend/pages/rooms.php">Rooms</a>
            <a href="<?= BASE_URL ?>/Frontend/pages/gallery.php">Gallery</a>
            <a href="<?= BASE_URL ?>/Frontend/pages/updates.php">Hotel Updates</a>
            <a href="<?= BASE_URL ?>/Frontend/pages/contact.php">Contact</a>
            <?php if ($isLoggedIn): ?>
                <a href="<?= BASE_URL ?>/Frontend/pages/dashboard.php" class="btn btn-outline btn-sm" style="margin-left:8px;">My Dashboard</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/Frontend/pages/login.php" class="btn btn-outline btn-sm" style="margin-left:8px;">Sign In</a>
            <?php endif; ?>
        </nav>
        <div class="nav-actions">
            <?php if ($isLoggedIn): ?>
                <a href="<?= BASE_URL ?>/Frontend/pages/dashboard.php#notifications" class="notif-bell" id="notif-bell" title="Notifications">
                    🔔<span class="notif-dot" id="notif-dot" style="display:none;"></span>
                </a>
            <?php endif; ?>
            <button class="nav-toggle" aria-label="Toggle menu">☰</button>
        </div>
    </div>
</header>
