<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$next = $_GET['next'] ?? '';
$allowedNext = in_array($next, ['report.php', 'track.php'], true) ? $next : '';

if (isLoggedIn()) {
    $role = currentUser()['role'];
    redirect($allowedNext !== '' ? $allowedNext : homePathForRole($role));
}

$otpPending = isset($_SESSION['otp_pending_user_id']) && isset($_SESSION['otp_pending_role']);
$error = '';
$selectedRole = 'employee';
$otpEmail = '';
$otpSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($otpPending && isset($_POST['otp_code'])) {
        $otpCode = trim($_POST['otp_code'] ?? '');
        $userId = (int) $_SESSION['otp_pending_user_id'];
        $role = $_SESSION['otp_pending_role'];
        $email = $_SESSION['otp_pending_email'] ?? '';
        $fullName = $_SESSION['otp_pending_full_name'] ?? '';

        $result = verifyOtp($userId, $otpCode);

        if ($result === null) {
            $_SESSION['user'] = [
                'id' => $userId,
                'full_name' => $fullName,
                'email' => $email,
                'role' => $role,
            ];
            unset($_SESSION['otp_pending_user_id'], $_SESSION['otp_pending_role'], $_SESSION['otp_pending_email'], $_SESSION['otp_pending_full_name']);
            $otpSuccess = true;
            redirect($allowedNext !== '' ? $allowedNext : homePathForRole($role));
        }

        if ($result === 'expired') {
            $error = 'OTP code has expired. Please login again.';
            unset($_SESSION['otp_pending_user_id'], $_SESSION['otp_pending_role'], $_SESSION['otp_pending_email'], $_SESSION['otp_pending_full_name']);
            $otpPending = false;
        } elseif ($result === 'max_attempts') {
            $error = 'Too many failed attempts. Please login again.';
            unset($_SESSION['otp_pending_user_id'], $_SESSION['otp_pending_role'], $_SESSION['otp_pending_email'], $_SESSION['otp_pending_full_name']);
            $otpPending = false;
        } elseif ($result === 'invalid') {
            $error = 'Invalid verification code. Please try again.';
        } else {
            $error = 'No OTP pending. Please login again.';
            $otpPending = false;
        }
    } elseif (!$otpPending) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $selectedRole = $_POST['role'] ?? 'employee';

        $allowedRoles = ['admin', 'ict', 'employee'];
        if (!in_array($selectedRole, $allowedRoles, true)) {
            $selectedRole = 'employee';
        }

        $failureReason = null;

        if (authenticate($email, $password, $selectedRole, $failureReason)) {
            $user = currentUser();
            $otpCode = generateOtp($user['id']);

            $_SESSION['otp_pending_user_id'] = $user['id'];
            $_SESSION['otp_pending_role'] = $user['role'];
            $_SESSION['otp_pending_email'] = $user['email'];
            $_SESSION['otp_pending_full_name'] = $user['full_name'];

            unset($_SESSION['user']);

            $otpEmail = $user['email'];
            $otpPending = true;

            $plainMessage = 'Your ICT Support login verification code is: ' . $otpCode . '. This code expires in ' . OTP_EXPIRY_MINUTES . ' minutes.';
            $htmlMessage = buildOtpEmail($user['full_name'], $otpCode);
            sendNotificationEmail($user['email'], 'Your ICT Support Login Code', $plainMessage, $htmlMessage);
        } else {
            $error = $failureReason === 'wrong_role'
                ? 'Wrong role selected for this account. Please choose the correct login role.'
                : 'Invalid credentials.';
        }
    } else {
        $error = 'Please enter the verification code.';
    }
}

$pageTitle = ($otpPending ? 'Verify OTP' : 'Employee Login') . ' | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-card">
    <br>
    <?php if ($otpPending): ?>
        <h2 data-i18n="otp_title">Enter Verification Code</h2>
        <p class="small-text" style="margin-bottom:18px;color:#475569;">A 6-digit code has been sent to <strong><?= e($otpEmail ?: ($_SESSION['otp_pending_email'] ?? '')) ?></strong>. It expires in <?= OTP_EXPIRY_MINUTES ?> minutes.</p>
        <?php if ($error): ?>
            <p class="alert alert-danger"><?= e($error) ?></p>
        <?php endif; ?>
        <form method="POST" class="form-grid">
            <label><span data-i18n="otp_code_label">Verification Code</span>
                <input type="text" name="otp_code" required maxlength="<?= OTP_LENGTH ?>" pattern="[0-9]{<?= OTP_LENGTH ?>}" inputmode="numeric" autocomplete="one-time-code" placeholder="<?= OTP_LENGTH ?>-digit code" style="text-align:center;font-size:1.4em;letter-spacing:.2em;">
            </label>
            <button type="submit" class="btn btn-primary" data-i18n="otp_verify_btn">Verify & Login</button><br>
        </form>
        <p class="small-text"><a href="login.php">Back to Login</a></p>
    <?php else: ?>
        <h2 data-i18n="login_title">Login</h2>
        <?php if (isset($_GET['registered'])): ?>
            <p class="alert alert-success" data-i18n="login_registered_success">Registration successful. You can now login.</p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="alert alert-danger"><?= e($error) ?></p>
        <?php endif; ?>
        <form method="POST" class="form-grid">
            <label><span>Login as</span>
                <select name="role" required data-no-placeholder>
                    <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="ict" <?= $selectedRole === 'ict' ? 'selected' : '' ?>>ICT Stuff</option>
                    <option value="employee" <?= $selectedRole === 'employee' ? 'selected' : '' ?>>Employee</option>
                </select>
            </label>
            <label><span data-i18n="login_email_label">Email</span>
                <input type="email" name="email" required>
            </label>
            <label><span data-i18n="login_password_label">Password</span>
                <input type="password" name="password" required>
            </label>
            <button type="submit" class="btn btn-primary" data-i18n="login_submit">Login</button><br>
        </form>
        <p class="small-text"><span data-i18n="login_register_prompt">New employee?</span> <a href="register.php<?= $allowedNext !== '' ? '?next=' . e($allowedNext) : '' ?>" data-i18n="login_register_link">Register with your badge</a></p>
        <p class="small-text auth-notice" data-i18n="login_support_notice">If you face any problem, please contact the ICT support team: 0763364721</p>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
