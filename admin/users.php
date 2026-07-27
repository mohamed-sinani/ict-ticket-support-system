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
<h2 data-i18n="admin_users_title">Users Management</h2>
<section class="panel-card">
    <h3><?= $editUser ? 'Edit User' : 'Add User' ?></h3>
    <form method="POST" class="form-grid">
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
        <label><span data-i18n="admin_password_label">Password (leave blank to keep current)</span>
            <input type="password" name="password" value="" placeholder="<?= $editUser ? 'Leave blank to keep current' : 'Required for new users' ?>">
        </label>
        <div class="table-actions">
            <button class="btn btn-primary" type="submit"><?= $editUser ? 'Save Changes' : 'Create User' ?></button>
            <?php if ($editUser): ?>
                <a class="btn btn-secondary" href="users.php">Cancel Edit</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel-card">
    <h3 data-i18n="admin_all_users">All Users</h3>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th data-i18n="common_name">Name</th>
                <th data-i18n="common_role">Role</th>
                <th data-i18n="admin_employee_no">Employee No.</th>
                <th data-i18n="common_email">Email</th>
                <th data-i18n="common_phone">Phone</th>
                <th data-i18n="common_job_title">Job Title</th>
                <th data-i18n="common_department">Department</th>
                <th data-i18n="common_action">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['full_name']) ?></td>
                    <td><?= strtoupper(e($u['role'])) ?></td>
                    <td><?= e((string) $u['employee_number']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e((string) $u['phone']) ?></td>
                    <td><?= e((string) $u['job_title']) ?></td>
                    <td><?= e((string) $u['department_name']) ?></td>
                    <td>
                        <div class="table-actions">
                            <a class="btn btn-secondary" href="users.php?edit=<?= (int) $u['id'] ?>">Edit</a>
                            <form method="POST" onsubmit="return confirm(window.appI18n ? window.appI18n.t('confirm_delete_user', 'Delete user?') : 'Delete user?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <button type="submit" class="btn btn-danger" data-i18n="common_delete">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
