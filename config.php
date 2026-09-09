<?php

/**
 * Returns a PDO connection to the database.
 * Update the host/dbname/username/password to match your environment.
 */
function getConnection(): PDO
{
    $host     = 'localhost';
    $dbname   = 'aeroglide';
    $username = 'root';
    $password = '';

    try {
        return new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $e) {
        die('Connection failed: ' . $e->getMessage());
    }
}