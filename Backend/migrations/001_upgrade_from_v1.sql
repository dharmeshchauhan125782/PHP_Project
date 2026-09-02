-- ============================================================
-- LuxuryStay Migration 001: Upgrade v1 schema -> v2 schema
-- ============================================================
-- Run this ONCE against your EXISTING luxurystay database if you
-- already have real users/bookings you want to keep.
--
-- Usage:
--   mysql -u root -p luxurystay < Database/migrations/001_upgrade_from_v1.sql
--
-- This script is idempotent-ish: ALTER TABLE ... ADD COLUMN calls use
-- IF NOT EXISTS where MySQL supports it (8.0+). If you are on MySQL
-- 5.7, run this once only (re-running will error on duplicate columns).
-- ============================================================

USE luxurystay;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- STEP 1: admin table upgrades
-- ------------------------------------------------------------
ALTER TABLE admin
  ADD COLUMN IF NOT EXISTS role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin' AFTER name,
  ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER role,
  ADD COLUMN IF NOT EXISTS failed_login_attempts INT NOT NULL DEFAULT 0 AFTER must_change_password,
  ADD COLUMN IF NOT EXISTS locked_until DATETIME DEFAULT NULL AFTER failed_login_attempts,
  ADD COLUMN IF NOT EXISTS last_login_at DATETIME DEFAULT NULL AFTER locked_until;

UPDATE admin SET role = 'super_admin' WHERE id = (SELECT MIN(id) FROM (SELECT id FROM admin) x);

-- ------------------------------------------------------------
-- STEP 2: users table upgrades
-- ------------------------------------------------------------
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS failed_login_attempts INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS locked_until DATETIME DEFAULT NULL;

-- ------------------------------------------------------------
-- STEP 3: rooms table upgrades
-- ------------------------------------------------------------
ALTER TABLE rooms
  ADD COLUMN IF NOT EXISTS floor INT NOT NULL DEFAULT 1 AFTER room_type,
  ADD COLUMN IF NOT EXISTS amenities TEXT DEFAULT NULL AFTER description,
  ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Backfill floor from the first digit of room_number (e.g. '402' -> floor 4)
UPDATE rooms SET floor = CAST(LEFT(room_number, 1) AS UNSIGNED) WHERE floor = 1;

-- v1 used status = 'available' | 'occupied' | 'maintenance'.
-- v2 derives occupancy from bookings instead of a permanent flag, so
-- 'occupied' is no longer a valid room.status value. Convert any rooms
-- that were only "occupied" because of an old active booking back to
-- 'available' -- date-based availability will correctly re-block them
-- for any dates that still overlap an active booking.
ALTER TABLE rooms MODIFY COLUMN status ENUM('available','occupied','maintenance') NOT NULL DEFAULT 'available';
UPDATE rooms SET status = 'available' WHERE status = 'occupied';
ALTER TABLE rooms MODIFY COLUMN status ENUM('available','maintenance') NOT NULL DEFAULT 'available';

