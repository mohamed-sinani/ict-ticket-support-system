<?php

declare(strict_types=1);

require_once __DIR__ . '/app.php';

function db(): mysqli
{
    static $conn = null;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    $host = '127.0.0.1';
    $user = 'root';
    $password = '';
    $database = 'ict_support_system';
    $port = 3306;

    $conn = new mysqli($host, $user, $password, $database, $port);

    if ($conn->connect_error) {
        http_response_code(500);
        die('Database connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}
