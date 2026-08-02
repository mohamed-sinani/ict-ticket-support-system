<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['admin']);

$conn = db();
$editUserId = (int) ($_GET['edit'] ?? 0);
$editUser = null;

if ($editUserId > 0) {
    $editStmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $editStmt->bind_param('i', $editUserId);
    $editStmt->execute();
    $editUser = $editStmt->get_result()->fetch_assoc() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { setFlash('Invalid security token. Please try again.', 'error'); redirect('users.php'); }
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $fullName = trim($_POST['full_name'] ?? '');
        $employeeNumber = trim($_POST['employee_number'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $jobTitle = trim($_POST['job_title'] ?? '');
        $departmentId = (int) ($_POST['department_id'] ?? 0);
        $role = $_POST['role'] ?? 'employee';
        $password = trim($_POST['password'] ?? '');
        $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : '';

        if ($fullName !== '' && $email !== '' && $password !== '' && in_array($role, ['admin', 'ict', 'employee'], true)) {
            $stmt = $conn->prepare('INSERT INTO users (full_name, employee_number, phone, email, job_title, department_id, role, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssiss', $fullName, $employeeNumber, $phone, $email, $jobTitle, $departmentId, $role, $passwordHash);
            $stmt->execute();
            setFlash('User created successfully.');
        } else {
            setFlash('Unable to create user. Please check required fields and provide a password.', 'error');
        }
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $employeeNumber = trim($_POST['employee_number'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $jobTitle = trim($_POST['job_title'] ?? '');
        $departmentId = (int) ($_POST['department_id'] ?? 0);
        $role = $_POST['role'] ?? 'employee';
        $password = trim($_POST['password'] ?? '');

        if ($id > 0 && $fullName !== '' && $email !== '' && in_array($role, ['admin', 'ict', 'employee'], true)) {
            if ($password !== '') {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE users SET full_name = ?, employee_number = ?, phone = ?, email = ?, job_title = ?, department_id = ?, role = ?, password = ? WHERE id = ?');
                $stmt->bind_param('sssssissi', $fullName, $employeeNumber, $phone, $email, $jobTitle, $departmentId, $role, $passwordHash, $id);
            } else {
                $stmt = $conn->prepare('UPDATE users SET full_name = ?, employee_number = ?, phone = ?, email = ?, job_title = ?, department_id = ?, role = ? WHERE id = ?');
                $stmt->bind_param('sssssisi', $fullName, $employeeNumber, $phone, $email, $jobTitle, $departmentId, $role, $id);
            }
            $stmt->execute();
            setFlash('User updated successfully.');
        } else {
            setFlash('Unable to update user. Please check required fields.', 'error');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && $id !== currentUser()['id']) {
            $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            setFlash('User deleted successfully.');
        } elseif ($id === currentUser()['id']) {
            setFlash('You cannot delete your own account.', 'error');
        } else {
            setFlash('Unable to delete user.', 'error');
        }
    }

    redirect('users.php');
}

$departments = getDepartments();
$formValues = [
    'id' => $editUser['id'] ?? 0,
    'full_name' => $editUser['full_name'] ?? '',
    'employee_number' => $editUser['employee_number'] ?? '',
    'phone' => $editUser['phone'] ?? '',
    'email' => $editUser['email'] ?? '',
    'job_title' => $editUser['job_title'] ?? '',
    'department_id' => $editUser['department_id'] ?? '',
    'role' => $editUser['role'] ?? 'employee',
];
$users = $conn->query('SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id ORDER BY u.created_at DESC')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Users Management | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<div class="db-hero">
    <div class="db-hero-left">
        <div class="db-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span data-i18n="subnav_users">Users</span>
        </div>
        <h1 data-i18n="admin_users_title">Users Management</h1>
        <p class="db-sub-desc" data-i18n="users_page_subtitle">Create, edit, and manage user accounts and their roles.</p>
    </div>
    <div class="db-hero-actions">
        <button type="button" class="db-view-btn primary" data-open-user-modal data-i18n="admin_add_user">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add User
        </button>
    </div>
</div>

<?php
$pendingApprovals = 0;
$admins = 0;
$employees = 0;
foreach ($users as $u) {
    if (($u['approval_status'] ?? 'approved') === 'pending') { $pendingApprovals++; }
    if ($u['role'] === 'admin') { $admins++; }
    if ($u['role'] === 'employee') { $employees++; }
}
?>
<div class="db-stats-grid">
    <a class="db-stat-card c-blue" href="users" style="text-decoration:none;">
        <div class="db-stat-top">
            <div class="db-stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <span class="db-period-badge alltime" data-i18n="admin_total_users">Total Users</span>
        </div>
        <div class="db-stat-value"><?= count($users) ?></div>
        <div class="db-stat-label" data-i18n="admin_total_users">Total Users</div>
    </a>
    <a class="db-stat-card c-amber" href="approvals" style="text-decoration:none;">
        <div class="db-stat-top">
            <div class="db-stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <span class="db-period-badge open" data-i18n="admin_pending_approvals">Pending</span>
        </div>
        <div class="db-stat-value"><?= $pendingApprovals ?></div>
        <div class="db-stat-label" data-i18n="admin_pending_approvals">Pending Approvals</div>
    </a>
    <div class="db-stat-card c-violet">
        <div class="db-stat-top">
            <div class="db-stat-icon purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            </div>
            <span class="db-period-badge alltime" data-i18n="admin_administrators">Admins</span>
        </div>
        <div class="db-stat-value"><?= $admins ?></div>
        <div class="db-stat-label" data-i18n="admin_administrators">Administrators</div>
    </div>
    <div class="db-stat-card c-green">
        <div class="db-stat-top">
            <div class="db-stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <span class="db-period-badge alltime" data-i18n="admin_employees">Employees</span>
        </div>
        <div class="db-stat-value"><?= $employees ?></div>
        <div class="db-stat-label" data-i18n="admin_employees">Employees</div>
    </div>
</div>

<?php if ($editUser): ?>
<section class="db-panel" id="userForm" style="margin-bottom:20px;">
    <div class="db-panel-header">
        <div class="db-panel-header-left">
            <div class="db-panel-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
            </div>
            <div>
                <h3 class="db-panel-title"><?= $editUser ? 'Edit User' : 'Add User' ?></h3>
                <p class="db-panel-subtitle"><?= $editUser ? 'Update this account profile and role.' : 'Register a new account for the institution.' ?></p>
            </div>
        </div>
        <?php if ($editUser): ?>
            <a class="db-view-btn" href="users" data-i18n="admin_cancel_edit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                Cancel Edit
            </a>
        <?php endif; ?>
    </div>
    <div class="db-panel-body">
        <form method="POST" class="db-form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
            <input type="hidden" name="id" value="<?= (int) $formValues['id'] ?>">
            <label><span data-i18n="common_full_name">Full Name</span><input type="text" name="full_name" value="<?= e((string) $formValues['full_name']) ?>" required></label>
            <label><span data-i18n="common_employee_number">Employee Number</span><input type="text" name="employee_number" value="<?= e((string) $formValues['employee_number']) ?>"></label>
            <label><span data-i18n="common_phone_number">Phone Number</span><input type="text" name="phone" value="<?= e((string) $formValues['phone']) ?>"></label>
            <label><span data-i18n="common_email">Email</span><input type="email" name="email" value="<?= e((string) $formValues['email']) ?>" required></label>
            <label><span data-i18n="common_job_title">Job Title</span><input type="text" name="job_title" value="<?= e((string) $formValues['job_title']) ?>"></label>
            <label><span data-i18n="common_department">Department</span>
                <select name="department_id">
                    <option value="" data-i18n="report_department_placeholder">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int) $dept['id'] ?>" <?= (string) $formValues['department_id'] === (string) $dept['id'] ? 'selected' : '' ?>><?= e($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span data-i18n="common_role">Role</span>
                <select name="role" required>
                    <option value="employee" data-i18n="common_role_employee" <?= $formValues['role'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                    <option value="ict" data-i18n="common_role_ict" <?= $formValues['role'] === 'ict' ? 'selected' : '' ?>>ICT Staff</option>
                    <option value="admin" data-i18n="common_role_admin" <?= $formValues['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </label>
            <label class="full"><span data-i18n="admin_password_label">Password (leave blank to keep current)</span>
                <input type="password" name="password" value="" placeholder="<?= $editUser ? 'Leave blank to keep current' : 'Required for new users' ?>">
            </label>
            <div class="db-form-actions">
                <button class="btn btn-primary" type="submit"><?= $editUser ? 'Save Changes' : 'Create User' ?></button>
                <?php if ($editUser): ?>
                    <a class="btn btn-secondary" href="users">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>
<?php endif; ?>

<div id="addUserModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="addUserTitle">
    <div class="confirm-modal db-modal-card" role="document">
        <div class="modal-head">
            <h3 id="addUserTitle" data-i18n="admin_add_user">Add User</h3>
            <button type="button" class="modal-close" data-close-user-modal aria-label="Close">&times;</button>
        </div>
        <p class="modal-desc">Register a new account for the institution</p>
        <div class="wizard-steps">
            <div class="wizard-step active" data-step="1">
                <span class="wizard-dot">1</span>
                <span class="wizard-label" data-i18n="modal_step_personal">Personal Info</span>
            </div>
            <div class="wizard-line"></div>
            <div class="wizard-step" data-step="2">
                <span class="wizard-dot">2</span>
                <span class="wizard-label" data-i18n="modal_step_work">Work Info</span>
            </div>
            <div class="wizard-line"></div>
            <div class="wizard-step" data-step="3">
                <span class="wizard-dot">3</span>
                <span class="wizard-label" data-i18n="modal_step_password">Password</span>
            </div>
        </div>
        <form method="POST" id="addUserForm" class="db-form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="wizard-panel" data-panel="1">
                <label><span data-i18n="common_full_name">Full Name</span><input type="text" name="full_name" required></label>
                <label><span data-i18n="common_employee_number">Employee Number</span><input type="text" name="employee_number"></label>
                <label><span data-i18n="common_phone_number">Phone Number</span><input type="text" name="phone"></label>
                <label><span data-i18n="common_email">Email</span><input type="email" name="email" required></label>
            </div>
            <div class="wizard-panel" data-panel="2" hidden>
                <label><span data-i18n="common_job_title">Job Title</span><input type="text" name="job_title"></label>
                <label><span data-i18n="common_department">Department</span>
                    <select name="department_id">
                        <option value="" data-i18n="report_department_placeholder">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= (int) $dept['id'] ?>"><?= e($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span data-i18n="common_role">Role</span>
                    <select name="role" required>
                        <option value="employee" data-i18n="common_role_employee">Employee</option>
                        <option value="ict" data-i18n="common_role_ict">ICT Staff</option>
                        <option value="admin" data-i18n="common_role_admin">Admin</option>
                    </select>
                </label>
            </div>
            <div class="wizard-panel" data-panel="3" hidden>
                <label class="full"><span data-i18n="admin_password_label">Password</span>
                    <input type="password" name="password" required placeholder="Required for new users">
                </label>
            </div>
            <div class="db-form-actions full wizard-actions">
                <button type="button" class="btn btn-secondary" data-close-user-modal data-i18n="common_cancel">Cancel</button>
                <button type="button" class="btn btn-secondary" data-wizard-prev data-i18n="common_back">Back</button>
                <button type="button" class="btn btn-primary" data-wizard-next data-i18n="common_next">Next</button>
                <button type="submit" class="btn btn-primary" data-wizard-submit data-i18n="common_add">Add</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var addUserModal = document.getElementById('addUserModal');
    var addUserForm = document.getElementById('addUserForm');
    if (!addUserModal || !addUserForm) return;

    var wizardPanels = addUserForm.querySelectorAll('.wizard-panel');
    var wizardSteps = addUserModal.querySelectorAll('.wizard-step');
    var wizardLines = addUserModal.querySelectorAll('.wizard-line');
    var btnPrev = addUserForm.querySelector('[data-wizard-prev]');
    var btnNext = addUserForm.querySelector('[data-wizard-next]');
    var btnSubmit = addUserForm.querySelector('[data-wizard-submit]');
    var currentStep = 1;
    var totalSteps = wizardPanels.length;

    function showStep(step) {
        currentStep = Math.min(Math.max(1, step), totalSteps);
        wizardPanels.forEach(function (panel, i) {
            panel.hidden = (i + 1) !== currentStep;
        });
        wizardSteps.forEach(function (el, i) {
            var n = i + 1;
            el.classList.toggle('active', n === currentStep);
            el.classList.toggle('done', n < currentStep);
        });
        wizardLines.forEach(function (line, i) {
            line.classList.toggle('done', i < currentStep - 1);
        });
        btnPrev.style.display = currentStep === 1 ? 'none' : '';
        btnNext.style.display = currentStep === totalSteps ? 'none' : '';
        btnSubmit.style.display = currentStep === totalSteps ? '' : 'none';
        var focusEl = wizardPanels[currentStep - 1].querySelector('input, select');
        if (focusEl) focusEl.focus();
    }

    btnNext.addEventListener('click', function () {
        if (addUserForm.checkValidity()) {
            showStep(currentStep + 1);
        } else {
            addUserForm.reportValidity();
        }
    });
    btnPrev.addEventListener('click', function () {
        showStep(currentStep - 1);
    });

    function openAddUserModal() {
        addUserForm.reset();
        showStep(1);
        addUserModal.classList.remove('hidden');
        addUserModal.setAttribute('aria-hidden', 'false');
        var first = addUserForm.querySelector('input[name="full_name"]');
        if (first) first.focus();
    }
    function closeAddUserModal() {
        addUserModal.classList.add('hidden');
        addUserModal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('[data-open-user-modal]').forEach(function (btn) {
        btn.addEventListener('click', openAddUserModal);
    });
    document.querySelectorAll('[data-close-user-modal]').forEach(function (btn) {
        btn.addEventListener('click', closeAddUserModal);
    });
    addUserModal.addEventListener('click', function (e) {
        if (e.target === addUserModal) closeAddUserModal();
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !addUserModal.classList.contains('hidden')) closeAddUserModal();
    });
});
</script>

<section class="db-panel">
    <div class="db-panel-header">
        <div class="db-panel-header-left">
            <div class="db-panel-icon violet">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div>
                <h3 class="db-panel-title" data-i18n="admin_all_users">All Users</h3>
                <p class="db-panel-subtitle"><?= count($users) ?> account(s) in the system</p>
            </div>
        </div>
        <span class="db-chart-badge"><?= count($users) ?></span>
    </div>
    <div class="db-panel-body pad-none">
        <div class="db-table-wrap">
            <table class="db-table">
                <thead>
                    <tr>
                        <th data-i18n="common_name">Name</th>
                        <th data-i18n="common_role">Role</th>
                        <th data-i18n="common_approval">Approval</th>
                        <th data-i18n="admin_employee_no">Employee No.</th>
                        <th data-i18n="common_email">Email</th>
                        <th data-i18n="common_department">Department</th>
                        <th data-i18n="common_registered">Registered</th>
                        <th data-i18n="common_action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <?php
                        $nameParts = preg_split('/\s+/', trim((string) $u['full_name']));
                        $firstPart = $nameParts[0] ?? '';
                        $lastPart = count($nameParts) > 1 ? end($nameParts) : '';
                        $initials = mb_strtoupper(mb_substr($firstPart, 0, 1) . mb_substr($lastPart !== '' && $lastPart !== $firstPart ? $lastPart : '', 0, 1));
                        if ($initials === '') { $initials = '?'; }
                        $apStatus = $u['approval_status'] ?? 'approved';
                        $apClass = $apStatus === 'approved' ? 'resolved' : ($apStatus === 'pending' ? 'pending' : 'rejected');
                        $apLabel = $apStatus === 'approved' ? 'Approved' : ($apStatus === 'pending' ? 'Pending' : 'Rejected');
                        ?>
                        <tr>
                            <td>
                                <div class="db-user-cell">
                                    <span class="db-t-avatar"><?= e($initials) ?></span>
                                    <span class="db-t-name"><?= e($u['full_name']) ?></span>
                                </div>
                            </td>
                            <td><span class="db-role-pill <?= e(strtolower((string) $u['role'])) ?>"><?= e($u['role']) ?></span></td>
                            <td><span class="db-status-pill <?= $apClass ?>"><?= $apLabel ?></span></td>
                            <td><span class="db-mono"><?= e((string) $u['employee_number']) ?></span></td>
                            <td><?= e($u['email']) ?></td>
                            <td><?= e((string) $u['department_name']) ?></td>
                            <td><span class="db-mono"><?= $u['created_at'] ? date('d M Y', strtotime($u['created_at'])) : '—' ?></span></td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-icon" href="users?edit=<?= (int) $u['id'] ?>" title="Edit" aria-label="Edit user">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </a>
                                    <form method="POST" onsubmit="return confirm(window.appI18n ? window.appI18n.t('confirm_delete_user', 'Delete user?') : 'Delete user?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="btn btn-icon danger" title="Delete" aria-label="Delete user">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
