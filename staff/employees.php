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

    // Strict check: Only update if the target user is an 'employee'
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
<h2 data-i18n="subnav_users">Employee Management</h2>

<section class="panel-card">
    <div class="table-wrap">
        <table>
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
                            <td><code><?= e($emp['employee_number']) ?></code></td>
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
                    <tr><td colspan="5">No employees found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>