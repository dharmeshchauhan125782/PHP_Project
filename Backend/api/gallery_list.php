<?php
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();

$images = $pdo->query("SELECT * FROM gallery WHERE category != 'testimonial' ORDER BY created_at DESC")->fetchAll();
$testimonials = $pdo->query("SELECT * FROM gallery WHERE category = 'testimonial' ORDER BY created_at DESC")->fetchAll();

jsonResponse(true, '', ['images' => $images, 'testimonials' => $testimonials]);
