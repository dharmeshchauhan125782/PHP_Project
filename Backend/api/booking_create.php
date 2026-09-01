<?php
/**
 * Create a new booking.
 *
 * Expects JSON body:
 * {
 *   room_id, check_in, check_out, adults, children,
 *   guest_name, guest_email, guest_phone,
 *   meals: { breakfast: bool, lunch: bool, dinner: bool },
 *   csrf_token
 * }
 *
 * The backend NEVER trusts a client-submitted total price — it is
 * always recalculated from the DB's current room price and meal prices
 * (spec sections 9, 29). Availability is re-checked and locked inside
 * a transaction to prevent double-booking races (spec section 11).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/AvailabilityService.php';
require_once __DIR__ . '/../services/PricingService.php';

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

requireCsrf();

$input = getJsonInput();

$roomId   = cleanInt($input['room_id'] ?? null, 1);
$checkIn  = (string)($input['check_in'] ?? '');
$checkOut = (string)($input['check_out'] ?? '');
$adults   = cleanInt($input['adults'] ?? 1, MIN_CAPACITY_GUESTS, MAX_ADULTS_PER_BOOKING);
$children = cleanInt($input['children'] ?? 0, 0, MAX_CHILDREN_PER_BOOKING);

$guestName  = clean($input['guest_name'] ?? '');
$guestEmail = cleanEmail($input['guest_email'] ?? '');
$guestPhone = clean($input['guest_phone'] ?? '');

$mealsInput = is_array($input['meals'] ?? null) ? $input['meals'] : [];
$selectedMeals = [
    'breakfast' => !empty($mealsInput['breakfast']),
    'lunch'     => !empty($mealsInput['lunch']),
    'dinner'    => !empty($mealsInput['dinner']),
];

// ------------------------------------------------------------
// Validation — never trust the frontend alone (spec sections 6, 7, 19)
// ------------------------------------------------------------
if (!$roomId) {
    jsonResponse(false, 'Please select a room.');
}
if (!$guestName || strlen($guestName) > 100) {
    jsonResponse(false, 'Please enter the guest name.');
}
if (!$guestEmail) {
    jsonResponse(false, 'Please enter a valid guest email.');
}
if (!isValidPhone($guestPhone)) {
    jsonResponse(false, 'Please enter a valid guest phone number.');
}
if ($adults === null) {
    jsonResponse(false, 'Please select a valid number of adults.');
}
if ($children === null) {
    jsonResponse(false, 'Please select a valid number of children.');
}

$ci = parseStrictDate($checkIn);
$co = parseStrictDate($checkOut);
$today = new DateTime('today');

if (!$ci || !$co) {
    jsonResponse(false, 'Please provide valid check-in and check-out dates.');
}
if ($ci < $today) {
    jsonResponse(false, 'Check-in date cannot be in the past.');
}
if ($co <= $ci) {
    jsonResponse(false, 'Check-out date must be after check-in date.');
}

$pdo = getDB();

$room = getRoomOrNull($pdo, $roomId);
if (!$room || !$room['is_active']) {
    jsonResponse(false, 'Selected room could not be found.');
}
if ($room['status'] === 'maintenance') {
    jsonResponse(false, 'This room is currently under maintenance and cannot be booked.');
}

$totalGuests = $adults + $children;
if ($totalGuests > (int)$room['capacity']) {
    jsonResponse(false, 'This room can accommodate a maximum of ' . $room['capacity'] . ' guests.');
}

// ------------------------------------------------------------
// Transaction: lock overlapping bookings, recheck availability,
// recalculate price from DB, insert booking + booking_meals atomically.
// ------------------------------------------------------------
$pdo->beginTransaction();
try {
    if (!lockAndCheckAvailability($pdo, $roomId, $checkIn, $checkOut)) {
        $pdo->rollBack();
        jsonResponse(false, 'This room is already booked for the selected dates. Please choose different dates or another room.');
    }

    $nights = $ci->diff($co)->days;
    $mealPrices = getMealPrices($pdo);
    $pricing = calculateBookingPrice((float)$room['price_per_night'], $nights, $adults, $children, $selectedMeals, $mealPrices);

    $bookingRef = 'PENDING'; // placeholder, replaced after insert with real id
    $stmt = $pdo->prepare("INSERT INTO bookings
        (booking_ref, user_id, room_id, check_in, check_out, adults, children, nights,
         room_price, meal_price, total_price, guest_name, guest_email, guest_phone, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $bookingRef, $_SESSION['user_id'], $roomId, $checkIn, $checkOut, $adults, $children, $nights,
        $pricing['room_subtotal'], $pricing['meal_subtotal'], $pricing['grand_total'],
        $guestName, $guestEmail, $guestPhone,
    ]);

    $bookingId = (int)$pdo->lastInsertId();
    $realRef = formatBookingRef($bookingId);
    $pdo->prepare("UPDATE bookings SET booking_ref = ? WHERE id = ?")->execute([$realRef, $bookingId]);

    foreach ($pricing['meals'] as $mealType => $mealData) {
        $stmt = $pdo->prepare("INSERT INTO booking_meals (booking_id, meal_type, price_per_person, total_guests, days, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$bookingId, $mealType, $mealData['price_per_person'], $mealData['total_guests'], $mealData['days'], $mealData['subtotal']]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('booking_create failed: ' . $e->getMessage());
    jsonResponse(false, 'Could not complete booking. Please try again.');
}

notifyUser(
    $_SESSION['user_id'],
    'booking_submitted',
    'Booking Request Submitted',
    "Your booking {$realRef} for Room {$room['room_number']} is pending approval.",
    $bookingId
);

jsonResponse(true, 'Booking request submitted! Awaiting confirmation.', [
    'booking_id' => $bookingId,
    'booking_ref' => $realRef,
    'room_number' => $room['room_number'],
    'room_type' => $room['room_type'],
    'check_in' => $checkIn,
    'check_out' => $checkOut,
    'adults' => $adults,
    'children' => $children,
    'nights' => $pricing['nights'],
    'room_subtotal' => $pricing['room_subtotal'],
    'meal_subtotal' => $pricing['meal_subtotal'],
    'meals' => $pricing['meals'],
    'grand_total' => $pricing['grand_total'],
    'status' => 'pending',
]);
