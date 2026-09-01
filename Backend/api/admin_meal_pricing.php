<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $prices = $pdo->query("SELECT meal_type, price_per_person, updated_at FROM meal_pricing ORDER BY FIELD(meal_type,'breakfast','lunch','dinner')")->fetchAll();
    jsonResponse(true, '', ['meal_prices' => $prices, 'csrf_token' => csrfToken()]);
}

if ($method === 'POST') {
    requireCsrf();
    $input = getJsonInput();
    $mealType = $input['meal_type'] ?? '';
    $price = isset($input['price_per_person']) ? (float)$input['price_per_person'] : null;

    if (!in_array($mealType, ['breakfast', 'lunch', 'dinner'], true)) {
        jsonResponse(false, 'Invalid meal type.');
    }
    if ($price === null || $price < 0 || $price > 100000) {
        jsonResponse(false, 'Please enter a valid price.');
    }

    $stmt = $pdo->prepare("UPDATE meal_pricing SET price_per_person = ?, updated_by = ? WHERE meal_type = ?");
    $stmt->execute([$price, $_SESSION['admin_id'], $mealType]);

    logActivity('meal_pricing_changed', ucfirst($mealType) . " price set to " . APP_CURRENCY_SYMBOL . $price, 'meal_pricing', null);

    jsonResponse(true, ucfirst($mealType) . ' price updated successfully.');
}

jsonResponse(false, 'Invalid request method.');
