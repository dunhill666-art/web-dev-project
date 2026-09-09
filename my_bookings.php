<?php
// Root compatibility wrapper for my_bookings.php
if (file_exists(__DIR__ . '/user/my_bookings.php')) {
    require_once __DIR__ . '/user/my_bookings.php';
}
