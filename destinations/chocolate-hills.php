<?php
// ============================================================
// AeroGlide — chocolate-hills.php
// Dedicated destination package page for Chocolate Hills, Bohol.
// ============================================================

$_GET['city'] = $_GET['city'] ?? 'Chocolate Hills, Bohol';

if (file_exists(__DIR__ . '/../destination.php')) {
    require __DIR__ . '/../destination.php';
} else {
    require __DIR__ . '/destination.php';
}

