<?php
// ============================================================
// AeroGlide — database/config.php
// Central MySQL PDO connection configuration
// ============================================================

if (!defined('AG_DB_HOST')) define('AG_DB_HOST', 'localhost');
if (!defined('AG_DB_NAME')) define('AG_DB_NAME', 'aeroglide');
if (!defined('AG_DB_USER')) define('AG_DB_USER', 'root');
if (!defined('AG_DB_PASS')) define('AG_DB_PASS', '');

/**
 * Returns a static PDO database connection instance.
 */
if (!function_exists('ag_db')) {
    function ag_db(): PDO
    {
        static $pdo = null;
        if ($pdo !== null) {
            return $pdo;
        }

        $dsn = 'mysql:host=' . AG_DB_HOST . ';dbname=' . AG_DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, AG_DB_USER, AG_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    }
}
