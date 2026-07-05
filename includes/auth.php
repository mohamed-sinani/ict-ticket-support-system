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
