<?php
// Root compatibility wrapper for boracay.php
$_GET['city'] = $_GET['city'] ?? 'Boracay, Aklan';
if (file_exists(__DIR__ . '/destinations/boracay.php')) {
    require __DIR__ . '/destinations/boracay.php';
} else {
    require __DIR__ . '/destination.php';
}
