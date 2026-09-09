<?php
// ============================================================
// AeroGlide — albay.php
// Dedicated destination package page for Mayon Volcano, Albay.
// Presets the destination city and loads destination.php.
// ============================================================

$_GET['city'] = $_GET['city'] ?? 'Mayon Volcano, Albay';

require __DIR__ . '/destination.php';
