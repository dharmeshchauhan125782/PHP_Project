<?php
/**
 * Public "Hotel Updates" feed (spec section 17). No login required —
 * announcements are meant to be visible on the public site too.
 */
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
$stmt = $pdo->query("SELECT a.id, a.title, a.message, a.type, a.created_at, ad.name AS published_by
    FROM announcements a
    JOIN admin ad ON ad.id = a.created_by
    WHERE a.is_active = 1
    ORDER BY a.created_at DESC
    LIMIT 50");
$announcements = $stmt->fetchAll();

jsonResponse(true, '', ['announcements' => $announcements]);
