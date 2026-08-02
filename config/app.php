<?php

declare(strict_types=1);

const APP_NAME = 'Report all Technical ICT issues by Submitting a Ticket';
const UPLOAD_DIR = __DIR__ . '/../uploads';
const MAX_UPLOAD_SIZE = 5 * 1024 * 1024;

function app_is_localhost(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost'));
    $host = preg_replace('/:\d+$/', '', $host);

    return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
        || substr($host, -6) === '.local'
        || substr($host, -5) === '.test';
}

function app_scheme(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        return strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : 'http';
    }

    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
}

function app_base_path(): string
{
    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    $docRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $projectRoot = realpath(__DIR__ . '/..');

    if ($docRoot && $projectRoot) {
        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        $projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');

        if ($docRoot !== '' && strpos($projectRoot, $docRoot) === 0) {
            $basePath = substr($projectRoot, strlen($docRoot));
            $basePath = '/' . trim((string) $basePath, '/');
            $basePath = $basePath === '/' ? '' : $basePath;
            return $basePath;
        }
    }

    $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    $basePath = preg_replace('#/(admin|staff|employee|api)$#', '', $scriptDir) ?: '';

    return $basePath === '/' ? '' : $basePath;
}

function app_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $basePath = rtrim(app_base_path(), '/');

    return $basePath . ($path === '' ? '' : '/' . $path);
}

function app_absolute_url(string $path = ''): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return app_scheme() . '://' . $host . app_url($path);
}

define('BASE_URL', app_base_path());

const OTP_LENGTH = 6;
const OTP_EXPIRY_MINUTES = 5;
const OTP_MAX_ATTEMPTS = 5;

const PASSWORD_RESET_MINUTES = 30;
const PASSWORD_RESET_MAX_ATTEMPTS = 5;
const PASSWORD_RESET_MAX_REQUESTS = 3;
const PASSWORD_RESET_RATE_WINDOW = 600;

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!defined($key) && !array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, string $default = ''): string
{
    return $_ENV[$key] ?? $default;
}

define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int) env('SMTP_PORT', '587'));
define('SMTP_USERNAME', env('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', ''));
define('SMTP_ENCRYPTION', env('SMTP_ENCRYPTION', 'tls'));

const STATUS_SUBMITTED = 'Submitted';
const STATUS_ASSIGNED = 'Assigned';
const STATUS_IN_PROGRESS = 'In Progress';
const STATUS_RESOLVED = 'Resolved';
const STATUS_CLOSED = 'Closed';

const TICKET_STATUSES = [
    STATUS_SUBMITTED,
    STATUS_ASSIGNED,
    STATUS_IN_PROGRESS,
    STATUS_RESOLVED,
    STATUS_CLOSED,
];
