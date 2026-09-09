<?php
// ============================================================
// AeroGlide — taal-volcano.php
// Dedicated destination package page for Taal Volcano, Batangas.
// ============================================================

$_GET['city'] = $_GET['city'] ?? 'Taal Volcano, Batangas';

if (file_exists(__DIR__ . '/../destination.php')) {
    require __DIR__ . '/../destination.php';
} else {
    require __DIR__ . '/destination.php';
}

