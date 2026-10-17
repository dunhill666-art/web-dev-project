<?php
// ============================================================
// AeroGlide — albay.php
// Dedicated destination package page for Mayon Volcano, Albay.
// Presets the destination city and loads destination.php.
// ============================================================

$_GET['city'] = $_GET['city'] ?? 'Mayon Volcano, Albay';

if (file_exists(__DIR__ . '/../destination.php')) {
    require __DIR__ . '/../destination.php';
} else {
    require __DIR__ . '/destination.php';
}

