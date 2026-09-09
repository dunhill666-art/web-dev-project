<?php
// db connection alias

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} elseif (file_exists(__DIR__ . '/database/config.php')) {
    require_once __DIR__ . '/database/config.php';
}
