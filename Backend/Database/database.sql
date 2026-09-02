-- ============================================================
-- LuxuryStay Hotel Management System — Database Schema v2
-- PHP + MySQL
-- ============================================================
-- This is the FULL schema for a fresh install (50 rooms).
-- If you already have a v1 database with real booking history,
-- do NOT run this file — use Database/migrations/001_upgrade_from_v1.sql
-- instead, which upgrades your existing data in place.
-- ============================================================

CREATE DATABASE IF NOT EXISTS luxurystay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luxurystay;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. admin
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    failed_login_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    last_login_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin -> username: admin | password: Admin@123
-- The password column below is a PLACEHOLDER, not a working hash.
-- After importing this file, open Backend/config/setup.php in your
-- browser ONCE (e.g. http://localhost:8000/Backend/config/setup.php)
-- to generate the real bcrypt hash via PHP's password_hash().
-- must_change_password forces a change on first login.
INSERT INTO admin (username, password, name, role, must_change_password) VALUES
('admin', 'CHANGE_ME_RUN_SETUP_PHP', 'Hotel Administrator', 'super_admin', 1);

-- ------------------------------------------------------------
-- 2. users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    failed_login_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. rooms
-- ------------------------------------------------------------
-- Status is a MANUAL override only ('available' / 'maintenance').
-- Real-time occupancy for a given date range is derived from the
-- bookings table (see Backend/services/AvailabilityService.php),
-- NOT from a permanent "occupied" flag. This is what makes
-- date-based availability (section 11 of the spec) work correctly.
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    room_type ENUM('Standard Room','Deluxe Room','Super Deluxe Room','Suite Room') NOT NULL,
    floor INT NOT NULL DEFAULT 1,
    description TEXT,
    amenities TEXT DEFAULT NULL COMMENT 'JSON array of amenity strings',
    price_per_night DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL DEFAULT 2,
    status ENUM('available','maintenance') NOT NULL DEFAULT 'available',
    cover_image VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'soft delete flag',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_type (room_type),
    INDEX idx_room_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. room_images (normalized - per-room gallery)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS room_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_room_images_room (room_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. meal_pricing (single-row-per-meal configurable pricing)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS meal_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_type ENUM('breakfast','lunch','dinner') NOT NULL UNIQUE,
    price_per_person DECIMAL(10,2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO meal_pricing (meal_type, price_per_person) VALUES
('breakfast', 300.00),
('lunch', 500.00),
('dinner', 500.00);

-- ------------------------------------------------------------
-- 6. bookings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_ref VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g. LS-000123',
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    adults INT NOT NULL DEFAULT 1,
    children INT NOT NULL DEFAULT 0,
    nights INT NOT NULL,
    room_price DECIMAL(10,2) NOT NULL COMMENT 'room subtotal = price_per_night * nights',
    meal_price DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'meal subtotal',
    total_price DECIMAL(10,2) NOT NULL COMMENT 'room_price + meal_price, backend-calculated',
    guest_name VARCHAR(100) NOT NULL,
    guest_email VARCHAR(150) NOT NULL,
    guest_phone VARCHAR(20) NOT NULL,
    status ENUM('pending','approved','rejected','checked_out','cancelled') NOT NULL DEFAULT 'pending',
    rejection_reason TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    CHECK (check_out > check_in),
    CHECK (adults >= 1),
    CHECK (children >= 0),
    INDEX idx_bookings_room_dates (room_id, check_in, check_out),
    INDEX idx_bookings_status (status),
    INDEX idx_bookings_user (user_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. booking_meals (normalized: which meals were selected)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS booking_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    meal_type ENUM('breakfast','lunch','dinner') NOT NULL,
    price_per_person DECIMAL(10,2) NOT NULL COMMENT 'price snapshot at time of booking',
    total_guests INT NOT NULL,
    days INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_booking_meal (booking_id, meal_type)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. contacts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 9. gallery (site-wide gallery + testimonials flag)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    category VARCHAR(50) DEFAULT 'general',
    testimonial_text TEXT DEFAULT NULL,
    testimonial_author VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 10. announcements ("Hotel Updates" / authority page)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('General','Important','Booking','Room','Maintenance','Policy') NOT NULL DEFAULT 'General',
    created_by INT NOT NULL COMMENT 'admin.id',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE CASCADE,
    INDEX idx_announcements_active (is_active, created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 11. notifications (in-app, per customer)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('booking_submitted','booking_approved','booking_rejected','booking_cancelled','announcement') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    related_booking_id INT DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    INDEX idx_notifications_user (user_id, is_read)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 12. activity_logs (admin audit trail)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'e.g. booking_approved, room_added',
    description VARCHAR(255) NOT NULL,
    target_type VARCHAR(50) DEFAULT NULL COMMENT 'e.g. booking, room, meal_pricing',
    target_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE SET NULL,
    INDEX idx_activity_logs_created (created_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Seed data lives in Database/seeds/ — run after this file:
--   Database/seeds/01_rooms_50.sql
--   Database/seeds/02_gallery_testimonials.sql
-- ============================================================
