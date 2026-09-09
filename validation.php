<?php
// Root compatibility wrapper for validation.php
if (file_exists(__DIR__ . '/database/validation.php')) {
    require_once __DIR__ . '/database/validation.php';
}