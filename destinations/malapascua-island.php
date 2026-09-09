<?php
// ============================================================
// AeroGlide — malapascua-island.php
// Dedicated destination package page for Malapascua Island, Cebu.
// ============================================================

$_GET['city'] = $_GET['city'] ?? 'Malapascua Island, Cebu';

if (file_exists(__DIR__ . '/../destination.php')) {
    require __DIR__ . '/../destination.php';
} else {
    require __DIR__ . '/destination.php';
}

