<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) {
    redirect(homePathForRole(currentUser()['role']));
}

$error = '';
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $now = time();
        $rate = $_SESSION['pw_reset_rate'] ?? ['start' => $now, 'count' => 0];
        if ($now - (int) $rate['start'] >= PASSWORD_RESET_RATE_WINDOW) {
            $rate = ['start' => $now, 'count' => 0];
        }
        if ((int) $rate['count'] >= PASSWORD_RESET_MAX_REQUESTS) {
            $error = 'Too many password reset requests. Please wait 10 minutes and try again.';
        } else {
            $rate['count'] = (int) $rate['count'] + 1;
            $_SESSION['pw_reset_rate'] = $rate;

            $email = strtolower(trim($_POST['email'] ?? ''));

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $conn = db();
                $stmt = $conn->prepare('SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1');
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();

                if ($user) {
                    $token = createPasswordReset((int) $user['id']);
                    $resetUrl = absoluteUrl('reset_password') . '?token=' . urlencode($token);
                    $plainMessage = 'Hello ' . $user['full_name'] . ",\n\n"
                        . 'We received a request to reset the password for your ICT Support account.' . "\n"
                        . 'Open the link below to choose a new password. It expires in ' . PASSWORD_RESET_MINUTES . " minutes.\n\n"
                        . $resetUrl . "\n\n"
                        . 'If you did not request this, you can safely ignore this email.';

                    sendNotificationEmail($user['email'], 'Your ICT Support Password Reset Link', $plainMessage, buildPasswordResetEmail($user['full_name'], $resetUrl, PASSWORD_RESET_MINUTES));
                }

                $sent = true;
            }
        }
    }
}

$pageTitle = 'Forgot Password | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-card">
    <h2 data-i18n="forgot_title">Forgot Password</h2>

    <?php if ($error): ?>
        <p class="alert alert-danger"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if ($sent): ?>
        <p class="alert alert-success">
            If an account exists for that email, a password reset link has been sent. Please check your inbox.
        </p>
        <p class="small-text">
            <a href="<?= $baseUrl ?>login">Back to login</a>
        </p>
    <?php else: ?>
        <p class="small-text" data-i18n="forgot_intro">Enter your account email and we will send you a link to reset your password.</p>
        <form method="POST" class="form-grid" novalidate>
            <?= csrf_field() ?>
            <label>
                <span data-i18n="common_email">Email</span>
                <input type="email" name="email" required autocomplete="email">
            </label>
            <button type="submit" class="btn btn-primary" data-i18n="forgot_submit">Send Reset Link</button>
        </form>
        <p class="small-text">
            <span data-i18n="login_register_prompt">Remembered your password?</span>
            <a href="<?= $baseUrl ?>login" data-i18n="forgot_back_login">Back to login</a>
        </p>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
