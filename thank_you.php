<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$next = $_GET['next'] ?? '';
$allowedNext = in_array($next, ['report.php', 'track.php'], true) ? $next : '';
$loginUrl = 'login.php' . ($allowedNext !== '' ? '?next=' . urlencode($allowedNext) : '');

if (isLoggedIn()) {
    redirect($allowedNext !== '' ? $allowedNext : homePathForRole(currentUser()['role']));
}

$pageTitle = 'Registration Complete | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-card auth-card-wide register-card">
    <br>
    <h2 data-i18n="thank_you_title">Thank you for registering</h2>
    <p class="small-text" data-i18n="thank_you_intro">Your account has been created successfully.</p>

    <div class="alert alert-success" role="status" aria-live="polite">
        <p>
            <strong>
                <span data-i18n="thank_you_redirect_prefix">You will be redirected to the login page in</span>
                <span id="thankYouCountdown">1</span>
                seconds.
            </strong>
        </p>
    </div>

    <p class="small-text" data-i18n="thank_you_fallback">If the redirect does not start automatically, use the button below.</p>
    <a class="btn btn-primary" href="<?= e($loginUrl) ?>" data-i18n="thank_you_login_now">Go to Login</a>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var countdownElement = document.getElementById('thankYouCountdown');
    var redirectUrl = <?= json_encode($loginUrl, JSON_UNESCAPED_SLASHES) ?>;
    var remaining = 5;

    var timer = setInterval(function () {
        remaining -= 1;

        if (countdownElement) {
            countdownElement.textContent = String(Math.max(remaining, 0));
        }

        if (remaining <= 0) {
            clearInterval(timer);
            window.location.href = redirectUrl;
        }
    }, 1000);
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>