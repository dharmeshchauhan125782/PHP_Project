<?php
/**
 * Generates 150 unique room images (1 cover + 2 gallery per room x 50 rooms)
 * using GD. Since real hotel photography isn't available in this
 * environment, each image is a distinct, brand-consistent "room key
 * card" graphic — not a generic gray placeholder — built from the
 * site's own design tokens (pine/stone/brass) so it looks like a
 * deliberate visual rather than a broken image.
 *
 * Each room gets a unique appearance derived deterministically from its
 * room number (so re-running this script is idempotent), varying:
 *   - background gradient angle + palette (by room type)
 *   - decorative concentric ring positions (echoes the site's key-tag motif)
 *   - the large room number typography placement
 * The 3 images per room (cover, -2, -3) use different compositions so
 * a room's gallery doesn't look like 3 copies of one image.
 *
 * Run with: php Backend/scripts/generate_room_images.php
 */

$outDir = __DIR__ . '/../../Frontend/images/rooms';
if (!is_dir($outDir)) mkdir($outDir, 0755, true);

$FONT_MONO_BOLD = '/tmp/mono-bold.ttf';
$FONT_MONO_REG  = '/tmp/mono-reg.ttf';
$FONT_SERIF     = '/tmp/serif.ttf';
$FONT_SERIF_IT  = '/tmp/serif-italic.ttf';

foreach ([$FONT_MONO_BOLD, $FONT_MONO_REG, $FONT_SERIF, $FONT_SERIF_IT] as $f) {
    if (!file_exists($f)) { fwrite(STDERR, "Missing font: $f\n"); exit(1); }
}

// Palettes per room type: [bg1, bg2, accent, accent_soft]
// Chosen so all four types are visually distinct at a glance, not just
// subtly different shades of the same green.
$PALETTES = [
    'Standard Room' => [[0x4A, 0x7A, 0x5E], [0x2A, 0x4E, 0x3A], [0xD8, 0xC4, 0xA0], [0xF6, 0xF3, 0xEC]],
    'Deluxe Room' => [[0x3C, 0x6E, 0x52], [0x0F, 0x2A, 0x22], [0xC9, 0xA0, 0x5C], [0xEF, 0xE9, 0xDD]],
    'Super Deluxe Room' => [[0x8A, 0x66, 0x36], [0x4A, 0x35, 0x1C], [0x0F, 0x2A, 0x22], [0xF6, 0xF3, 0xEC]],
    'Suite Room' => [[0x1B, 0x1B, 0x18], [0x0A, 0x1D, 0x17], [0xA6, 0x7C, 0x42], [0xD8, 0xC4, 0xA0]],
];

// Deterministic PRNG seeded by room number so output is stable across reruns
function seededRand($seed, $min, $max) {
    $h = crc32($seed);
    mt_srand($h);
    $val = mt_rand($min, $max);
    mt_srand(); // reset global state
    return $val;
}

function hexColor($im, $rgb, $alpha = 0) {
    if ($alpha > 0) return imagecolorallocatealpha($im, $rgb[0], $rgb[1], $rgb[2], $alpha);
    return imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
}

function lerp($a, $b, $t) { return $a + ($b - $a) * $t; }

function drawGradient($im, $w, $h, $c1, $c2, $angleVariant) {
    // Simple diagonal gradient; angleVariant flips direction for variety
    for ($y = 0; $y < $h; $y++) {
        $t = $y / $h;
        if ($angleVariant === 1) $t = 1 - $t;
        if ($angleVariant === 2) $t = abs(($y / $h) - 0.5) * 2;
        $r = (int)lerp($c1[0], $c2[0], $t);
        $g = (int)lerp($c1[1], $c2[1], $t);
        $b = (int)lerp($c1[2], $c2[2], $t);
        $color = imagecolorallocate($im, $r, $g, $b);
        imageline($im, 0, $y, $w, $y, $color);
    }
}

function drawRing($im, $cx, $cy, $r, $color, $thickness = 2) {
    for ($t = 0; $t < $thickness; $t++) {
        imageellipse($im, $cx, $cy, ($r + $t) * 2, ($r + $t) * 2, $color);
    }
}

