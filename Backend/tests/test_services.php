<?php
/**
 * Manual verification script for PricingService and AvailabilityService.
 * Run with: php Backend/tests/test_services.php
 * Not exposed via web — for development verification only.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/PricingService.php';
require_once __DIR__ . '/../services/AvailabilityService.php';

function assertEqual($label, $actual, $expected) {
    $pass = (abs($actual - $expected) < 0.001);
    echo ($pass ? "PASS" : "FAIL") . " - $label: expected=$expected actual=$actual" . PHP_EOL;
    return $pass;
}

$allPass = true;

// ------------------------------------------------------------
// Test 1: Spec section 29 worked example
// Room price/night = 4500, nights = 3, adults=2, children=1
// Breakfast (300) + Dinner (500) selected, no lunch
// Expected: room_subtotal=13500, meal_subtotal=7200, grand_total=20700
// ------------------------------------------------------------
$mealPrices = ['breakfast' => 300.00, 'lunch' => 500.00, 'dinner' => 500.00];
$result = calculateBookingPrice(4500.00, 3, 2, 1, ['breakfast' => true, 'lunch' => false, 'dinner' => true], $mealPrices);
$allPass &= assertEqual('room_subtotal', $result['room_subtotal'], 13500.00);
$allPass &= assertEqual('meal_subtotal', $result['meal_subtotal'], 7200.00);
$allPass &= assertEqual('grand_total', $result['grand_total'], 20700.00);
$allPass &= assertEqual('total_guests', $result['total_guests'], 3);

// ------------------------------------------------------------
// Test 2: No meals selected
// ------------------------------------------------------------
$result2 = calculateBookingPrice(2800.00, 2, 1, 0, ['breakfast' => false, 'lunch' => false, 'dinner' => false], $mealPrices);
$allPass &= assertEqual('no-meals room_subtotal', $result2['room_subtotal'], 5600.00);
$allPass &= assertEqual('no-meals meal_subtotal', $result2['meal_subtotal'], 0.00);
$allPass &= assertEqual('no-meals grand_total', $result2['grand_total'], 5600.00);

// ------------------------------------------------------------
// Test 3: All meals selected
// price/night=6800, nights=4, adults=2, children=2 (4 guests)
// meal_subtotal = 4 guests * (300+500+500) * 4 nights = 4*1300*4=20800
// room_subtotal = 6800*4=27200
// grand_total = 48000
// ------------------------------------------------------------
$result3 = calculateBookingPrice(6800.00, 4, 2, 2, ['breakfast' => true, 'lunch' => true, 'dinner' => true], $mealPrices);
$allPass &= assertEqual('all-meals room_subtotal', $result3['room_subtotal'], 27200.00);
$allPass &= assertEqual('all-meals meal_subtotal', $result3['meal_subtotal'], 20800.00);
$allPass &= assertEqual('all-meals grand_total', $result3['grand_total'], 48000.00);

// ------------------------------------------------------------
// Test 4: dateRangesOverlap logic (spec section 11 example)
// Existing booking: 26 Aug - 29 Aug
// ------------------------------------------------------------
$existingIn = '2026-08-26';
$existingOut = '2026-08-29';

// 27-28 Aug should overlap (nested inside)
$overlap1 = dateRangesOverlap('2026-08-27', '2026-08-28', $existingIn, $existingOut);
echo (($overlap1 === true) ? "PASS" : "FAIL") . " - overlap 27-28 Aug inside 26-29 Aug should overlap" . PHP_EOL;
$allPass &= $overlap1;

// 30 Aug - 2 Sep should NOT overlap (starts after existing checkout)
$overlap2 = dateRangesOverlap('2026-08-30', '2026-09-02', $existingIn, $existingOut);
echo (($overlap2 === false) ? "PASS" : "FAIL") . " - 30 Aug-2 Sep after 26-29 Aug should NOT overlap" . PHP_EOL;
$allPass &= !$overlap2;

// Back-to-back: new check-in == existing check-out (29 Aug) should NOT overlap (half-open ranges)
$overlap3 = dateRangesOverlap('2026-08-29', '2026-08-31', $existingIn, $existingOut);
echo (($overlap3 === false) ? "PASS" : "FAIL") . " - back-to-back booking (check-in = existing check-out) should NOT overlap" . PHP_EOL;
$allPass &= !$overlap3;

// Partial overlap at the start: 24-27 Aug overlaps 26-29 Aug
$overlap4 = dateRangesOverlap('2026-08-24', '2026-08-27', $existingIn, $existingOut);
echo (($overlap4 === true) ? "PASS" : "FAIL") . " - 24-27 Aug partially overlapping 26-29 Aug should overlap" . PHP_EOL;
$allPass &= $overlap4;

// ------------------------------------------------------------
// Test 5: isRoomAvailable against real DB data
// This test seeds its own throwaway user + booking so it is not
// dependent on any other script having run first, and cleans up
// after itself.
// ------------------------------------------------------------
try {
    $pdo = getDB();

    $stmt = $pdo->query("SELECT id FROM rooms WHERE room_number = '101' LIMIT 1");
    $roomId = $stmt->fetchColumn();
    if (!$roomId) {
        throw new Exception('Room 101 not found - run Database/seeds/01_rooms_50.sql first.');
    }

    // Seed a throwaway test user + an approved booking for room 101 (2026-09-01..2026-09-03)
    $pdo->exec("DELETE FROM users WHERE email = 'test-services-runner@luxurystay.local'");
    $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)")
        ->execute(['Test Runner', 'test-services-runner@luxurystay.local', password_hash('x', PASSWORD_BCRYPT), '0000000000']);
    $testUserId = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO bookings (booking_ref, user_id, room_id, check_in, check_out, adults, children, nights, room_price, meal_price, total_price, guest_name, guest_email, guest_phone, status)
        VALUES ('TEST-TMP', ?, ?, '2026-09-01', '2026-09-03', 1, 0, 2, 100, 0, 100, 'Test Runner', 'test-services-runner@luxurystay.local', '0000000000', 'approved')")
        ->execute([$testUserId, $roomId]);
    $testBookingId = $pdo->lastInsertId();

    $available_overlap = isRoomAvailable($pdo, $roomId, '2026-09-02', '2026-09-04');
    echo (($available_overlap === false) ? "PASS" : "FAIL") . " - room 101 should be UNAVAILABLE for overlapping 2026-09-02..04" . PHP_EOL;
    $allPass &= !$available_overlap;

    $available_clear = isRoomAvailable($pdo, $roomId, '2026-09-10', '2026-09-12');
    echo (($available_clear === true) ? "PASS" : "FAIL") . " - room 101 should be AVAILABLE for non-overlapping 2026-09-10..12" . PHP_EOL;
    $allPass &= $available_clear;

    // Cleanup
    $pdo->prepare("DELETE FROM bookings WHERE id = ?")->execute([$testBookingId]);
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$testUserId]);
} catch (Exception $e) {
    echo "FAIL - DB test threw exception: " . $e->getMessage() . PHP_EOL;
    $allPass = false;
}

echo PHP_EOL . ($allPass ? "=== ALL TESTS PASSED ===" : "=== SOME TESTS FAILED ===") . PHP_EOL;
exit($allPass ? 0 : 1);
