<?php
// Root compatibility wrapper for function.php
if (file_exists(__DIR__ . '/database/function.php')) {
    require_once __DIR__ . '/database/function.php';
}
