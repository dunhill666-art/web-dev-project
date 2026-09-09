<?php
// Root compatibility wrapper for malapascua-island.php
$_GET['city'] = $_GET['city'] ?? 'Malapascua Island, Cebu';
if (file_exists(__DIR__ . '/destinations/malapascua-island.php')) {
    require __DIR__ . '/destinations/malapascua-island.php';
} else {
    require __DIR__ . '/destination.php';
}
