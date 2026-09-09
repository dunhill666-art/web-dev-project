# AeroGlide — Philippine Airline & Getaway Booking Platform

AeroGlide is a modern domestic airline flight booking, hotel package, and car rental platform for the Philippines.

## Directory Structure

- `database/`
  - `config.php` — Central PDO database connection logic
  - `database.sql` — Schema and seed data for phpMyAdmin import
  - `function.php` — Global helper functions & auth state management
  - `validation.php` — Input sanitization & validation rules
  - `success.php` — Reusable success alert UI helpers
- `Login/`
  - `auth.php` — Core authentication request handler
  - `login.php` — User login form
  - `signup.php` — Registration form
  - `logout.php` — Session termination script
  - `forgot_password.php` — Password reset request form
  - `reset_password.php` — Token verification & password update form
- `User/`
  - `dashboard.php` — User trip dashboard & printable boarding pass viewer
- `admin/` — Administrative panel (Bookings, Users, Revenue reports, Activity logs)
- `place/` — Philippine destination detail pages
- Root files (`index.php`, `destination.php`, `checkout.php`, `help.php`, `style.css`, `script.js`)

## Quick Setup Guide

1. Start WAMP/XAMPP server with Apache & MySQL.
2. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
3. Create database named `aeroglide` and import `database/database.sql`.
4. Alternatively, visit `http://localhost/project/db_setup.php` in your browser.

## Credentials

- **Admin Login**: `admin` / `admin2026!`
- **Demo User**: `demo` / `password123`
