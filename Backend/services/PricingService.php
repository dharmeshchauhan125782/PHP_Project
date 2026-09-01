<?php
/**
 * PricingService
 *
 * The backend NEVER trusts a total_price sent from JavaScript.
 * Every booking's price is recalculated here from:
 *   - the room's current price_per_night (from the rooms table)
 *   - the current meal_pricing table
 *   - the actual nights / guests / selected meals submitted
 *
 * Formula (spec section 29):
 *   room_subtotal  = price_per_night * nights
 *   meal_subtotal  = SUM over selected meals of (total_guests * meal_price * nights)
 *   grand_total    = room_subtotal + meal_subtotal
 *
 * "Applicable days" for meals = number of nights, i.e. each day of the
 * stay. This is a documented, consistent business rule (spec section 9).
 */

require_once __DIR__ . '/../config/db.php';

/** Fetch current meal prices as ['breakfast' => 300.00, 'lunch' => 500.00, 'dinner' => 500.00] */
function getMealPrices(PDO $pdo) {
    $stmt = $pdo->query("SELECT meal_type, price_per_person FROM meal_pricing WHERE is_active = 1");
    $prices = [];
    foreach ($stmt->fetchAll() as $row) {
        $prices[$row['meal_type']] = (float)$row['price_per_person'];
    }
    return $prices;
}

/**
 * Calculate a full price breakdown for a booking.
 *
 * @param float $pricePerNight   room's price_per_night (from DB, not client)
 * @param int   $nights          number of nights (check_out - check_in)
 * @param int   $adults
 * @param int   $children
 * @param array $selectedMeals   e.g. ['breakfast' => true, 'lunch' => false, 'dinner' => true]
 * @param array $mealPrices      from getMealPrices()
 *
 * @return array{
 *   nights:int, total_guests:int, room_subtotal:float,
 *   meals: array<string, array{price_per_person:float, total_guests:int, days:int, subtotal:float}>,
 *   meal_subtotal:float, grand_total:float
 * }
 */
function calculateBookingPrice($pricePerNight, $nights, $adults, $children, array $selectedMeals, array $mealPrices) {
    $totalGuests = $adults + $children;
    $roomSubtotal = round($pricePerNight * $nights, 2);

    $meals = [];
    $mealSubtotal = 0.0;

    foreach (['breakfast', 'lunch', 'dinner'] as $mealType) {
        if (empty($selectedMeals[$mealType])) continue;
        $pricePerPerson = $mealPrices[$mealType] ?? 0.0;
        $subtotal = round($totalGuests * $pricePerPerson * $nights, 2);
        $meals[$mealType] = [
            'price_per_person' => $pricePerPerson,
            'total_guests' => $totalGuests,
            'days' => $nights,
            'subtotal' => $subtotal,
        ];
        $mealSubtotal += $subtotal;
    }

    $mealSubtotal = round($mealSubtotal, 2);
    $grandTotal = round($roomSubtotal + $mealSubtotal, 2);

    return [
        'nights' => $nights,
        'total_guests' => $totalGuests,
        'room_subtotal' => $roomSubtotal,
        'meals' => $meals,
        'meal_subtotal' => $mealSubtotal,
        'grand_total' => $grandTotal,
    ];
}

/** Generate the next booking reference, e.g. LS-000123, from a just-inserted booking id. */
function formatBookingRef($bookingId) {
    return BOOKING_REF_PREFIX . str_pad((string)$bookingId, 6, '0', STR_PAD_LEFT);
}
