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
                        <th data-i18n="common_department">Department</th>
                        <th data-i18n="common_action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $emp['id'] ?>">
                                <td>
                                    <input type="text" name="full_name" value="<?= e($emp['full_name']) ?>" required>
                                </td>
                                <td><span class="db-mono"><?= e($emp['employee_number']) ?></span></td>
                                <td>
                                    <input type="email" name="email" value="<?= e($emp['email']) ?>" required>
                                </td>
                                <td>
                                    <select name="department_id">
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= (int) $dept['id'] ?>" <?= (int)$emp['department_id'] === (int)$dept['id'] ? 'selected' : '' ?>>
                                                <?= e($dept['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <label>
                                            <span class="small-text" data-i18n="common_phone">Phone</span>
                                            <input type="text" name="phone" value="<?= e((string)$emp['phone']) ?>">
                                        </label>
                                        <button type="submit" class="btn btn-primary btn-sm" data-i18n="common_update">Update</button>
                                    </div>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($employees)): ?>
                        <tr><td colspan="5" class="small-text">No employees found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>