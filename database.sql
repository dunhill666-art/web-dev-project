-- ============================================================
-- AeroGlide Database SQL Schema Dump
-- Import this file into phpMyAdmin or MySQL server
-- Database: aeroglide
-- ============================================================

CREATE DATABASE IF NOT EXISTS `aeroglide` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `aeroglide`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(180) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `used_coupon` VARCHAR(30) NULL DEFAULT NULL COMMENT '1 coupon restriction per user',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `password_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `booking_ref` VARCHAR(20) NOT NULL UNIQUE,
  `booking_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traveler_name` VARCHAR(120) NOT NULL,
  `traveler_email` VARCHAR(180) NOT NULL,
  `traveler_phone` VARCHAR(30) NULL,
  `city` VARCHAR(120) NOT NULL,
  `mode` ENUM('flights','hotels','packages') NOT NULL DEFAULT 'flights',
  `adults` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `cabin` VARCHAR(40) NOT NULL DEFAULT 'Economy',
  `fare_name` VARCHAR(80) NULL,
  `fare_desc` VARCHAR(200) NULL,
  `flight_schedule` VARCHAR(200) NULL,
  `hotel_name` VARCHAR(120) NULL,
  `hotel_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `nights` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `car_name` VARCHAR(80) NULL,
  `car_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `days` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_num` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_label` VARCHAR(80) NULL,
  `grand_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cancellation_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cancelled_at` DATETIME NULL DEFAULT NULL,
  `promo_code` VARCHAR(30) NULL,
  `payment_method` VARCHAR(30) NULL,
  `status` ENUM('confirmed','pending','cancelled') NOT NULL DEFAULT 'confirmed',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_booking_date` (`booking_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `action` VARCHAR(200) NOT NULL,
  `target` VARCHAR(200) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
