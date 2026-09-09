<?php
// Root compatibility wrapper for signup.php
if (file_exists(__DIR__ . '/user/signup.php')) {
    require_once __DIR__ . '/user/signup.php';
}
