<?php
// Root compatibility wrapper for logout.php
if (file_exists(__DIR__ . '/user/logout.php')) {
    require_once __DIR__ . '/user/logout.php';
}
