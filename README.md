# Luxury Stay — Hotel Reservation Management System

A full-stack hotel reservation platform built entirely with **PHP 8, MySQL, and React (CDN + Babel, buildless)**.

> **Note on tech stack:** The original plan referenced a PHP + Node.js dual-backend design.
> This build implements everything as a **single PHP/MySQL backend** — no Node.js anywhere —
> since the project subject is PHP. The "live room availability" feature that was planned as a
> Node.js companion microservice is implemented natively in `api/rooms_search.php` instead.

## Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript (ES6), React 18 (via CDN + Babel Standalone — no build step, no npm required)
- **Backend:** PHP 8.x (PDO, sessions, bcrypt password hashing)
- **Database:** MySQL / MariaDB — 7 tables (`admin`, `users`, `rooms`, `room_images`, `bookings`, `contacts`, `gallery`)
- **Tools:** XAMPP, phpMyAdmin, VS Code

## Project Structure

```
luxurystay/
├── admin/                  Admin panel (login, dashboard SPA, API endpoints)
│   ├── api/                 rooms/users/bookings/gallery management, admin auth
│   ├── login.php
│   └── dashboard.php
├── api/                    Public + user-facing API endpoints
│   ├── register.php / login.php / logout.php
│   ├── rooms_list.php / rooms_search.php
│   ├── booking_create.php / booking_list.php / booking_cancel.php
│   ├── profile.php
│   ├── contact_submit.php
│   └── gallery_list.php
├── assets/
│   ├── css/style.css        Design system (Royal Blue + Gold, Cormorant Garamond + Jost)
│   ├── js/                  React components (home, rooms, dashboard, admin) + api.js helper
│   └── uploads/              Uploaded room & gallery images (writable)
├── config/db.php            Database connection (PDO)
├── includes/functions.php   Session, sanitization, JSON response helpers
├── sql/database.sql         Full schema + seed data
├── index.php                Homepage
├── login.php / register.php
├── dashboard.php            User panel (profile, password, booking history)
├── rooms.php                Browse & book rooms
└── setup.php                One-time script to generate the admin password hash
```

## Setup on XAMPP (Windows/Mac/Linux)

1. **Copy the project** into your XAMPP `htdocs` folder, e.g. `C:\xampp\htdocs\luxurystay`.
2. **Start Apache and MySQL** from the XAMPP control panel.
3. **Create the database:** open phpMyAdmin (`http://localhost/phpmyadmin`), click *Import*, and select `sql/database.sql`. This creates the `luxurystay` database with all 7 tables and sample rooms.
4. **Check `config/db.php`** — the defaults (`localhost` / `root` / no password) match a stock XAMPP install. Update if your MySQL credentials differ.
5. **Run the one-time setup script** by visiting `http://localhost/luxurystay/setup.php` in your browser. This generates a correct bcrypt hash for the default admin password using PHP's own `password_hash()` function (guarantees it works regardless of environment). **Delete `setup.php` afterward.**
6. **Visit the site:** `http://localhost/luxurystay/index.php`

### Default Admin Login
- URL: `http://localhost/luxurystay/admin/login.php`
- Username: `admin`
- Password: `Admin@123`

*(Change this password immediately after your first login — you can add a "change admin password" field to `admin/api/login.php`'s companion profile flow, or update it directly in phpMyAdmin using `password_hash()`.)*

## Alternative: PHP Built-in Server

If you don't want to use XAMPP's Apache, you can run:

```bash
php -S localhost:8000
```

from the project root (make sure MySQL/MariaDB is running separately and `config/db.php` points to it).

## Features Implemented

**Guest-facing:**
- Homepage with hero, live room availability search, featured rooms, gallery, testimonials, contact form
- Register / login (bcrypt-hashed passwords, session-based auth)
- Browse rooms with live search by date range & guest count (double-booking prevention via DB transaction + row locking)
- User dashboard: edit profile, change password, view & cancel bookings

**Admin panel:**
- Live stats dashboard (rooms, bookings, users, revenue)
- Booking approvals: approve / reject / delete
- Full room CRUD with cover image + per-room image gallery upload
- User management (search, remove)
- Gallery & testimonial management

**Security:**
- Bcrypt password hashing, session regeneration on login
- PDO prepared statements everywhere (no SQL injection surface)
- Input sanitization on all form fields
- File upload validation (extension allow-list, 5MB size limit)
- Separate `admin` vs `users` session namespaces so guest and admin auth never cross

## Notes

- Sample room images use royalty-free Unsplash URLs as placeholders until you upload real photos via the admin panel.
- The `assets/uploads/` folders must be writable by the web server (`chmod 755` on Linux/Mac; XAMPP on Windows works out of the box).
