<?php
// Root compatibility wrapper for siargao.php
$_GET['city'] = $_GET['city'] ?? 'Siargao, Surigao del Norte';
if (file_exists(__DIR__ . '/destinations/siargao.php')) {
    require __DIR__ . '/destinations/siargao.php';
} else {
    require __DIR__ . '/destination.php';
}
