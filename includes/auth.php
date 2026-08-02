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

    return hash_equals($storedPassword, $plainPassword);
}

function authenticate(string $email, string $password, ?string $expectedRole = null, ?string &$failureReason = null): bool
{
    $failureReason = null;

    $conn = db();
    $sql = 'SELECT id, full_name, email, role, password, approval_status, review_reason FROM users WHERE email = ? LIMIT 1';
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
        'approval_status' => $user['approval_status'] ?? 'approved',
        'review_reason' => $user['review_reason'] ?? null,
    ];

    return true;
}

function approvalStatus(): string
{
    return (string) (currentUser()['approval_status'] ?? 'approved');
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

function createPasswordReset(int $userId): string
{
    $conn = db();

    $sql = 'UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + PASSWORD_RESET_MINUTES * 60);

    $sql = 'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $userId, $tokenHash, $expiresAt);
    $stmt->execute();

    return $token;
}

function findPasswordReset(string $rawToken): ?array
{
    $conn = db();
    $tokenHash = hash('sha256', $rawToken);

    $sql = 'SELECT id, user_id, expires_at, attempts, used FROM password_resets WHERE token_hash = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();

    return is_array($reset) ? $reset : null;
}

function consumePasswordReset(string $rawToken, string $newPassword): ?string
{
    $reset = findPasswordReset($rawToken);

    if ($reset === null) {
        return 'invalid_token';
    }

    if ((int) $reset['used'] === 1) {
        return 'already_used';
    }

    if (strtotime($reset['expires_at']) < time()) {
        return 'expired';
    }

    if ((int) $reset['attempts'] >= PASSWORD_RESET_MAX_ATTEMPTS) {
        return 'max_attempts';
    }

    $conn = db();
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $sql = 'UPDATE users SET password = ? WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $passwordHash, $reset['user_id']);
    $stmt->execute();
    if ($stmt->affected_rows < 0) {
        return 'failed';
    }

    $sql = 'UPDATE password_resets SET used = 1 WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $reset['id']);
    $stmt->execute();

    return null;
}

function homePathForRole(string $role): string
{
    if ($role === 'admin') {
        return 'admin/dashboard';
    }

    if ($role === 'ict') {
        return 'staff/dashboard';
    }

    return 'employee/dashboard';
}

function requireLogin(array $roles = ['admin', 'ict']): void
{
    if (!isLoggedIn()) {
        header('Location: ../login');
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
    if (isset($_COOKIE['ict_remember'])) {
        setcookie('ict_remember', '', time() - 42000, '/');
    }
    session_destroy();
}
