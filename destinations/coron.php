<?php
// ============================================================
// AeroGlide — coron.php
// Dedicated destination package page for Coron, Palawan.
// ============================================================

$_GET['city'] = $_GET['city'] ?? 'Coron, Palawan';

if (file_exists(__DIR__ . '/../destination.php')) {
    require __DIR__ . '/../destination.php';
} else {
    require __DIR__ . '/destination.php';
}

