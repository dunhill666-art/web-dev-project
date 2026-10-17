<?php
// ============================================================
// AeroGlide — siargao.php
// Dedicated destination package page for Siargao, Surigao del Norte.
// ============================================================

$_GET['city'] = $_GET['city'] ?? 'Siargao, Surigao del Norte';

if (file_exists(__DIR__ . '/../destination.php')) {
    require __DIR__ . '/../destination.php';
} else {
    require __DIR__ . '/destination.php';
}

