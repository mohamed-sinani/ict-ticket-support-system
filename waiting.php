<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) {
    redirect(homePathForRole(currentUser()['role']));
}

$pageTitle = 'Awaiting Approval | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-card auth-card-wide">
    <div class="auth-status-icon pending" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
    </div>
    <h2 data-i18n="waiting_title">Account Awaiting Approval</h2>
    <p class="small-text" data-i18n="waiting_intro">Your registration has been received and is waiting for review by the administrator.</p>

    <div class="alert alert-waiting" role="status" aria-live="polite">
        <p><strong data-i18n="waiting_notice">You will be able to log in as soon as your account is approved.</strong></p>
        <p class="small-text" data-i18n="waiting_notice_sub">An email will be sent to you once the administrator approves or rejects your request.</p>
    </div>

    <p class="small-text" data-i18n="waiting_support">If you believe this is taking too long, please contact the ICT support team: 0763364721</p>

    <div class="auth-actions" style="margin-top:18px;">
        <a class="btn btn-secondary" href="<?= $baseUrl ?>" data-i18n="nav_home">Home</a>
        <a class="btn btn-primary" href="<?= $baseUrl ?>login" data-i18n="waiting_login_link">Try Login Again</a>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
