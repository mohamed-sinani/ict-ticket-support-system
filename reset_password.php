<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) {
    redirect(homePathForRole(currentUser()['role']));
}

$token = trim((string) ($_GET['token'] ?? ($_POST['token'] ?? '')));
$error = '';
$success = false;
$linkInvalid = false;

function resetTokenError(?array $reset): ?string
{
    if ($reset === null) {
        return 'This password reset link is invalid.';
    }
    if ((int) $reset['used'] === 1) {
        return 'This password reset link has already been used.';
    }
    if (strtotime($reset['expires_at']) < time()) {
        return 'This password reset link has expired. Please request a new one.';
    }
    if ((int) $reset['attempts'] >= PASSWORD_RESET_MAX_ATTEMPTS) {
        return 'Too many failed attempts with this link. Please request a new one.';
    }
    return null;
}

if ($token !== '') {
    $linkError = resetTokenError(findPasswordReset($token));
    $linkInvalid = $linkError !== null;
    if ($linkInvalid) {
        $error = $linkError;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security token. Please try again.';
    } elseif ($token === '') {
        $error = 'This password reset link is invalid.';
        $linkInvalid = true;
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $consumeError = consumePasswordReset($token, $password);

            if ($consumeError !== null) {
                $linkInvalid = true;
                $error = [
                    'already_used' => 'This password reset link has already been used.',
                    'max_attempts' => 'Too many failed attempts with this link. Please request a new one.',
                    'expired' => 'This password reset link has expired. Please request a new one.',
                ][$consumeError] ?? 'This password reset link is invalid.';
            } else {
                setFlash('Your password has been reset successfully. Please login with your new password.', 'success');
                redirect('login');
            }
        }
    }
}

$pageTitle = 'Reset Password | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-card">
    <h2 data-i18n="reset_title">Reset Password</h2>

    <?php if ($error): ?>
        <p class="alert alert-danger"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if ($linkInvalid): ?>
        <p class="small-text">
            <a href="<?= $baseUrl ?>forgot_password" data-i18n="reset_request_new">Request a new reset link</a>
        </p>
    <?php else: ?>
        <p class="small-text" data-i18n="reset_intro">Choose a new password for your account.</p>
        <form method="POST" class="form-grid" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label>
                <span data-i18n="reset_password_label">New Password</span>
                <input type="password" name="password" minlength="8" required autocomplete="new-password" placeholder="Minimum 8 characters">
            </label>
            <label>
                <span data-i18n="reset_confirm_label">Confirm New Password</span>
                <input type="password" name="confirm_password" minlength="8" required autocomplete="new-password" placeholder="Repeat password">
            </label>
            <button type="submit" class="btn btn-primary" data-i18n="reset_submit">Reset Password</button>
        </form>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
