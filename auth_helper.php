<?php
// Root compatibility wrapper for auth_helper.php
if (file_exists(__DIR__ . '/user/auth_helper.php')) {
    require_once __DIR__ . '/user/auth_helper.php';
}
