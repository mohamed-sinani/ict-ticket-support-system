<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['admin']);

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { setFlash('Invalid security token. Please try again.', 'error'); redirect('departments.php'); }
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            $stmt = $conn->prepare('INSERT INTO departments (name) VALUES (?)');
            $stmt->bind_param('s', $name);
            $stmt->execute();
            setFlash('Department added successfully.');
        } else {
            setFlash('Department name is required.', 'error');
        }
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id > 0 && $name !== '') {
            $stmt = $conn->prepare('UPDATE departments SET name = ? WHERE id = ?');
            $stmt->bind_param('si', $name, $id);
            $stmt->execute();
            setFlash('Department updated successfully.');
        } else {
            setFlash('Unable to update department.', 'error');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM departments WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            setFlash('Department deleted successfully.');
        } else {
            setFlash('Unable to delete department.', 'error');
        }
    }

    redirect('departments.php');
}

$departments = getDepartments();
$pageTitle = 'Departments | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<h2 data-i18n="admin_departments_title">Departments Management</h2>
<section class="panel-card">
    <h3 data-i18n="admin_add_department">Add Department</h3>
    <form method="POST" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <input type="text" name="name" required placeholder="Department Name" data-i18n-placeholder="admin_department_name_placeholder">
        <button class="btn btn-primary" type="submit" data-i18n="common_add">Add</button>
    </form>
</section>

<section class="panel-card">
    <h3 data-i18n="admin_existing_departments">Existing Departments</h3>
    <div class="departments-grid">
        <?php foreach ($departments as $dept): ?>
            <div class="dept-card">
                <form method="POST" class="table-edit-form" id="department-form-<?= (int) $dept['id'] ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int) $dept['id'] ?>">
                    <input type="text" name="name" value="<?= e($dept['name']) ?>" required>
                </form>

                <div class="dept-actions">
                    <button class="btn btn-secondary" type="submit" form="department-form-<?= (int) $dept['id'] ?>" data-i18n="common_update">Update</button>
                    <form method="POST" class="inline-delete-form" id="delete-form-<?= (int) $dept['id'] ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $dept['id'] ?>">
                        <button class="btn btn-danger js-delete-dept" type="button" data-form-id="delete-form-<?= (int) $dept['id'] ?>" data-dept-name="<?= e($dept['name']) ?>" data-i18n="common_delete">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<div id="confirmOverlay" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="confirm-modal" role="document">
        <h3 id="confirmTitle">Confirm</h3>
        <p id="confirmMessage">Are you sure?</p>
        <div class="confirm-actions">
            <button type="button" class="btn btn-secondary" id="confirmCancel">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmOk">Delete</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let pendingForm = null;
    const overlay = document.getElementById('confirmOverlay');
    const msgEl = document.getElementById('confirmMessage');
    const okBtn = document.getElementById('confirmOk');
    const cancelBtn = document.getElementById('confirmCancel');

    function showConfirm(formId, name) {
        pendingForm = document.getElementById(formId);
        msgEl.textContent = (window.appI18n ? window.appI18n.t('confirm_delete_department', 'Delete this department?') : 'Delete this department?') + '\n\n"' + name + '"';
        overlay.classList.remove('hidden');
        overlay.setAttribute('aria-hidden', 'false');
        okBtn.focus();
    }

    function hideConfirm() {
        overlay.classList.add('hidden');
        overlay.setAttribute('aria-hidden', 'true');
        pendingForm = null;
    }

    document.querySelectorAll('.js-delete-dept').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const formId = btn.getAttribute('data-form-id');
            const name = btn.getAttribute('data-dept-name') || '';
            showConfirm(formId, name);
        });
    });

    cancelBtn.addEventListener('click', function () {
        hideConfirm();
    });

    okBtn.addEventListener('click', function () {
        if (pendingForm) {
            pendingForm.submit();
        }
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !overlay.classList.contains('hidden')) {
            hideConfirm();
        }
    });
});
</script>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
