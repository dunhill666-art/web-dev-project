<?php
// ============================================================
// AeroGlide Database Setup Script
// Run ONCE to create the MySQL database, tables, and seed data.
// Visit: http://localhost/project/db_setup.php
// ============================================================

// ── PDO connection (no DB selected yet so we can CREATE DATABASE) ──
$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbName = 'aeroglide';

try {
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("❌ Connection failed: " . htmlspecialchars($e->getMessage()));
}

$log = [];
$error = null;

try {
    // 1. Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");
    $log[] = "✅ Database <strong>aeroglide</strong> created / confirmed.";

    // 2. USERS table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id`            INT AUTO_INCREMENT PRIMARY KEY,
            `username`      VARCHAR(60)  NOT NULL UNIQUE,
            `name`          VARCHAR(120) NOT NULL,
            `email`         VARCHAR(180) NOT NULL UNIQUE,
            `password`      VARCHAR(255) NOT NULL,
            `role`          ENUM('user','admin') NOT NULL DEFAULT 'user',
            `used_coupon`   VARCHAR(30)  NULL DEFAULT NULL COMMENT '1 coupon per account',
            `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `last_login`    DATETIME     NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $log[] = "✅ Table <strong>users</strong> ready.";

    // 3. PASSWORD RESET TOKENS table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `password_tokens` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`    INT NOT NULL,
            `token`      VARCHAR(64)  NOT NULL UNIQUE,
            `expires_at` DATETIME     NOT NULL,
            `used`       TINYINT(1)   NOT NULL DEFAULT 0,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $log[] = "✅ Table <strong>password_tokens</strong> ready.";

    // 4. BOOKINGS table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `bookings` (
            `id`              INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`         INT NOT NULL,
            `booking_ref`     VARCHAR(20)  NOT NULL UNIQUE,
            `booking_date`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `traveler_name`   VARCHAR(120) NOT NULL,
            `traveler_email`  VARCHAR(180) NOT NULL,
            `traveler_phone`  VARCHAR(30)  NULL,
            `city`            VARCHAR(120) NOT NULL,
            `mode`            ENUM('flights','hotels','packages') NOT NULL DEFAULT 'flights',
            `adults`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `cabin`           VARCHAR(40)  NOT NULL DEFAULT 'Economy',
            `fare_name`       VARCHAR(80)  NULL,
            `fare_desc`       VARCHAR(200) NULL,
            `flight_schedule` VARCHAR(200) NULL,
            `hotel_name`      VARCHAR(120) NULL,
            `hotel_price`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `nights`          TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `car_name`        VARCHAR(80)  NULL,
            `car_price`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `days`            TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `discount_num`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `discount_label`  VARCHAR(80)  NULL,
            `grand_total`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `cancellation_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `refund_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `cancelled_at`    DATETIME NULL DEFAULT NULL,
            `promo_code`      VARCHAR(30)  NULL,
            `payment_method`  VARCHAR(30)  NULL,
            `status`          ENUM('confirmed','pending','cancelled') NOT NULL DEFAULT 'confirmed',
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_booking_date` (`booking_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    try { $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `cancellation_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `grand_total`"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `cancellation_fee`"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `cancelled_at` DATETIME NULL DEFAULT NULL AFTER `refund_amount`"); } catch (Exception $e) {}

    $log[] = "✅ Table <strong>bookings</strong> ready.";

    // 5. ADMIN ACTIVITY LOG table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_logs` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `admin_id`   INT NOT NULL,
            `action`     VARCHAR(200) NOT NULL,
            `target`     VARCHAR(200) NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $log[] = "✅ Table <strong>admin_logs</strong> ready.";

    // 6. Seed admin account
    $adminPass = password_hash('admin2026!', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("
        INSERT INTO `users` (`username`,`name`,`email`,`password`,`role`)
        VALUES ('admin','AeroGlide Admin','admin@aeroglide.com',:pw,'admin')
        ON DUPLICATE KEY UPDATE `role`='admin'
    ");
    $stmt->execute([':pw' => $adminPass]);
    $log[] = "✅ Admin account seeded — username: <strong>admin</strong> / password: <strong>admin2026!</strong>";

    // 7. Seed initial traveler account
    $demoPass = password_hash('password123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("
        INSERT INTO `users` (`username`,`name`,`email`,`password`,`role`)
        VALUES ('demo','Juan Dela Cruz','juan@aeroglide.com',:pw,'user')
        ON DUPLICATE KEY UPDATE `name`='Juan Dela Cruz'
    ");
    $stmt->execute([':pw' => $demoPass]);
    $log[] = "✅ Traveler account seeded — username: <strong>demo</strong> / password: <strong>password123</strong>";

    // 8. Migrate existing bookings from users_db.json if present
    $jsonPath = __DIR__ . '/users_db.json';
    if (file_exists($jsonPath)) {
        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $migrated = 0;
        if (is_array($jsonData['users'] ?? null)) {
            foreach ($jsonData['users'] as $uKey => $uData) {
                if ($uKey === 'admin') continue;

                // Check / insert user
                $uStmt = $pdo->prepare("SELECT id FROM users WHERE username=:u OR email=:e LIMIT 1");
                $uStmt->execute([':u' => $uData['username'], ':e' => $uData['email']]);
                $existing = $uStmt->fetch();

                if (!$existing) {
                    $iStmt = $pdo->prepare("INSERT INTO users (username,name,email,password,role,used_coupon) VALUES (:u,:n,:e,:p,'user',:c) ON DUPLICATE KEY UPDATE id=id");
                    $iStmt->execute([
                        ':u' => $uData['username'],
                        ':n' => $uData['name'],
                        ':e' => $uData['email'],
                        ':p' => $uData['password'],
                        ':c' => !empty($uData['used_coupons']) ? (is_array($uData['used_coupons']) ? $uData['used_coupons'][0] : $uData['used_coupons']) : null,
                    ]);
                    $userId = $pdo->lastInsertId();
                } else {
                    $userId = $existing['id'];
                    // Update used coupon if needed
                    if (!empty($uData['used_coupons'])) {
                        $coupon = is_array($uData['used_coupons']) ? $uData['used_coupons'][0] : $uData['used_coupons'];
                        $pdo->prepare("UPDATE users SET used_coupon=:c WHERE id=:id")->execute([':c'=>$coupon,':id'=>$userId]);
                    }
                }

                // Migrate bookings
                if (!empty($uData['bookings'])) {
                    foreach ($uData['bookings'] as $b) {
                        $bStmt = $pdo->prepare("
                            INSERT IGNORE INTO bookings
                            (user_id,booking_ref,booking_date,traveler_name,traveler_email,traveler_phone,
                             city,mode,adults,cabin,fare_name,fare_desc,flight_schedule,
                             hotel_name,hotel_price,nights,car_name,car_price,days,
                             subtotal,discount_num,discount_label,grand_total,promo_code,payment_method)
                            VALUES
                            (:uid,:ref,:dt,:tn,:te,:tp,
                             :city,:mode,:adults,:cabin,:fn,:fd,:fs,
                             :hn,:hp,:ni,:cn,:cp,:da,
                             :sub,:disc,:dlbl,:gt,:promo,:pay)
                        ");
                        $bStmt->execute([
                            ':uid'   => $userId,
                            ':ref'   => $b['bookingRef'] ?? ('AG-' . strtoupper(bin2hex(random_bytes(4)))),
                            ':dt'    => $b['bookingDate'] ?? date('Y-m-d H:i:s'),
                            ':tn'    => $b['travelerName'] ?? $uData['name'],
                            ':te'    => $b['travelerEmail'] ?? $uData['email'],
                            ':tp'    => $b['travelerPhone'] ?? null,
                            ':city'  => $b['city'] ?? '',
                            ':mode'  => $b['mode'] ?? 'packages',
                            ':adults'=> $b['adults'] ?? 1,
                            ':cabin' => $b['cabin'] ?? 'Economy',
                            ':fn'    => $b['fareName'] ?? null,
                            ':fd'    => $b['fareDesc'] ?? null,
                            ':fs'    => $b['flightSchedule'] ?? null,
                            ':hn'    => $b['hotelName'] ?? null,
                            ':hp'    => $b['hotelPrice'] ?? 0,
                            ':ni'    => $b['nights'] ?? 0,
                            ':cn'    => $b['carName'] ?? null,
                            ':cp'    => $b['carPrice'] ?? 0,
                            ':da'    => $b['days'] ?? 0,
                            ':sub'   => $b['subTotal'] ?? 0,
                            ':disc'  => $b['discountNum'] ?? 0,
                            ':dlbl'  => $b['discountLabel'] ?? null,
                            ':gt'    => $b['grandTotal'] ?? 0,
                            ':promo' => $b['promoCode'] ?? null,
                            ':pay'   => $b['paymentMethod'] ?? null,
                        ]);
                        $migrated++;
                    }
                }
            }
        }
        $log[] = "✅ Migrated <strong>{$migrated}</strong> booking(s) from users_db.json.";
    }

    $log[] = "<hr><strong style='color:#16a34a;font-size:1.1rem;'>Setup complete! Delete this file (db_setup.php) after setup.</strong>";

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AeroGlide — Database Setup</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f0f7ff; margin: 0; padding: 40px 20px; color: #0a1425; }
  .wrap { max-width: 760px; margin: 0 auto; }
  h1 { font-size: 2rem; font-weight: 900; color: #0d6efd; margin-bottom: 4px; }
  .subtitle { color: #64748b; margin-bottom: 2rem; }
  .card { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
  .log-item { padding: 10px 14px; border-radius: 10px; margin-bottom: 8px; background: #f8faff; border-left: 4px solid #0d6efd; font-size: 0.95rem; }
  .error { background: #fff0f0; border-color: #dc2626; color: #dc2626; }
  hr { border: none; border-top: 1px dashed #e2e8f0; margin: 16px 0; }
  .links { margin-top: 1.5rem; display: flex; gap: 12px; flex-wrap: wrap; }
  .btn { padding: 11px 22px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.9rem; display: inline-block; }
  .btn-blue { background: #0d6efd; color: #fff; }
  .btn-gray { background: #f1f5f9; color: #0a1425; border: 1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="wrap">
  <h1>✈️ AeroGlide DB Setup</h1>
  <p class="subtitle">Setting up MySQL database for the AeroGlide booking system.</p>
  <div class="card">
    <?php if ($error): ?>
      <div class="log-item error">❌ Fatal Error: <?= htmlspecialchars($error) ?></div>
    <?php else: ?>
      <?php foreach ($log as $entry): ?>
        <div class="log-item"><?= $entry ?></div>
      <?php endforeach; ?>
    <?php endif; ?>
    <div class="links">
      <a href="index.php" class="btn btn-blue">Go to Homepage</a>
      <a href="admin/index.php" class="btn btn-blue">Open Admin Panel</a>
      <a href="login.php" class="btn btn-gray">Login Page</a>
    </div>
  </div>
</div>
</body>
</html>
