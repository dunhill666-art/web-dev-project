<?php
// Root compatibility wrapper for albay.php
$_GET['city'] = $_GET['city'] ?? 'Albay, Bicol';
if (file_exists(__DIR__ . '/destinations/albay.php')) {
    require __DIR__ . '/destinations/albay.php';
} else {
    require __DIR__ . '/destination.php';
}
