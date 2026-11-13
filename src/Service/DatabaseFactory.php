<?php

namespace App\Service;

use PDO;

class DatabaseFactory
{
    public static function getConnection(): PDO
    {
        require_once dirname(__DIR__, 2) . '/database/config.php';
        return ag_db();
    }
}

