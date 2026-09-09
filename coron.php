<?php
// Root compatibility wrapper for coron.php
$_GET['city'] = $_GET['city'] ?? 'Coron, Palawan';
if (file_exists(__DIR__ . '/destinations/coron.php')) {
    require __DIR__ . '/destinations/coron.php';
} else {
    require __DIR__ . '/destination.php';
}
