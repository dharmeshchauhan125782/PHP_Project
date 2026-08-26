-- ============================================================
-- Luxury Stay Hotel Reservation System
-- Database Schema (PHP + MySQL) - 7 Tables
-- ============================================================

CREATE DATABASE IF NOT EXISTS luxurystay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luxurystay;

-- ------------------------------------------------------------
-- 1. admin
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin -> username: admin | password: Admin@123
-- The password column below is a PLACEHOLDER, not a working hash.
-- After importing this file, open setup.php in your browser ONCE
-- (e.g. http://localhost:8000/setup.php) to generate the real bcrypt
-- hash for "Admin@123" via PHP's own password_hash() function.
INSERT INTO admin (username, password, name) VALUES
('admin', 'CHANGE_ME_RUN_SETUP_PHP', 'Hotel Administrator');

-- ------------------------------------------------------------
-- 2. users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE CHECK (email LIKE '%_@__%.__%'),
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. rooms
-- ------------------------------------------------------------
-- Room_Type is restricted to the 4 official categories.
-- Status is Available / Occupied, updated automatically whenever a booking is
-- approved (-> Occupied) or checked out / rejected / cancelled (-> Available).
-- 'maintenance' is kept as an optional manual override for rooms taken
-- offline by the admin (e.g. repairs) and does not count as Available.
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,               -- Room_ID
    room_number VARCHAR(20) NOT NULL UNIQUE,          -- Room_Number
    room_type ENUM('Standard Room','Deluxe Room','Super Deluxe Room','Suite Room') NOT NULL, -- Room_Type
    description TEXT,
    price_per_night DECIMAL(10,2) NOT NULL,           -- Price
    capacity INT NOT NULL DEFAULT 2,
    status ENUM('available','occupied','maintenance') NOT NULL DEFAULT 'available', -- Status
    cover_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. room_images (normalized - per-room gallery)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS room_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. bookings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending','approved','checked_out','rejected','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    CHECK (check_out > check_in)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. contacts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. gallery (site-wide gallery + testimonials flag)
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
-- Seed rooms — fixed total of 10 rooms across the 4 categories
-- ------------------------------------------------------------
-- cover_image is left NULL; the frontend shows an elegant placeholder photo
-- until the admin uploads real room photos via the Rooms panel.
-- All rooms start 'available', so Available Rooms = 10, Occupied = 0.
INSERT INTO rooms (room_number, room_type, description, price_per_night, capacity, status, cover_image) VALUES
('101', 'Standard Room', 'Comfortable and cozy room ideal for solo travellers and short stays.', 2800.00, 1, 'available', NULL),
('102', 'Standard Room', 'Comfortable and cozy room ideal for solo travellers and short stays.', 2800.00, 1, 'available', NULL),
('103', 'Standard Room', 'Comfortable and cozy room ideal for solo travellers and short stays.', 2800.00, 2, 'available', NULL),
('201', 'Deluxe Room', 'Elegant deluxe room with king-size bed, city view, and premium amenities.', 4500.00, 2, 'available', NULL),
('202', 'Deluxe Room', 'Elegant deluxe room with king-size bed, city view, and premium amenities.', 4500.00, 2, 'available', NULL),
('203', 'Deluxe Room', 'Elegant deluxe room with king-size bed, city view, and premium amenities.', 4500.00, 3, 'available', NULL),
('301', 'Super Deluxe Room', 'Upgraded deluxe room with a lounge corner, skyline view, and premium decor.', 6800.00, 3, 'available', NULL),
('302', 'Super Deluxe Room', 'Upgraded deluxe room with a lounge corner, skyline view, and premium decor.', 6800.00, 3, 'available', NULL),
('401', 'Suite Room', 'Spacious suite with a separate living area, private balcony, and butler service.', 12000.00, 4, 'available', NULL),
('402', 'Suite Room', 'Our finest suite featuring a jacuzzi, panoramic views, and private dining.', 15000.00, 4, 'available', NULL);

-- Seed testimonials (stored in gallery table with testimonial fields)
INSERT INTO gallery (title, category, testimonial_text, testimonial_author) VALUES
('Guest Testimonial', 'testimonial', 'An unforgettable stay — the staff went above and beyond and the Royal Suite was breathtaking.', 'Ananya Shah'),
('Guest Testimonial', 'testimonial', 'Booking was effortless and the room quality exceeded every expectation. Truly a luxury experience.', 'Rohan Mehta'),
('Guest Testimonial', 'testimonial', 'From check-in to check-out, everything felt seamless and elegant. We will be back!', 'Priya Nair');
