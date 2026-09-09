<?php
// Root compatibility wrapper for taal-volcano.php
$_GET['city'] = $_GET['city'] ?? 'Taal Volcano, Batangas';
if (file_exists(__DIR__ . '/destinations/taal-volcano.php')) {
    require __DIR__ . '/destinations/taal-volcano.php';
} else {
    require __DIR__ . '/destination.php';
}
