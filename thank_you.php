<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$next = $_GET['next'] ?? '';
$allowedNext = in_array($next, ['report', 'track'], true) ? $next : '';
$continueUrl = 'waiting' . ($allowedNext !== '' ? '?next=' . urlencode($allowedNext) : '');

if (isLoggedIn()) {
    redirect($allowedNext !== '' ? $allowedNext : homePathForRole(currentUser()['role']));
}

$pageTitle = 'Registration Complete | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-card auth-card-wide register-card">
    <br>
    <h2 data-i18n="thank_you_pending_title">Registration Received</h2>
    <p class="small-text" data-i18n="thank_you_pending_intro">Your registration has been submitted and is now awaiting administrator approval.</p>

    <div class="alert alert-waiting" role="status" aria-live="polite">
        <p>
            <strong>
                <span data-i18n="thank_you_pending_hint">Once approved, you will receive an email and can start submitting tickets.</span>
            </strong>
        </p>
        <p class="small-text" data-i18n="waiting_notice_sub">An email will be sent to you once the administrator approves or rejects your request.</p>
    </div>

    <p class="small-text" data-i18n="thank_you_fallback">If the redirect does not start automatically, use the button below.</p>
    <a class="btn btn-primary" href="<?= e($continueUrl) ?>" data-i18n="waiting_login_link">Try Login Again</a>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var countdownElement = document.getElementById('thankYouCountdown');
    var redirectUrl = <?= json_encode($continueUrl, JSON_UNESCAPED_SLASHES) ?>;
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
