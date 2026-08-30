# LuxuryStay Hotel Management System

A full-stack hotel booking and management platform: PHP + MySQL backend, vanilla JS/HTML/CSS frontend, 50-room inventory, date-based availability, meal add-ons, and a complete admin panel.

---

## 1. Project Structure

```
LuxuryStay/
├── Frontend/           Customer-facing website
│   ├── pages/           index, rooms, room-details, login, register, dashboard, gallery, contact, updates
│   ├── components/       header, footer, booking-modal (shared PHP includes)
│   ├── css/style.css     design system (tokens, components, responsive)
│   ├── js/                api.js (fetch/CSRF/toast helpers), booking.js (booking modal logic)
│   └── images/rooms/      50 rooms × 3 images each (generated — see section 6)
│
├── Backend/             All PHP logic — nothing here is publicly browsable content,
│   │                    it's pure API/service code
│   ├── api/               customer + admin REST-ish JSON endpoints
│   ├── auth/               admin login/logout/change-password
│   ├── services/            AvailabilityService.php, PricingService.php, UploadService.php
│   ├── config/               db.php (credentials), app.php (constants), setup.php (one-time)
│   ├── includes/             functions.php — session bootstrap, CSRF, sanitizers, auth guards
│   ├── scripts/               generate_room_images.php
│   └── tests/                  test_services.php (pricing + availability unit tests)
│
├── Database/
│   ├── database.sql        full fresh-install schema (run this for a NEW install)
│   ├── migrations/           001_upgrade_from_v1.sql (run this ONLY if upgrading an existing v1 DB)
│   └── seeds/                 01_rooms_50.sql, 02_gallery_testimonials.sql
│
├── Admin/                Admin panel (separate login from the customer site)
│   ├── pages/              dashboard, bookings, rooms, customers, gallery, meal-pricing,
│   │                        announcements, activity-logs, settings, login
│   └── components/sidebar.php
│
└── README.md            (this file)
```

---

## 2. Requirements

- PHP 8.1+ with extensions: `pdo_mysql`, `gd`, `mbstring`, `fileinfo`
- MySQL 5.7+ or MariaDB 10.4+
- Any local server stack works: XAMPP, WAMP, MAMP, or PHP's built-in server

---

## 3. Setting Up the Database

### Fresh install (new hotel, no existing data)

```bash
mysql -u root -p -e "CREATE DATABASE luxurystay"
mysql -u root -p luxurystay < Database/database.sql
mysql -u root -p luxurystay < Database/seeds/01_rooms_50.sql
mysql -u root -p luxurystay < Database/seeds/02_gallery_testimonials.sql
```

This creates all 12 tables and seeds exactly 50 rooms (16 Standard, 14 Deluxe, 10 Super Deluxe, 10 Suite across 5 floors) plus 3 guest testimonials.

### Upgrading an existing v1 database (you have real bookings you want to keep)

**Do not** run `database.sql` on top of existing data. Instead:

```bash
mysql -u root -p luxurystay < Database/migrations/001_upgrade_from_v1.sql
```

This adds all new columns/tables (meals, adults/children, rejection reasons, announcements, notifications, activity logs) to your existing schema, backfills sensible defaults for old bookings, and adds the 40 new rooms needed to reach 50 — all without deleting any existing booking history.

---

## 4. Configure the Database Connection

Edit `Backend/config/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'luxurystay');
define('DB_USER', 'root');
define('DB_PASS', '');   // your MySQL password
```

---

## 5. Running the Project

### Option A — PHP's built-in server (quickest for local testing)

From the `LuxuryStay/` folder:

```bash
php -S localhost:8000
```

Then visit:
- Customer site: `http://localhost:8000/Frontend/pages/index.php`
- Admin panel: `http://localhost:8000/Admin/pages/login.php`

### Option B — XAMPP / WAMP / MAMP

Copy the entire `LuxuryStay/` folder into your `htdocs` (XAMPP) or `www` (WAMP) directory, start Apache + MySQL from the control panel, then visit `http://localhost/LuxuryStay/Frontend/pages/index.php`.

---

## 6. First-Time Admin Setup (required, one-time)

