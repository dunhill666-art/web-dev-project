<?php
// ============================================================
// AeroGlide — database/config.php
// Central MySQL PDO connection configuration (with .env support)
// ============================================================

// Lightweight .env loader if Symfony Dotenv is not loaded
if (!isset($_ENV['DB_NAME']) && file_exists(dirname(__DIR__) . '/.env')) {
    $lines = file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim(trim($val), "\"'");
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $val;
            putenv("$key=$val");
        }
    }
}

if (!defined('AG_DB_HOST')) define('AG_DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('AG_DB_PORT')) define('AG_DB_PORT', $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306');
if (!defined('AG_DB_NAME')) define('AG_DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'aeroglide');
if (!defined('AG_DB_USER')) define('AG_DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root');
if (!defined('AG_DB_PASS')) define('AG_DB_PASS', $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '');

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
