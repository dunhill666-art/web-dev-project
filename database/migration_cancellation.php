<?php
// ============================================================
// AeroGlide — database/migration_cancellation.php
// Migration for pending_cancellation status and admin-mediated workflow
// ============================================================

require_once __DIR__ . '/config.php';

try {
    $db = ag_db();
    
    // Modify status ENUM
    $db->exec("ALTER TABLE `bookings` MODIFY COLUMN `status` ENUM('confirmed','pending','cancelled','pending_cancellation') NOT NULL DEFAULT 'confirmed'");
    
    // Add supplementary columns if not exist
    try { $db->exec("ALTER TABLE `bookings` ADD COLUMN `cancellation_reason` VARCHAR(255) NULL AFTER `status`"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE `bookings` ADD COLUMN `admin_notes` TEXT NULL AFTER `cancellation_reason`"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE `bookings` ADD COLUMN `cancellation_requested_at` DATETIME NULL AFTER `admin_notes`"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE `bookings` ADD COLUMN `cancellation_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `grand_total`"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE `bookings` ADD COLUMN `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `cancellation_fee`"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE `bookings` ADD COLUMN `cancelled_at` DATETIME NULL DEFAULT NULL AFTER `refund_amount`"); } catch (Exception $e) {}
    
    echo "✅ Bookings table successfully updated with pending_cancellation status and audit columns.\n";
} catch (Exception $e) {
    echo "❌ Migration error: " . $e->getMessage() . "\n";
}
