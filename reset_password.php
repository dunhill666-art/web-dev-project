<?php
// Root compatibility wrapper for reset_password.php
if (file_exists(__DIR__ . '/user/reset_password.php')) {
    require_once __DIR__ . '/user/reset_password.php';
}