The seeded default admin account has a placeholder password that must be converted into a real bcrypt hash before you can log in. **Do this once, right after importing the database:**

1. Visit `http://localhost:8000/Backend/config/setup.php` in your browser.
2. You'll see confirmation of the default login:
   - **Username:** `admin`
   - **Password:** `Admin@123`
3. Go to `http://localhost:8000/Admin/pages/login.php` and log in with those credentials.
4. You will immediately be prompted to set a new password — this is required before you can use the admin panel.
5. **For security, delete or restrict access to `Backend/config/setup.php` after this step.** Running it again is harmless (it refuses to overwrite an existing password), but it's good practice to remove it from a production deployment.

---

## 7. Generating Room Images

50 rooms × 3 images (1 cover + 2 gallery) are already generated and included in `Frontend/images/rooms/`. If you ever need to regenerate them (e.g. after clearing the folder):

```bash
php Backend/scripts/generate_room_images.php
```

This produces 150 unique, on-brand images — every room has a genuinely different image, not a repeated placeholder. If you have real hotel photography, simply replace the files in `Frontend/images/rooms/` using the same filenames referenced in the `rooms` and `room_images` tables (`room-<number>.jpg`, `room-<number>-2.jpg`, `room-<number>-3.jpg`).

---

## 8. Adding / Editing Rooms

From the admin panel: **Admin → Rooms → + Add Room** (or **Edit** on any existing row).

You can set room number, type, floor, price, capacity, status (available/maintenance), description, amenities (add/remove freely), a cover image, and additional gallery images. Uploads are validated server-side for real file type, size (max 5MB), and dimensions — disguised executables are rejected even if renamed to `.jpg`.

Removing a room is a **soft delete** (it's hidden from customer listings but its booking history is preserved for reporting).

---

## 9. Configuring Meal Prices

**Admin → Meal Pricing.** Set breakfast/lunch/dinner price-per-person-per-day. Changes take effect immediately for all new bookings — nothing is hardcoded in the frontend.

---

## 10. How Pricing & Availability Work

- **Availability** is never a permanent "occupied" flag on a room. A room is unavailable for a date range only if it has another *pending* or *approved* booking whose dates overlap. This means a room correctly becomes bookable again the moment an overlapping booking is rejected or cancelled — no manual admin intervention needed.
- **Pricing** is always calculated server-side from the database's current room price and meal prices — the backend never trusts a total sent from the browser. Formula:
  ```
  room_subtotal = price_per_night × nights
  meal_subtotal = Σ (total_guests × meal_price × nights) for each selected meal
  grand_total   = room_subtotal + meal_subtotal
  ```

---

## 11. Security Notes

- Passwords hashed with `password_hash()` / verified with `password_verify()` — never stored in plaintext.
- Both customer and admin logins lock out for 15 minutes after 5 failed attempts.
- CSRF tokens required on every state-changing request.
- All database queries use prepared statements.
- File uploads are validated by actual file content (MIME sniffing + `getimagesize()`), not just the filename extension.
- User-submitted text is stored raw and escaped only at the point of output (in both PHP templates and JS rendering) — this is the standard, correct pattern that avoids data corruption and double-escaping bugs.

---

## 12. Running the Test Suite

A small PHP test script verifies the pricing formula and date-overlap availability logic against a live database:

```bash
php Backend/tests/test_services.php
```

---

## 13. Default Ports / URLs Reference

| What | URL |
|---|---|
| Customer homepage | `/Frontend/pages/index.php` |
| Customer login | `/Frontend/pages/login.php` |
| Customer dashboard | `/Frontend/pages/dashboard.php` |
| Admin login | `/Admin/pages/login.php` |
| Admin dashboard | `/Admin/pages/dashboard.php` |
| One-time admin setup | `/Backend/config/setup.php` |

---

Built as a full restructure and upgrade of the original LuxuryStay project: reorganized into Frontend/Backend/Database/Admin, redesigned UI, 50-room inventory with unique imagery, adults/children + meal pricing, date-based availability, admin rejection-reason workflow, hardened security, and a complete admin management panel.
