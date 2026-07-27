<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function verifyPassword(string $plainPassword, string $storedPassword): bool
{
    $info = password_get_info($storedPassword);

    if (($info['algo'] ?? null) !== null && ($info['algo'] ?? 0) !== 0) {
        return password_verify($plainPassword, $storedPassword);
    }

    // Development fallback to support seeded plain-text demo users.
    return hash_equals($storedPassword, $plainPassword);
}

function authenticate(string $email, string $password, ?string $expectedRole = null, ?string &$failureReason = null): bool
{
    $failureReason = null;

    $conn = db();
    $sql = 'SELECT id, full_name, email, role, password FROM users WHERE email = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !verifyPassword($password, $user['password'])) {
        $failureReason = 'invalid_credentials';
        return false;
    }

    if ($expectedRole !== null && $user['role'] !== $expectedRole) {
        $failureReason = 'wrong_role';
        return false;
    }

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];

    return true;
}

function generateOtp(int $userId): string
{
    $conn = db();

    $sql = 'UPDATE otp_codes SET used = 1 WHERE user_id = ? AND used = 0';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $code = '';
    for ($i = 0; $i < OTP_LENGTH; $i++) {
        $code .= random_int(0, 9);
    }

    $expiresAt = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);

    $sql = 'INSERT INTO otp_codes (user_id, code, expires_at) VALUES (?, ?, ?)';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $userId, $code, $expiresAt);
    $stmt->execute();

    return $code;
}

function verifyOtp(int $userId, string $code): ?string
{
    $conn = db();

    $sql = 'SELECT id, code, expires_at, attempts, used FROM otp_codes WHERE user_id = ? AND used = 0 ORDER BY id DESC LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $otp = $stmt->get_result()->fetch_assoc();

    if (!$otp) {
        return 'no_otp';
    }

    if (strtotime($otp['expires_at']) < time()) {
        return 'expired';
    }

    if ((int) $otp['attempts'] >= OTP_MAX_ATTEMPTS) {
        return 'max_attempts';
    }

    if (!hash_equals($otp['code'], $code)) {
        $sql = 'UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $otp['id']);
        $stmt->execute();
        return 'invalid';
    }

    $sql = 'UPDATE otp_codes SET used = 1 WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $otp['id']);
    $stmt->execute();

    return null;
}

function homePathForRole(string $role): string
{
    if ($role === 'admin') {
        return 'admin/dashboard.php';
    }

    if ($role === 'ict') {
        return 'staff/dashboard.php';
    }

    return 'employee/dashboard.php';
}

function requireLogin(array $roles = ['admin', 'ict']): void
{
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }

    $user = currentUser();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
