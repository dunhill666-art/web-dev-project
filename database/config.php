<?php
// ============================================================
// AeroGlide — database/config.php
// Central MySQL/MariaDB PDO connection configuration
// Compatible with Docker, WampServer, and standalone environments
// ============================================================

// Lightweight .env loader if Symfony Dotenv is not loaded
if (file_exists(dirname(__DIR__) . '/.env')) {
    $lines = file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim(trim($val), "\"'");
        
        // Priority: Only set if NOT already passed in environment (e.g. from Docker)
        if (getenv($key) === false && !isset($_ENV[$key])) {
            $_ENV[$key] = $val;
            putenv("$key=$val");
        }
    }
}

$dbHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
$dbPort = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');
$dbName = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'aeroglide');
$dbUser = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$dbPass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ($_ENV['DB_PASSWORD'] ?? '');

if (!defined('AG_DB_HOST')) define('AG_DB_HOST', $dbHost);
if (!defined('AG_DB_PORT')) define('AG_DB_PORT', $dbPort);
if (!defined('AG_DB_NAME')) define('AG_DB_NAME', $dbName);
if (!defined('AG_DB_USER')) define('AG_DB_USER', $dbUser);
if (!defined('AG_DB_PASS')) define('AG_DB_PASS', $dbPass);

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

        $dsn = 'mysql:host=' . AG_DB_HOST . ';port=' . AG_DB_PORT . ';dbname=' . AG_DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, AG_DB_USER, AG_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    }
}