-- ------------------------------------------------------------
-- STEP 4: add the 40 new rooms to reach the full 50-room inventory
-- (existing room_numbers are preserved untouched via INSERT IGNORE,
-- since room_number is UNIQUE)
-- ------------------------------------------------------------
INSERT IGNORE INTO rooms (room_number, room_type, floor, description, amenities, price_per_night, capacity, status, cover_image) VALUES
('101', 'Standard Room', 1, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2800.00, 2, 'available', 'room-101.jpg'),
('102', 'Standard Room', 1, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2800.00, 2, 'available', 'room-102.jpg'),
('103', 'Standard Room', 1, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2800.00, 2, 'available', 'room-103.jpg'),
('104', 'Standard Room', 1, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2800.00, 2, 'available', 'room-104.jpg'),
('105', 'Standard Room', 1, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2800.00, 2, 'available', 'room-105.jpg'),
('106', 'Standard Room', 1, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2800.00, 2, 'available', 'room-106.jpg'),
('107', 'Standard Room', 1, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2800.00, 2, 'available', 'room-107.jpg'),
('108', 'Standard Room', 1, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2800.00, 2, 'available', 'room-108.jpg'),
('109', 'Standard Room', 1, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 3200.00, 3, 'available', 'room-109.jpg'),
('110', 'Standard Room', 1, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 3200.00, 3, 'available', 'room-110.jpg'),
('201', 'Standard Room', 2, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2950.00, 2, 'available', 'room-201.jpg'),
('202', 'Standard Room', 2, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2950.00, 2, 'available', 'room-202.jpg'),
('203', 'Standard Room', 2, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2950.00, 2, 'available', 'room-203.jpg'),
('204', 'Standard Room', 2, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2950.00, 2, 'available', 'room-204.jpg'),
('205', 'Standard Room', 2, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2950.00, 2, 'available', 'room-205.jpg'),
('206', 'Standard Room', 2, 'Comfortable and cozy room ideal for solo travellers and short stays, featuring a plush single/double bed, work desk, and city-facing window.', '["Free WiFi", "Air Conditioning", "Flat-screen TV", "Work Desk", "Daily Housekeeping"]', 2950.00, 2, 'available', 'room-206.jpg'),
('207', 'Deluxe Room', 2, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 4650.00, 3, 'available', 'room-207.jpg'),
('208', 'Deluxe Room', 2, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 4650.00, 3, 'available', 'room-208.jpg'),
('209', 'Deluxe Room', 2, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 5050.00, 4, 'available', 'room-209.jpg'),
('210', 'Deluxe Room', 2, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 5050.00, 4, 'available', 'room-210.jpg'),
('301', 'Deluxe Room', 3, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 4800.00, 3, 'available', 'room-301.jpg'),
('302', 'Deluxe Room', 3, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 4800.00, 3, 'available', 'room-302.jpg'),
('303', 'Deluxe Room', 3, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 4800.00, 3, 'available', 'room-303.jpg'),
('304', 'Deluxe Room', 3, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 4800.00, 3, 'available', 'room-304.jpg'),
('305', 'Deluxe Room', 3, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 4800.00, 3, 'available', 'room-305.jpg'),
('306', 'Deluxe Room', 3, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 4800.00, 3, 'available', 'room-306.jpg'),
('307', 'Deluxe Room', 3, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 4800.00, 3, 'available', 'room-307.jpg'),
('308', 'Deluxe Room', 3, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 4800.00, 3, 'available', 'room-308.jpg'),
('309', 'Deluxe Room', 3, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 5200.00, 4, 'available', 'room-309.jpg'),
('310', 'Deluxe Room', 3, 'Elegant deluxe room with a king-size bed, city view, premium linens, and a spacious sitting area for a relaxed stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "City View", "Rain Shower", "Daily Housekeeping"]', 5200.00, 4, 'available', 'room-310.jpg'),
('401', 'Super Deluxe Room', 4, 'Upgraded room with a dedicated lounge corner, skyline view, premium decor, and enhanced in-room amenities.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "Lounge Area", "Skyline View", "Rain Shower", "Bathtub", "Premium Toiletries"]', 7300.00, 4, 'available', 'room-401.jpg'),
('402', 'Super Deluxe Room', 4, 'Upgraded room with a dedicated lounge corner, skyline view, premium decor, and enhanced in-room amenities.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "Lounge Area", "Skyline View", "Rain Shower", "Bathtub", "Premium Toiletries"]', 7300.00, 4, 'available', 'room-402.jpg'),
('403', 'Super Deluxe Room', 4, 'Upgraded room with a dedicated lounge corner, skyline view, premium decor, and enhanced in-room amenities.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "Lounge Area", "Skyline View", "Rain Shower", "Bathtub", "Premium Toiletries"]', 7300.00, 4, 'available', 'room-403.jpg'),
('404', 'Super Deluxe Room', 4, 'Upgraded room with a dedicated lounge corner, skyline view, premium decor, and enhanced in-room amenities.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "Lounge Area", "Skyline View", "Rain Shower", "Bathtub", "Premium Toiletries"]', 7300.00, 4, 'available', 'room-404.jpg'),
('405', 'Super Deluxe Room', 4, 'Upgraded room with a dedicated lounge corner, skyline view, premium decor, and enhanced in-room amenities.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "Lounge Area", "Skyline View", "Rain Shower", "Bathtub", "Premium Toiletries"]', 7300.00, 4, 'available', 'room-405.jpg'),
('406', 'Super Deluxe Room', 4, 'Upgraded room with a dedicated lounge corner, skyline view, premium decor, and enhanced in-room amenities.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "Lounge Area", "Skyline View", "Rain Shower", "Bathtub", "Premium Toiletries"]', 7300.00, 4, 'available', 'room-406.jpg'),
('407', 'Super Deluxe Room', 4, 'Upgraded room with a dedicated lounge corner, skyline view, premium decor, and enhanced in-room amenities.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "Lounge Area", "Skyline View", "Rain Shower", "Bathtub", "Premium Toiletries"]', 7300.00, 4, 'available', 'room-407.jpg'),
('408', 'Super Deluxe Room', 4, 'Upgraded room with a dedicated lounge corner, skyline view, premium decor, and enhanced in-room amenities.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "Lounge Area", "Skyline View", "Rain Shower", "Bathtub", "Premium Toiletries"]', 7300.00, 4, 'available', 'room-408.jpg'),
('409', 'Super Deluxe Room', 4, 'Upgraded room with a dedicated lounge corner, skyline view, premium decor, and enhanced in-room amenities.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "Lounge Area", "Skyline View", "Rain Shower", "Bathtub", "Premium Toiletries"]', 7700.00, 5, 'available', 'room-409.jpg'),
('410', 'Super Deluxe Room', 4, 'Upgraded room with a dedicated lounge corner, skyline view, premium decor, and enhanced in-room amenities.', '["Free WiFi", "Air Conditioning", "Smart TV", "Mini Bar", "Lounge Area", "Skyline View", "Rain Shower", "Bathtub", "Premium Toiletries"]', 7700.00, 5, 'available', 'room-410.jpg'),
('501', 'Suite Room', 5, 'Spacious suite with a separate living area, private balcony, butler service, and panoramic views for the ultimate luxury stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Private Balcony", "Butler Service", "Jacuzzi", "Living Area", "Premium Toiletries", "Complimentary Breakfast Nook", "Panoramic View"]', 12800.00, 5, 'available', 'room-501.jpg'),
('502', 'Suite Room', 5, 'Spacious suite with a separate living area, private balcony, butler service, and panoramic views for the ultimate luxury stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Private Balcony", "Butler Service", "Jacuzzi", "Living Area", "Premium Toiletries", "Complimentary Breakfast Nook", "Panoramic View"]', 12800.00, 5, 'available', 'room-502.jpg'),
('503', 'Suite Room', 5, 'Spacious suite with a separate living area, private balcony, butler service, and panoramic views for the ultimate luxury stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Private Balcony", "Butler Service", "Jacuzzi", "Living Area", "Premium Toiletries", "Complimentary Breakfast Nook", "Panoramic View"]', 12800.00, 5, 'available', 'room-503.jpg'),
('504', 'Suite Room', 5, 'Spacious suite with a separate living area, private balcony, butler service, and panoramic views for the ultimate luxury stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Private Balcony", "Butler Service", "Jacuzzi", "Living Area", "Premium Toiletries", "Complimentary Breakfast Nook", "Panoramic View"]', 12800.00, 5, 'available', 'room-504.jpg'),
('505', 'Suite Room', 5, 'Spacious suite with a separate living area, private balcony, butler service, and panoramic views for the ultimate luxury stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Private Balcony", "Butler Service", "Jacuzzi", "Living Area", "Premium Toiletries", "Complimentary Breakfast Nook", "Panoramic View"]', 12800.00, 5, 'available', 'room-505.jpg'),
('506', 'Suite Room', 5, 'Spacious suite with a separate living area, private balcony, butler service, and panoramic views for the ultimate luxury stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Private Balcony", "Butler Service", "Jacuzzi", "Living Area", "Premium Toiletries", "Complimentary Breakfast Nook", "Panoramic View"]', 12800.00, 5, 'available', 'room-506.jpg'),
('507', 'Suite Room', 5, 'Spacious suite with a separate living area, private balcony, butler service, and panoramic views for the ultimate luxury stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Private Balcony", "Butler Service", "Jacuzzi", "Living Area", "Premium Toiletries", "Complimentary Breakfast Nook", "Panoramic View"]', 12800.00, 5, 'available', 'room-507.jpg'),
('508', 'Suite Room', 5, 'Spacious suite with a separate living area, private balcony, butler service, and panoramic views for the ultimate luxury stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Private Balcony", "Butler Service", "Jacuzzi", "Living Area", "Premium Toiletries", "Complimentary Breakfast Nook", "Panoramic View"]', 12800.00, 5, 'available', 'room-508.jpg'),
('509', 'Suite Room', 5, 'Spacious suite with a separate living area, private balcony, butler service, and panoramic views for the ultimate luxury stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Private Balcony", "Butler Service", "Jacuzzi", "Living Area", "Premium Toiletries", "Complimentary Breakfast Nook", "Panoramic View"]', 13200.00, 6, 'available', 'room-509.jpg'),
('510', 'Suite Room', 5, 'Spacious suite with a separate living area, private balcony, butler service, and panoramic views for the ultimate luxury stay.', '["Free WiFi", "Air Conditioning", "Smart TV", "Private Balcony", "Butler Service", "Jacuzzi", "Living Area", "Premium Toiletries", "Complimentary Breakfast Nook", "Panoramic View"]', 13200.00, 6, 'available', 'room-510.jpg');

