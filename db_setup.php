<?php
// Root compatibility wrapper for db_setup.php
if (file_exists(__DIR__ . '/database/db_setup.php')) {
    require_once __DIR__ . '/database/db_setup.php';
}
