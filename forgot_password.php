<?php
// Root compatibility wrapper for forgot_password.php
if (file_exists(__DIR__ . '/user/forgot_password.php')) {
    require_once __DIR__ . '/user/forgot_password.php';
}
