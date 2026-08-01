<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['ict']);

$conn = db();
$user = currentUser();
$userId = (int) $user['id'];

$errors = [];
$profile = [
    'full_name' => $user['full_name'] ?? '',
    'email' => $user['email'] ?? '',
    'phone' => '',
    'job_title' => '',
];

$stmt = $conn->prepare('SELECT full_name, email, phone, job_title, password FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$currentRow = $stmt->get_result()->fetch_assoc();
if ($currentRow) {
    $profile = array_merge($profile, $currentRow);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { setFlash('Invalid security token. Please try again.', 'error'); redirect('settings.php'); }
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $jobTitle = trim($_POST['job_title'] ?? '');
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $email === '' || $phone === '' || $jobTitle === '') {
        $errors[] = 'Please complete all required fields.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    $emailStmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $emailStmt->bind_param('si', $email, $userId);
    $emailStmt->execute();
    if ($emailStmt->get_result()->fetch_assoc()) {
        $errors[] = 'That email address is already in use.';
    }

    $updatePassword = $newPassword !== '' || $confirmPassword !== '' || $currentPassword !== '';
    $newPasswordHash = null;

    if ($updatePassword) {
        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $errors[] = 'Complete the current password, new password, and confirmation fields.';
        } elseif (!verifyPassword($currentPassword, (string) $currentRow['password'] ?? '')) {
            $errors[] = 'Current password is incorrect.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } else {
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        }
    }

    if (!$errors) {
        if ($newPasswordHash !== null) {
            $update = $conn->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, job_title = ?, password = ? WHERE id = ?');
            $update->bind_param('sssssi', $fullName, $email, $phone, $jobTitle, $newPasswordHash, $userId);
        } else {
            $update = $conn->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, job_title = ? WHERE id = ?');
            $update->bind_param('ssssi', $fullName, $email, $phone, $jobTitle, $userId);
        }

        $update->execute();
        $_SESSION['user']['full_name'] = $fullName;
        $_SESSION['user']['email'] = $email;
        setFlash('Settings updated successfully.');
        redirect('settings.php');
    }

    setFlash(implode(' ', $errors), 'error');
    $profile['full_name'] = $fullName;
    $profile['email'] = $email;
    $profile['phone'] = $phone;
    $profile['job_title'] = $jobTitle;
}

$pageTitle = 'Settings | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<div class="db-hero">
    <div class="db-hero-left">
        <div class="db-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v2"></path><path d="M12 21v2"></path><path d="m4.22 4.22 1.42 1.42"></path><path d="m18.36 18.36 1.42 1.42"></path><path d="M1 12h2"></path><path d="M21 12h2"></path><path d="m4.22 19.78 1.42-1.42"></path><path d="m18.36 5.64 1.42-1.42"></path></svg>
            <span data-i18n="subnav_settings">Settings</span>
        </div>
        <h1 data-i18n="admin_settings_title">Settings</h1>
        <p class="db-sub-desc" data-i18n="settings_page_subtitle">Update your profile details and password.</p>
    </div>
    <div class="db-hero-actions">
        <span class="db-date-pill">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <?= e(date('l, F j, Y')) ?>
        </span>
    </div>
</div>

<form method="POST" autocomplete="off">
    <?= csrf_field() ?>
    <section class="db-panel" style="margin-bottom:20px;">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <div>
                    <h3 class="db-panel-title">Profile Information</h3>
                    <p class="db-panel-subtitle">Update your name, email, phone, and job title.</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body">
            <div class="db-form-grid">
                <label><span data-i18n="common_full_name">Full Name</span>
                    <input type="text" name="full_name" required value="<?= e((string) $profile['full_name']) ?>" placeholder="Full name">
                </label>
                <label><span data-i18n="common_email">Email</span>
                    <input type="email" name="email" required value="<?= e((string) $profile['email']) ?>" placeholder="name@institution.edu">
                </label>
                <label><span data-i18n="common_phone">Phone</span>
                    <input type="text" name="phone" required value="<?= e((string) $profile['phone']) ?>" placeholder="07XXXXXXXX">
                </label>
                <label><span data-i18n="common_job_title">Job Title</span>
                    <input type="text" name="job_title" required value="<?= e((string) $profile['job_title']) ?>" placeholder="Your job title">
                </label>
            </div>
        </div>
    </section>

    <section class="db-panel" style="margin-bottom:20px;">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title">Security</h3>
                    <p class="db-panel-subtitle">Change your password when needed.</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body">
            <div class="db-form-grid">
                <label><span data-i18n="admin_current_password_label">Current Password</span>
                    <span class="password-field">
                        <input type="password" name="current_password" placeholder="Enter current password" data-password-field>
                        <button type="button" class="password-toggle" aria-label="Show password" data-password-toggle>
                            <span aria-hidden="true">👁</span>
                        </button>
                    </span>
                </label>
                <label><span data-i18n="admin_new_password_label">New Password</span>
                    <span class="password-field">
                        <input type="password" name="new_password" minlength="8" placeholder="Minimum 8 characters" data-password-field>
                        <button type="button" class="password-toggle" aria-label="Show password" data-password-toggle>
                            <span aria-hidden="true">👁</span>
                        </button>
                    </span>
                </label>
                <label class="full"><span data-i18n="admin_confirm_password_label">Confirm New Password</span>
                    <span class="password-field">
                        <input type="password" name="confirm_password" minlength="8" placeholder="Repeat new password" data-password-field>
                        <button type="button" class="password-toggle" aria-label="Show password" data-password-toggle>
                            <span aria-hidden="true">👁</span>
                        </button>
                    </span>
                </label>
            </div>
            <p class="small-text" style="margin:12px 0 0;">Leave password fields blank if you only want to update your profile information.</p>
        </div>
    </section>

    <div class="db-form-actions">
        <button type="submit" class="btn btn-primary" data-i18n="admin_settings_save">Save Changes</button>
    </div>
</form>
</section>
</div>
<script>
document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
        const wrapper = button.closest('.password-field');
        const input = wrapper ? wrapper.querySelector('[data-password-field]') : null;
        if (!input) return;

        const isHidden = input.getAttribute('type') === 'password';
        input.setAttribute('type', isHidden ? 'text' : 'password');
        button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        button.classList.toggle('is-visible', isHidden);
        button.querySelector('span').textContent = isHidden ? '🙈' : '👁';
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