function generateRoomImage($outPath, $roomNumber, $roomType, $floor, $variant, $palettes, $fonts) {
    $w = 800; $h = 600;
    $im = imagecreatetruecolor($w, $h);
    imageantialias($im, true);

    $palette = $palettes[$roomType] ?? $palettes['Standard Room'];
    [$bg1, $bg2, $accent, $accentSoft] = $palette;

    $seed = $roomNumber . '-' . $variant;
    $angleVariant = seededRand($seed . 'angle', 0, 2);
    drawGradient($im, $w, $h, $bg1, $bg2, $angleVariant);

    // Decorative concentric rings (echoes the site's key-tag ring motif),
    // position + size derived deterministically from the room number.
    $accentColorSoft = hexColor($im, $accent, 100);
    $numRings = seededRand($seed . 'rings', 2, 4);
    for ($i = 0; $i < $numRings; $i++) {
        $cx = seededRand($seed . 'cx' . $i, 0, $w);
        $cy = seededRand($seed . 'cy' . $i, 0, $h);
        $r = seededRand($seed . 'r' . $i, 60, 220);
        drawRing($im, $cx, $cy, $r, $accentColorSoft, 1);
    }

    // Subtle vignette for depth
    $vignette = imagecolorallocatealpha($im, 0, 0, 0, 100);
    imagefilledrectangle($im, 0, 0, $w, 40, $vignette);
    imagefilledrectangle($im, 0, $h - 100, $w, $h, $vignette);

    // Thin brass corner rule (matches site's hairline rule detail)
    $accentColor = hexColor($im, $accent);
    imagesetthickness($im, 2);
    imageline($im, 40, $h - 30, 100, $h - 30, $accentColor);

    // Large room number, bottom-left, mono bold (matches .key-tag typography)
    $numberColor = hexColor($im, $accentSoft);
    imagettftext($im, 46, 0, 40, $h - 55, $numberColor, $fonts['mono_bold'], $roomNumber);

    // Room type label, small mono, uppercase, comfortably above the number
    // (imagettftext's y coordinate is the text BASELINE, and the "101"
    // number's ascenders extend well above its own baseline at h-55, so
    // the label needs real clearance above that, not just a few px).
    $typeLabel = strtoupper($roomType);
    $typeColor = hexColor($im, $accent);
    imagettftext($im, 13, 0, 40, $h - 120, $typeColor, $fonts['mono_reg'], $typeLabel);

    // Floor indicator, top-right
    $floorLabel = "FLOOR $floor";
    $floorColor = hexColor($im, $accentSoft, 20);
    imagettftext($im, 12, 0, $w - 130, 46, $floorColor, $fonts['mono_reg'], $floorLabel);

    // Brand mark, top-left, serif italic (evokes the site's Fraunces display font)
    $brandColor = hexColor($im, $accentSoft, 10);
    imagettftext($im, 22, 0, 40, 56, $brandColor, $fonts['serif_italic'], 'LuxuryStay');

    imagejpeg($im, $outPath, 88);
    imagedestroy($im);
}

// ---- Room plan (must match Database/seeds/01_rooms_50.sql) ----
$plan = [];
for ($n = 101; $n <= 110; $n++) $plan[] = [$n, 'Standard Room'];
for ($n = 201; $n <= 206; $n++) $plan[] = [$n, 'Standard Room'];
for ($n = 207; $n <= 210; $n++) $plan[] = [$n, 'Deluxe Room'];
for ($n = 301; $n <= 310; $n++) $plan[] = [$n, 'Deluxe Room'];
for ($n = 401; $n <= 410; $n++) $plan[] = [$n, 'Super Deluxe Room'];
for ($n = 501; $n <= 510; $n++) $plan[] = [$n, 'Suite Room'];

if (count($plan) !== 50) { fwrite(STDERR, "Room plan count mismatch: " . count($plan) . "\n"); exit(1); }

$fonts = ['mono_bold' => $FONT_MONO_BOLD, 'mono_reg' => $FONT_MONO_REG, 'serif_italic' => $FONT_SERIF_IT];

$generated = 0;
foreach ($plan as [$num, $type]) {
    $floor = (int)substr((string)$num, 0, 1);
    generateRoomImage("$outDir/room-$num.jpg", (string)$num, $type, $floor, 'cover', $PALETTES, $fonts);
    generateRoomImage("$outDir/room-$num-2.jpg", (string)$num, $type, $floor, 'gallery2', $PALETTES, $fonts);
    generateRoomImage("$outDir/room-$num-3.jpg", (string)$num, $type, $floor, 'gallery3', $PALETTES, $fonts);
    $generated += 3;
}

echo "Generated $generated images for " . count($plan) . " rooms in $outDir\n";
