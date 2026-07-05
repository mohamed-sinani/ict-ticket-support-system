<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$next = $_GET['next'] ?? '';
$allowedNext = in_array($next, ['report.php', 'track.php'], true) ? $next : '';

if (isLoggedIn()) {
    redirect($allowedNext !== '' ? $allowedNext : homePathForRole(currentUser()['role']));
}

$conn = db();
$departments = getDepartments();
$errors = [];
$old = [
    'full_name' => '',
    'employee_number' => '',
    'phone' => '',
    'email' => '',
    'job_title' => '',
    'department_id' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['employee_number'] = strtoupper(trim($_POST['employee_number'] ?? ''));
    $old['phone'] = trim($_POST['phone'] ?? '');
    $old['email'] = strtolower(trim($_POST['email'] ?? ''));
    $old['job_title'] = trim($_POST['job_title'] ?? '');
    $old['department_id'] = trim($_POST['department_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $departmentId = (int) $old['department_id'];

    if ($old['full_name'] === '' || $old['employee_number'] === '' || $old['phone'] === '' || $old['email'] === '' || $old['job_title'] === '' || $departmentId <= 0 || $password === '' || $confirmPassword === '') {
        $errors[] = 'Please complete all required fields.';
    }

    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($departmentId > 0) {
        $deptStmt = $conn->prepare('SELECT id FROM departments WHERE id = ? LIMIT 1');
        $deptStmt->bind_param('i', $departmentId);
        $deptStmt->execute();
        if (!$deptStmt->get_result()->fetch_assoc()) {
            $errors[] = 'Please select a valid department.';
        }
    }

    if (!$errors) {
        $duplicateStmt = $conn->prepare('SELECT id FROM users WHERE email = ? OR employee_number = ? LIMIT 1');
        $duplicateStmt->bind_param('ss', $old['email'], $old['employee_number']);
        $duplicateStmt->execute();
        if ($duplicateStmt->get_result()->fetch_assoc()) {
            $errors[] = 'Email or employee badge already exists.';
        }
    }

    if (!$errors) {
        $role = 'employee';
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (full_name, employee_number, phone, email, job_title, department_id, role, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssiss', $old['full_name'], $old['employee_number'], $old['phone'], $old['email'], $old['job_title'], $departmentId, $role, $passwordHash);
        $stmt->execute();

        redirect('thank_you.php' . ($allowedNext !== '' ? '?next=' . urlencode($allowedNext) : ''));
    }
}

$pageTitle = 'Employee Registration | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-card auth-card-wide register-card">
    <br>
    <h2 data-i18n="register_title">Employee Registration</h2>
    <p class="small-text" data-i18n="register_intro">Create your account using your official employee badge number.</p>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="stepper auth-stepper" aria-label="Registration steps">
        <span class="step active" data-register-step-indicator="1">1. Details</span>
        <span class="step" data-register-step-indicator="2">2. Account</span>
    </div>

    <form method="POST" class="form-grid auth-form" id="registerForm" novalidate>
        <div class="wizard-step active" data-register-step="1">
            <div class="auth-form-grid">
                <label><span data-i18n="common_full_name">Full Name</span>
                    <input type="text" name="full_name" value="<?= e($old['full_name']) ?>" required data-i18n-placeholder="common_full_name" placeholder="Full name">
                </label>
                <label><span data-i18n="register_badge_label">Employee badge / check number</span>
                    <input type="text" name="employee_number" value="<?= e($old['employee_number']) ?>" required data-i18n-placeholder="register_badge_label" placeholder="Employee badge / check number">
                </label>
                <label><span data-i18n="common_email">Email</span>
                    <input type="email" name="email" value="<?= e($old['email']) ?>" required data-i18n-placeholder="common_email" placeholder="name@institution.edu">
                </label>
                <label><span data-i18n="register_phone_label">Phone Number</span>
                    <input type="tel" name="phone" value="<?= e($old['phone']) ?>" required data-i18n-placeholder="register_phone_label" placeholder="07XXXXXXXX">
                </label>
            </div>
            <div class="wizard-actions auth-actions">
                <button type="button" class="btn btn-primary" data-register-next data-i18n="report_continue">Continue</button>
            </div>
        </div>

        <div class="wizard-step" data-register-step="2">
            <div class="auth-form-grid">
                <label><span data-i18n="common_job_title">Job Title</span>
                    <input type="text" name="job_title" value="<?= e($old['job_title']) ?>" required data-i18n-placeholder="common_job_title" placeholder="Your job title">
                </label>
                <label><span data-i18n="common_department">Department</span>
                    <select name="department_id" required>
                        <option value="" data-i18n="report_department_placeholder">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= (int) $dept['id'] ?>" <?= (string) $dept['id'] === $old['department_id'] ? 'selected' : '' ?>><?= e($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span data-i18n="register_password_label">Password</span>
                    <input type="password" name="password" minlength="8" required data-i18n-placeholder="register_password_label" placeholder="Minimum 8 characters">
                </label>
                <label><span data-i18n="register_confirm_password_label">Confirm Password</span>
                    <input type="password" name="confirm_password" minlength="8" required data-i18n-placeholder="register_confirm_password_label" placeholder="Repeat password">
                </label>
            </div>
            <div class="wizard-actions auth-actions">
                <button type="button" class="btn btn-secondary" data-register-prev data-i18n="report_back">Back</button>
                <button type="submit" class="btn btn-primary" data-i18n="register_submit">Create Account</button>
            </div>
        </div>
    </form>

    <p class="small-text"><span data-i18n="register_login_prompt">Already registered?</span> <a href="login.php<?= $allowedNext !== '' ? '?next=' . e($allowedNext) : '' ?>" target="_self" data-i18n="register_login_link">Login</a></p>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registerForm');
    if (!form) return;

    const steps = Array.from(form.querySelectorAll('[data-register-step]'));
    const indicators = Array.from(document.querySelectorAll('[data-register-step-indicator]'));

    function showStep(stepNumber) {
        steps.forEach(function (step) {
            step.classList.toggle('active', step.getAttribute('data-register-step') === String(stepNumber));
        });
        indicators.forEach(function (indicator) {
            indicator.classList.toggle('active', indicator.getAttribute('data-register-step-indicator') === String(stepNumber));
        });
    }

    function currentStepIsValid() {
        const activeStep = form.querySelector('[data-register-step].active');
        const controls = Array.from(activeStep.querySelectorAll('input, select, textarea'));
        return controls.every(function (control) {
            return control.reportValidity();
        });
    }

    form.addEventListener('click', function (event) {
        if (event.target.matches('[data-register-next]') && currentStepIsValid()) {
            showStep(2);
        }

        if (event.target.matches('[data-register-prev]')) {
            showStep(1);
        }
    });

    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            const firstInvalid = form.querySelector(':invalid');
            const invalidStep = firstInvalid ? firstInvalid.closest('[data-register-step]') : null;
            if (invalidStep) {
                showStep(invalidStep.getAttribute('data-register-step'));
                firstInvalid.reportValidity();
            }
        }
    });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
