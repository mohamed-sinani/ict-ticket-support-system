<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$next = $_GET['next'] ?? '';
$allowedNext = in_array($next, ['report', 'track'], true) ? $next : '';

if (isLoggedIn()) {
    $role = currentUser()['role'];
    redirect($allowedNext !== '' ? $allowedNext : homePathForRole($role));
}

$otpPending = isset($_SESSION['otp_pending_user_id']) && isset($_SESSION['otp_pending_role']);
$error = '';
$selectedRole = 'employee';
$otpEmail = '';
$otpSuccess = false;

// If an OTP is pending, forward the user to the dedicated OTP page.
if ($otpPending) {
    // Preserve any 'next' destination.
    $redirect = 'otp' . ($allowedNext !== '' ? '?next=' . urlencode($allowedNext) : '');
    redirect($redirect);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = $_POST['role'] ?? 'employee';
    $failureReason = null;

    if (!verify_csrf()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        if (authenticate($email, $password, $selectedRole, $failureReason)) {
            $user = currentUser();

            if (approvalStatus() === 'pending') {
                unset($_SESSION['user']);
                redirect('waiting');
                exit;
            }

            if (approvalStatus() === 'rejected') {
                $reason = trim((string) ($user['review_reason'] ?? ''));
                unset($_SESSION['user']);
                $error = 'Your account was not approved.' . ($reason !== '' ? ' Reason: ' . $reason : ' Please contact the ICT support team.');
            } else {
                $otpCode = generateOtp($user['id']);

                // Store pending OTP info
                $_SESSION['otp_pending_user_id'] = $user['id'];
                $_SESSION['otp_pending_role'] = $user['role'];
                $_SESSION['otp_pending_email'] = $user['email'];
                $_SESSION['otp_pending_full_name'] = $user['full_name'];

                // Ensure user is not logged in yet
                unset($_SESSION['user']);

                $otpEmail = $user['email'];
                $otpPending = true;

                $plainMessage = 'Your ICT Support login verification code is: ' . $otpCode . '. This code expires in ' . OTP_EXPIRY_MINUTES . ' minutes.';
                $htmlMessage = buildOtpEmail($user['full_name'], $otpCode);
                sendNotificationEmail($user['email'], 'Your ICT Support Login Code', $plainMessage, $htmlMessage);

                $redirect = 'otp' . ($allowedNext !== '' ? '?next=' . urlencode($allowedNext) : '');
                redirect($redirect);
                exit;
            }
        } else {
            $error = $failureReason === 'wrong_role'
                ? 'Wrong role selected for this account. Please choose the correct login role.'
                : 'Invalid credentials.';
        }
    }
}
?>

<?php
$pageTitle = ($otpPending ? 'Verify OTP' : 'Employee Login') . ' | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-card">
    <h2 data-i18n="login_title">Login</h2>
    <?php if (isset($_GET['registered'])): ?>
        <p class="alert alert-success" data-i18n="login_registered_success">Registration successful. You can now login.</p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="alert alert-danger"><?php echo e($error); ?></p>
    <?php endif; ?>
    <form method="POST" class="form-grid">
        <?= csrf_field() ?>
        <label>
            <span>Login as</span>
            <select name="role" required data-no-placeholder>
                <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="ict" <?= $selectedRole === 'ict' ? 'selected' : '' ?>>ICT Stuff</option>
                <option value="employee" <?= $selectedRole === 'employee' ? 'selected' : '' ?>>Employee</option>
            </select>
        </label>
        <label>
            <span data-i18n="login_email_label">Email</span>
            <input type="email" name="email" required>
        </label>
        <label>
            <span data-i18n="login_password_label">Password</span>
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary" data-i18n="login_submit">Login</button>
    </form>
    <p class="small-text">
        <a href="<?= $baseUrl ?>forgot_password" data-i18n="login_forgot_link">Forgot password?</a>
    </p>
    <p class="small-text">
        <span data-i18n="login_register_prompt">New employee?</span>
        <a href="<?= $baseUrl ?>register<?php echo $allowedNext !== '' ? '?next=' . e($allowedNext) : '' ?>" data-i18n="login_register_link">Register with your badge</a>
    </p>
    <p class="small-text auth-notice" data-i18n="login_support_notice">If you face any problem, please contact the ICT support team: 0763364721</p>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
