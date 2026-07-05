<?php
require_once __DIR__ . '/config/db.php';
$conn = db();
$res = $conn->query("SELECT id, tracking_code, assigned_to, status, created_at FROM tickets ORDER BY id DESC LIMIT 50");
if (!$res) {
    echo "Query error: " . $conn->error . PHP_EOL;
    exit(1);
}
$rows = $res->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $r) {
    echo implode(' | ', [$r['id'], $r['tracking_code'] ?? 'NULL', $r['assigned_to'] ?? 'NULL', $r['status'], $r['created_at']]) . PHP_EOL;
}
if (empty($rows)) {
    echo "No tickets found\n";
}
