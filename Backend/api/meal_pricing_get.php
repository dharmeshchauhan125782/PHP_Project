<?php
/**
 * Public read-only endpoint so the booking form can show live meal
 * prices without hardcoding them into frontend JS (spec section 16).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/PricingService.php';

$pdo = getDB();
$prices = getMealPrices($pdo);

jsonResponse(true, '', ['meal_prices' => $prices]);
