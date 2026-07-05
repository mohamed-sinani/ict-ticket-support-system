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

$error = '';
$selectedRole = 'employee';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = $_POST['role'] ?? 'employee';

    $allowedRoles = ['admin', 'ict', 'employee'];
    if (!in_array($selectedRole, $allowedRoles, true)) {
        $selectedRole = 'employee';
    }

    $failureReason = null;

    if (authenticate($email, $password, $selectedRole, $failureReason)) {
        $role = currentUser()['role'];
        redirect($allowedNext !== '' ? $allowedNext : homePathForRole($role));
    }

    $error = $failureReason === 'wrong_role'
        ? 'Wrong role selected for this account. Please choose the correct login role.'
        : 'Invalid credentials.';
}

$pageTitle = 'Employee Login | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-card">
    <br>
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
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
