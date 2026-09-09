<?php
// Root compatibility wrapper for db.php
if (file_exists(__DIR__ . '/database/db.php')) {
    require_once __DIR__ . '/database/db.php';
}
