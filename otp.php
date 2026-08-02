<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$allowedNext = $_GET['next'] ?? '';
$allowedNext = in_array($allowedNext, ['report', 'track'], true) ? $allowedNext : '';

// Ensure OTP pending info exists
if (!isset($_SESSION['otp_pending_user_id'], $_SESSION['otp_pending_role'])) {
    // No pending OTP, redirect to login
    redirect('login' . ($allowedNext !== '' ? '?next=' . urlencode($allowedNext) : ''));
    exit;
}

$error = '';
$otpEmail = $_SESSION['otp_pending_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        setFlash('Invalid security token. Please try again.', 'error');
        redirect('login');
    }

    // Resend OTP request
    if (isset($_POST['resend'])) {
        $userId = (int) $_SESSION['otp_pending_user_id'];
        $user = [
            'id' => $userId,
            'email' => $_SESSION['otp_pending_email'] ?? '',
            'full_name' => $_SESSION['otp_pending_full_name'] ?? '',
            'role' => $_SESSION['otp_pending_role'] ?? 'employee',
        ];
        $otpCode = generateOtp($userId);
        $plainMessage = 'Your ICT Support login verification code is: ' . $otpCode . '. This code expires in ' . OTP_EXPIRY_MINUTES . ' minutes.';
        $htmlMessage = buildOtpEmail($user['full_name'], $otpCode);
        sendNotificationEmail($user['email'], 'Your ICT Support Login Code', $plainMessage, $htmlMessage);
        $error = 'A new verification code has been sent.';
    }
    // Verify OTP
    elseif (isset($_POST['otp_code'])) {
        $otpCode = trim($_POST['otp_code'] ?? '');
        $userId = (int) $_SESSION['otp_pending_user_id'];
        $role = $_SESSION['otp_pending_role'];
        $email = $_SESSION['otp_pending_email'] ?? '';
        $fullName = $_SESSION['otp_pending_full_name'] ?? '';
        $result = verifyOtp($userId, $otpCode);
        if ($result === null) {
            // OTP valid – log the user in
            $_SESSION['user'] = [
                'id' => $userId,
                'full_name' => $fullName,
                'email' => $email,
                'role' => $role,
            ];
            if (!empty($_SESSION['otp_pending_remember_me'])) {
                session_regenerate_id(true);
                $_SESSION['remember_me'] = true;
                setcookie('ict_remember', '1', [
                    'expires' => time() + 7 * 86400,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
            // Clean pending data
            unset($_SESSION['otp_pending_user_id'], $_SESSION['otp_pending_role'], $_SESSION['otp_pending_email'], $_SESSION['otp_pending_full_name'], $_SESSION['otp_pending_remember_me']);
            // Redirect to intended page or role home
            redirect($allowedNext !== '' ? $allowedNext : homePathForRole($role));
            exit;
        }
        if ($result === 'expired') {
            $error = 'OTP code has expired. Please login again.';
        } elseif ($result === 'max_attempts') {
            $error = 'Too many failed attempts. Please login again.';
        } elseif ($result === 'invalid') {
            $error = 'Invalid verification code. Please try again.';
        } else {
            $error = 'Verification failed.';
        }
    }
}

$pageTitle = 'Enter OTP | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-card">
    <h2 data-i18n="otp_title">Enter Verification Code</h2>
    <p class="small-text" style="margin-bottom:18px;color:#475569;">
        A 6-digit code has been sent to <strong><?= e($otpEmail) ?></strong>. It expires in <?= OTP_EXPIRY_MINUTES ?> minutes.
    </p>
    <?php if ($error): ?>
        <p class="alert alert-danger"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="POST" class="form-grid">
        <?= csrf_field() ?>
        <?php if (!empty($_SESSION['otp_pending_remember_me'])): ?>
            <input type="hidden" name="remember_me" value="1">
        <?php endif; ?>
        <label>
            <span data-i18n="otp_code_label">Verification Code</span>
            <input type="text" name="otp_code" required maxlength="<?= OTP_LENGTH ?>" pattern="[0-9]{<?= OTP_LENGTH ?>}" inputmode="numeric" autocomplete="one-time-code" placeholder="<?= OTP_LENGTH ?>-digit code" style="text-align:center;font-size:1.4em;letter-spacing:.2em;">
        </label>
        <button type="submit" class="btn btn-primary" data-i18n="otp_verify_btn">Verify &amp; Login</button>
    </form>
    <form method="POST" class="form-grid" style="margin-top:1rem;">
        <?= csrf_field() ?>
        <input type="hidden" name="resend" value="1">
        <button type="submit" class="btn btn-secondary">Resend Code</button>
    </form>
    <p class="small-text"><a href="<?= $baseUrl ?>login<?= $allowedNext !== '' ? '?next=' . e($allowedNext) : '' ?>">Back to Login</a></p>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
