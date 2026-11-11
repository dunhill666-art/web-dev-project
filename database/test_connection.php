<?php
// ============================================================
// AeroGlide — database/test_connection.php
// Run this file to verify your database connection is working.
// Access via: http://localhost/website/database/test_connection.php
// DELETE this file after testing (do not leave in production).
// ============================================================

// Restrict access: only allow from localhost
$allowed = ['127.0.0.1', '::1', 'localhost'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, $allowed)) {
    http_response_code(403);
    die('<h2>403 Forbidden — Test script can only run from localhost.</h2>');
}

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AeroGlide — DB Connection Test</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 780px; margin: 40px auto; padding: 0 20px; background: #f8fafc; color: #0f172a; }
        h1 { font-size: 1.6rem; margin-bottom: 1.5rem; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; }
        .pass { color: #16a34a; font-weight: 700; }
        .fail { color: #dc2626; font-weight: 700; }
        .warn { color: #ca8a04; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        th { background: #f1f5f9; font-weight: 700; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 700; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-red   { background: #fee2e2; color: #991b1b; }
        .badge-yellow{ background: #fef9c3; color: #713f12; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem; }
    </style>
</head>
<body>
<h1>&#128747; AeroGlide — Database Connection Test</h1>

<?php

$results = [];

// --- TEST 1: PDO Connection ---
try {
    $db = ag_db();
    $results[] = ['label' => 'PDO Connection', 'status' => 'PASS', 'detail' => sprintf('Connected to <strong>%s</strong> on <strong>%s</strong> as user <strong>%s</strong>', AG_DB_NAME, AG_DB_HOST, AG_DB_USER)];
} catch (PDOException $e) {
    $results[] = ['label' => 'PDO Connection', 'status' => 'FAIL', 'detail' => htmlspecialchars($e->getMessage())];
    // Render and bail early
    echo '<div class="card"><table><thead><tr><th>Test</th><th>Result</th><th>Detail</th></tr></thead><tbody>';
    foreach ($results as $r) {
        $bc = $r['status'] === 'PASS' ? 'badge-green' : 'badge-red';
        echo sprintf('<tr><td>%s</td><td><span class="badge %s">%s</span></td><td>%s</td></tr>', $r['label'], $bc, $r['status'], $r['detail']);
    }
    echo '</tbody></table></div>';
    die('<div class="card"><span class="fail">Cannot continue — fix the connection error above first.</span></div></body></html>');
}

// --- TEST 2: MySQL Server Version ---
try {
    $ver = $db->query("SELECT VERSION() AS v")->fetchColumn();
    $results[] = ['label' => 'MySQL Version', 'status' => 'PASS', 'detail' => 'Server version: <strong>' . htmlspecialchars($ver) . '</strong>'];
} catch (Exception $e) {
    $results[] = ['label' => 'MySQL Version', 'status' => 'FAIL', 'detail' => htmlspecialchars($e->getMessage())];
}

// --- TEST 3: Check all expected tables exist ---
$expectedTables = ['users', 'bookings', 'flights', 'password_tokens', 'admin_logs'];
try {
    $existingTables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($expectedTables as $table) {
        if (in_array($table, $existingTables)) {
            $countRow = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            $results[] = ['label' => "Table: <code>$table</code>", 'status' => 'PASS', 'detail' => 'Found &mdash; <strong>' . number_format((int)$countRow) . '</strong> rows'];
        } else {
            $results[] = ['label' => "Table: <code>$table</code>", 'status' => 'WARN', 'detail' => 'Table not found &mdash; run <code>database/database.sql</code> in phpMyAdmin to create it.'];
        }
    }
} catch (Exception $e) {
    $results[] = ['label' => 'Table Check', 'status' => 'FAIL', 'detail' => htmlspecialchars($e->getMessage())];
}

// --- TEST 4: Check flights table evening coverage ---
try {
    $eveningFlights = $db->query("SELECT COUNT(*) FROM flights WHERE departure_time >= '18:00:00'")->fetchColumn();
    $totalFlights   = $db->query("SELECT COUNT(*) FROM flights")->fetchColumn();
    $status = ($eveningFlights > 0) ? 'PASS' : 'WARN';
    $detail = 'Total: <strong>' . number_format((int)$totalFlights) . '</strong> flights &nbsp;|&nbsp; Evening (18:00+): <strong>' . number_format((int)$eveningFlights) . '</strong>';
    $results[] = ['label' => 'Evening Flights Seeded', 'status' => $status, 'detail' => $detail];
} catch (Exception $e) {
    $results[] = ['label' => 'Evening Flights Seeded', 'status' => 'FAIL', 'detail' => htmlspecialchars($e->getMessage())];
}

// --- TEST 5: Write test (INSERT + DELETE using a transaction) ---
try {
    $db->beginTransaction();
    $stmt = $db->prepare("INSERT INTO admin_logs (admin_id, action, target) SELECT id, 'test_connection_check', 'test_connection.php' FROM users ORDER BY id LIMIT 1");
    $stmt->execute();
    $lastId = $db->lastInsertId();
    if ($lastId) {
        $db->exec("DELETE FROM admin_logs WHERE id = " . (int)$lastId);
        $db->commit();
        $results[] = ['label' => 'Write Access (INSERT/DELETE)', 'status' => 'PASS', 'detail' => 'Test row written and cleaned up &mdash; database is writable.'];
    } else {
        $db->rollBack();
        $results[] = ['label' => 'Write Access (INSERT/DELETE)', 'status' => 'WARN', 'detail' => 'No user rows found to reference. Add a user first to test write access fully.'];
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    $results[] = ['label' => 'Write Access (INSERT/DELETE)', 'status' => 'WARN', 'detail' => 'Write test skipped: ' . htmlspecialchars($e->getMessage())];
}

// --- RENDER RESULTS ---
echo '<div class="card">';
echo '<table>';
echo '<thead><tr><th>Test</th><th>Result</th><th>Detail</th></tr></thead><tbody>';
foreach ($results as $r) {
    $badgeClass = match ($r['status']) {
        'PASS'  => 'badge-green',
        'FAIL'  => 'badge-red',
        default => 'badge-yellow',
    };
    echo sprintf('<tr><td>%s</td><td><span class="badge %s">%s</span></td><td>%s</td></tr>',
        $r['label'], $badgeClass, $r['status'], $r['detail']
    );
}
echo '</tbody></table>';
echo '</div>';

// Config info box
echo '<div class="card">';
echo '<h3 style="margin:0 0 1rem">&#9881;&#65039; Connection Config (database/config.php)</h3>';
echo '<table>';
echo '<tr><td><strong>Host</strong></td><td><code>' . htmlspecialchars(AG_DB_HOST) . '</code></td></tr>';
echo '<tr><td><strong>Database</strong></td><td><code>' . htmlspecialchars(AG_DB_NAME) . '</code></td></tr>';
echo '<tr><td><strong>Username</strong></td><td><code>' . htmlspecialchars(AG_DB_USER) . '</code></td></tr>';
echo '<tr><td><strong>Password</strong></td><td><code>' . (AG_DB_PASS === '' ? '(empty — local WAMP default)' : '***') . '</code></td></tr>';
echo '<tr><td><strong>Charset</strong></td><td><code>utf8mb4</code></td></tr>';
echo '<tr><td><strong>PDO Error Mode</strong></td><td><code>ERRMODE_EXCEPTION</code></td></tr>';
echo '</table>';
echo '</div>';

echo '<div class="card" style="background:#fef9c3;border-color:#fde047;">';
echo '<strong>&#9888;&#65039; Security Reminder:</strong> Delete <code>test_connection.php</code> after testing. Never expose this file on a public server.';
echo '</div>';
?>
</body>
</html>
