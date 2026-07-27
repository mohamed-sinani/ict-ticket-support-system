<?php

declare(strict_types=1);

const APP_NAME = 'Institutional ICT Support Ticket & Issue Tracking System';
const UPLOAD_DIR = __DIR__ . '/../uploads';
const MAX_UPLOAD_SIZE = 5 * 1024 * 1024; // 5MB

function app_is_localhost(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost'));
    $host = preg_replace('/:\d+$/', '', $host);

    return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
        || str_ends_with($host, '.local')
        || str_ends_with($host, '.test');
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

        if ($docRoot !== '' && str_starts_with($projectRoot, $docRoot)) {
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

const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_USERNAME = 'tuma.maoni.app@gmail.com';
const SMTP_PASSWORD = 'liqzovplyarquxzt';
const SMTP_ENCRYPTION = 'tls';

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
