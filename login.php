<?php
// Root compatibility wrapper for login.php
if (file_exists(__DIR__ . '/user/login.php')) {
    require_once __DIR__ . '/user/login.php';
}