-- ------------------------------------------------------------
-- STEP 5: room_images sort_order column
-- ------------------------------------------------------------
ALTER TABLE room_images
  ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 0;

-- ------------------------------------------------------------
-- STEP 6: meal_pricing table (new)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS meal_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_type ENUM('breakfast','lunch','dinner') NOT NULL UNIQUE,
    price_per_person DECIMAL(10,2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO meal_pricing (meal_type, price_per_person) VALUES
('breakfast', 300.00),
('lunch', 500.00),
('dinner', 500.00);

-- ------------------------------------------------------------
-- STEP 7: bookings table upgrades (THIS PRESERVES EXISTING ROWS)
-- ------------------------------------------------------------
ALTER TABLE bookings
  ADD COLUMN IF NOT EXISTS booking_ref VARCHAR(20) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS adults INT NOT NULL DEFAULT 1 AFTER check_out,
  ADD COLUMN IF NOT EXISTS children INT NOT NULL DEFAULT 0 AFTER adults,
  ADD COLUMN IF NOT EXISTS nights INT NOT NULL DEFAULT 1 AFTER children,
  ADD COLUMN IF NOT EXISTS room_price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER nights,
  ADD COLUMN IF NOT EXISTS meal_price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER room_price,
  ADD COLUMN IF NOT EXISTS guest_name VARCHAR(100) NOT NULL DEFAULT '' AFTER meal_price,
  ADD COLUMN IF NOT EXISTS guest_email VARCHAR(150) NOT NULL DEFAULT '' AFTER guest_name,
  ADD COLUMN IF NOT EXISTS guest_phone VARCHAR(20) NOT NULL DEFAULT '' AFTER guest_email,
  ADD COLUMN IF NOT EXISTS rejection_reason TEXT DEFAULT NULL AFTER status,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Backfill new columns from existing data so old bookings remain valid
UPDATE bookings b
  JOIN users u ON u.id = b.user_id
  SET
    b.adults = GREATEST(COALESCE(b.guests, 1), 1),
    b.children = 0,
    b.nights = GREATEST(DATEDIFF(b.check_out, b.check_in), 1),
    b.room_price = b.total_price,
    b.meal_price = 0,
    b.guest_name = u.name,
    b.guest_email = u.email,
    b.guest_phone = COALESCE(u.phone, '')
  WHERE b.room_price = 0;

-- Backfill unique booking_ref e.g. LS-000123
UPDATE bookings SET booking_ref = CONCAT('LS-', LPAD(id, 6, '0')) WHERE booking_ref IS NULL;

ALTER TABLE bookings MODIFY COLUMN booking_ref VARCHAR(20) NOT NULL;
-- Add UNIQUE index on booking_ref if not already present
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE table_schema = DATABASE() AND table_name = 'bookings' AND index_name = 'booking_ref');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE bookings ADD UNIQUE KEY booking_ref (booking_ref)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- v1 status enum had no 'checked_out'/'cancelled' distinctions the same way -- widen it
ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','approved','rejected','checked_out','cancelled') NOT NULL DEFAULT 'pending';

-- The old 'guests' column is superseded by adults+children but is kept
-- (not dropped) to avoid destroying data in case other code still reads it.

-- ------------------------------------------------------------
-- STEP 8: booking_meals table (new)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS booking_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    meal_type ENUM('breakfast','lunch','dinner') NOT NULL,
    price_per_person DECIMAL(10,2) NOT NULL,
    total_guests INT NOT NULL,
    days INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_booking_meal (booking_id, meal_type)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- STEP 9: announcements table (new)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('General','Important','Booking','Room','Maintenance','Policy') NOT NULL DEFAULT 'General',
    created_by INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- STEP 10: notifications table (new)
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
    FOREIGN KEY (related_booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- STEP 11: activity_logs table (new)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    target_type VARCHAR(50) DEFAULT NULL,
    target_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Done. Verify with:
--   SELECT COUNT(*) FROM rooms;               -- should be 50
--   SELECT COUNT(*) FROM bookings WHERE booking_ref IS NULL; -- should be 0
-- ------------------------------------------------------------
