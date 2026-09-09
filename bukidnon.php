<?php
// Root compatibility wrapper for bukidnon.php
$_GET['city'] = $_GET['city'] ?? 'Bukidnon, Northern Mindanao';
if (file_exists(__DIR__ . '/destinations/bukidnon.php')) {
    require __DIR__ . '/destinations/bukidnon.php';
} else {
    require __DIR__ . '/destination.php';
}
