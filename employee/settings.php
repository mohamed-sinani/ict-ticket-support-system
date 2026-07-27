<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['employee']);

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
<h2 data-i18n="admin_settings_title">Settings</h2>
<section class="panel-card settings-page">
    <h3 data-i18n="admin_settings_title">Settings</h3>
    <p class="small-text" data-i18n="admin_settings_intro">Update your profile details and password.</p>

    <form method="POST" class="settings-form" autocomplete="off">
        <section class="panel-card settings-category">
            <div class="settings-category-head">
                <h4>Profile Information</h4>
                <p>Update your name, email, phone, and job title.</p>
            </div>
            <div class="settings-category-grid">
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
        </section>

        <section class="panel-card settings-category settings-password-card">
            <div class="settings-category-head">
                <h4>Security</h4>
                <p>Change your password when needed.</p>
            </div>
            <div class="settings-category-grid">
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
                <label><span data-i18n="admin_confirm_password_label">Confirm New Password</span>
                    <span class="password-field">
                        <input type="password" name="confirm_password" minlength="8" placeholder="Repeat new password" data-password-field>
                        <button type="button" class="password-toggle" aria-label="Show password" data-password-toggle>
                            <span aria-hidden="true">👁</span>
                        </button>
                    </span>
                </label>
            </div>
            <p class="small-text">Leave password fields blank if you only want to update your profile information.</p>
        </section>

        <div class="auth-actions settings-actions">
            <button type="submit" class="btn btn-primary" data-i18n="admin_settings_save">Save Changes</button>
        </div>
    </form>
</section>
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
