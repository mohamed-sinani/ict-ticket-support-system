<?php

declare(strict_types=1);

require_once __DIR__ . '/app.php';

function db(): mysqli
{
    static $conn = null;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    $host = env('DB_HOST', '127.0.0.1');
    $user = env('DB_USER', 'root');
    $password = env('DB_PASSWORD', '');
    $database = env('DB_NAME', 'ict_support_system');
    $port = (int) env('DB_PORT', '3306');

    $conn = new mysqli($host, $user, $password, $database, $port);

    if ($conn->connect_error) {
        http_response_code(500);
        die('Database connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}
