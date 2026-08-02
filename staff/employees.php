<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['ict']);

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { setFlash('Invalid security token. Please try again.', 'error'); redirect('employees.php'); }
    $employeeId = (int) ($_POST['user_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $deptId = (int) ($_POST['department_id'] ?? 0);

    if ($employeeId > 0 && $fullName !== '' && $email !== '') {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, department_id = ? WHERE id = ? AND role = 'employee'");
        $stmt->bind_param('sssii', $fullName, $email, $phone, $deptId, $employeeId);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            setFlash('Employee information updated successfully.');
        } else {
            setFlash('No changes made or user is not an employee.', 'error');
        }
    } else {
        setFlash('Please fill in all required fields.', 'error');
    }
    redirect('employees.php');
}

$departments = getDepartments();
$sql = "SELECT u.*, d.name as department_name 
        FROM users u 
        LEFT JOIN departments d ON d.id = u.department_id 
        WHERE u.role = 'employee' 
        ORDER BY u.full_name ASC";
$employees = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Employee Directory | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<div class="db-hero">
    <div class="db-hero-left">
        <div class="db-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span data-i18n="staff_employees">Employees</span>
        </div>
        <h1 data-i18n="staff_employees">Employee Management</h1>
        <p class="db-sub-desc" data-i18n="employees_page_subtitle">Manage employee profiles and contact details.</p>
    </div>
    <div class="db-hero-actions">
        <span class="db-date-pill">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <?= e(date('l, F j, Y')) ?>
        </span>
    </div>
</div>

<section class="db-panel">
    <div class="db-panel-header">
        <div class="db-panel-header-left">
            <div class="db-panel-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div>
                <h3 class="db-panel-title" data-i18n="staff_employees">Employee Directory</h3>
                <p class="db-panel-subtitle"><?= count($employees) ?> employee(s) registered</p>
            </div>
        </div>
        <span class="db-chart-badge"><?= count($employees) ?></span>
    </div>
    <div class="db-panel-body pad-none">
        <div class="db-table-wrap">
            <table class="db-table">
                <thead>
                    <tr>
                        <th data-i18n="common_full_name">Full Name</th>
                        <th data-i18n="common_employee_number">Badge No.</th>
                        <th data-i18n="common_email">Email</th>
                        <th data-i18n="common_phone">Phone</th>
                        <th data-i18n="common_department">Department</th>
                        <th data-i18n="common_action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td><?= e($emp['full_name']) ?></td>
                            <td><span class="db-mono"><?= e($emp['employee_number']) ?></span></td>
                            <td><?= e($emp['email']) ?></td>
                            <td><?= e((string) $emp['phone']) ?></td>
                            <td><?= e((string) $emp['department_name']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="btn btn-icon" data-open-employee-edit
                                        data-id="<?= (int) $emp['id'] ?>"
                                        data-name="<?= e($emp['full_name']) ?>"
                                        data-email="<?= e($emp['email']) ?>"
                                        data-phone="<?= e((string) $emp['phone']) ?>"
                                        data-dept="<?= (int) $emp['department_id'] ?>"
                                        aria-label="Edit employee" title="Edit employee">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($employees)): ?>
                        <tr><td colspan="6" class="small-text">No employees found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<div id="editEmployeeModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="editEmployeeTitle">
    <div class="confirm-modal db-modal-card" role="document">
        <div class="modal-head">
            <h3 id="editEmployeeTitle" data-i18n="staff_edit_employee">Edit Employee</h3>
            <button type="button" class="modal-close" data-close-employee-modal aria-label="Close">&times;</button>
        </div>
        <p class="modal-desc">Update employee profile details.</p>
        <form method="POST" id="editEmployeeForm">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" id="eeUserId" value="0">
            <div class="db-form-grid">
                <label><span data-i18n="common_full_name">Full Name</span><input type="text" name="full_name" id="eeName" required></label>
                <label><span data-i18n="common_email">Email</span><input type="email" name="email" id="eeEmail" required></label>
                <label><span data-i18n="common_phone">Phone</span><input type="text" name="phone" id="eePhone"></label>
                <label><span data-i18n="common_department">Department</span>
                    <select name="department_id" id="eeDept">
                        <option value="" data-i18n="report_department_placeholder">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= (int) $dept['id'] ?>"><?= e($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="confirm-actions">
                <button type="button" class="btn btn-secondary" data-close-employee-modal data-i18n="common_cancel">Cancel</button>
                <button type="submit" class="btn btn-primary" data-i18n="common_update">Update</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('editEmployeeModal');
    const form = document.getElementById('editEmployeeForm');
    if (!modal || !form) return;

    function openEditModal(btn) {
        document.getElementById('eeUserId').value = btn.getAttribute('data-id') || '0';
        document.getElementById('eeName').value = btn.getAttribute('data-name') || '';
        document.getElementById('eeEmail').value = btn.getAttribute('data-email') || '';
        document.getElementById('eePhone').value = btn.getAttribute('data-phone') || '';
        document.getElementById('eeDept').value = btn.getAttribute('data-dept') || '';
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.getElementById('eeName').focus();
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('[data-open-employee-edit]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openEditModal(btn);
        });
    });
    document.querySelectorAll('[data-close-employee-modal]').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
});
</script>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>