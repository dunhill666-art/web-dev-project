<?php
// Root compatibility wrapper for chocolate-hills.php
$_GET['city'] = $_GET['city'] ?? 'Chocolate Hills, Bohol';
if (file_exists(__DIR__ . '/destinations/chocolate-hills.php')) {
    require __DIR__ . '/destinations/chocolate-hills.php';
} else {
    require __DIR__ . '/destination.php';
}
