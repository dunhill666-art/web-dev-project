<?php
// Root compatibility wrapper for auth.php
if (file_exists(__DIR__ . '/user/auth.php')) {
    require_once __DIR__ . '/user/auth.php';
}
