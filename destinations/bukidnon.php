<?php
// ============================================================
// AeroGlide — bukidnon.php
// Dedicated destination package page for Bukidnon.
// ============================================================

$_GET['city'] = $_GET['city'] ?? 'Bukidnon';

if (file_exists(__DIR__ . '/../destination.php')) {
    require __DIR__ . '/../destination.php';
} else {
    require __DIR__ . '/destination.php';
}

