#!/bin/bash
set -e

echo "=========================================================="
echo " Starting AeroGlide Aviation Containerized Stack"
echo "=========================================================="

# 1. Wait for database service to accept PDO connections
echo "==> Verifying database connectivity at ${DB_HOST:-db}:${DB_PORT:-3306}..."

MAX_TRIES=30
COUNT=0
until php -r "
require_once '/var/www/html/database/config.php';
try {
    \$p = ag_db();
    echo 'OK';
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
" >/dev/null 2>&1; do
    COUNT=$((COUNT+1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "❌ Database connection timed out after $MAX_TRIES attempts."
        break
    fi
    echo "    Waiting for database ($COUNT/$MAX_TRIES)..."
    sleep 2
done

echo "✅ Database connection established."

# 2. Check and ensure schema / dynamic seed flights are present
php -r "
require_once '/var/www/html/database/config.php';
try {
    \$pdo = ag_db();
    \$tables = \$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    
    // Check if flights or users are missing
    if (!in_array('users', \$tables) || !in_array('flights', \$tables)) {
        echo '==> Initializing complete database schema & dynamic seed data...' . PHP_EOL;
        require_once '/var/www/html/database/db_setup.php';
    } else {
        \$flightCount = (int)\$pdo->query('SELECT COUNT(*) FROM flights')->fetchColumn();
        if (\$flightCount === 0) {
            echo '==> Seeding dynamic flight schedules...' . PHP_EOL;
            require_once '/var/www/html/database/db_setup.php';
        } else {
            echo '==> Database ready with ' . number_format(\$flightCount) . ' scheduled flights.' . PHP_EOL;
        }
    }
} catch (Exception \$e) {
    echo 'Warning during database check: ' . \$e->getMessage() . PHP_EOL;
}
" || true

echo "=========================================================="
echo " AeroGlide Web Server Ready at http://localhost:8080"
echo " phpMyAdmin Visual GUI Ready at http://localhost:8081"
echo "=========================================================="

exec "$@"
