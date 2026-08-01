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
<div class="db-hero">
    <div class="db-hero-left">
        <div class="db-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"></path><path d="M5 21V7l7-4 7 4v14"></path><path d="M9 21v-8h6v8"></path></svg>
            <span data-i18n="subnav_departments">Departments</span>
        </div>
        <h1 data-i18n="admin_departments_title">Departments Management</h1>
        <p class="db-sub-desc" data-i18n="departments_page_subtitle">Organise the institution into departments for cleaner ticket routing.</p>
    </div>
    <div class="db-hero-actions">
        <button type="button" class="db-view-btn primary" data-open-dept-modal data-i18n="admin_add_department">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add Department
        </button>
    </div>
</div>

<section class="db-panel">
    <div class="db-panel-header">
        <div class="db-panel-header-left">
            <div class="db-panel-icon violet">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-6 9 6v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><path d="M9 21v-8h6v8"></path></svg>
            </div>
            <div>
                <h3 class="db-panel-title" data-i18n="admin_existing_departments">Existing Departments</h3>
                <p class="db-panel-subtitle"><?= count($departments) ?> department(s) configured</p>
            </div>
        </div>
        <span class="db-chart-badge"><?= count($departments) ?></span>
    </div>
    <div class="db-panel-body">
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
                        <button class="btn btn-secondary btn-sm" type="submit" form="department-form-<?= (int) $dept['id'] ?>" data-i18n="common_update">Update</button>
                        <form method="POST" class="inline-delete-form" id="delete-form-<?= (int) $dept['id'] ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $dept['id'] ?>">
                            <button class="btn btn-danger btn-sm js-delete-dept" type="button" data-form-id="delete-form-<?= (int) $dept['id'] ?>" data-dept-name="<?= e($dept['name']) ?>" data-i18n="common_delete">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($departments)): ?>
                <div class="db-empty" style="grid-column:1/-1;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l7-4 7 4v14"></path><path d="M9 21v-8h6v8"></path></svg>
                    <h4>No departments yet</h4>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<div id="addDeptModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="addDeptTitle">
    <div class="confirm-modal db-modal-card" role="document">
        <div class="modal-head">
            <h3 id="addDeptTitle" data-i18n="admin_add_department">Add Department</h3>
            <button type="button" class="modal-close" data-close-dept-modal aria-label="Close">&times;</button>
        </div>
        <p class="modal-desc">Create a new department for ticket routing</p>
        <form method="POST" id="addDeptForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <label class="modal-label">
                <span data-i18n="admin_department_name_placeholder">Department Name</span>
                <input type="text" name="name" required placeholder="e.g. Academics" data-i18n-placeholder="admin_department_name_placeholder">
            </label>
            <div class="confirm-actions">
                <button type="button" class="btn btn-secondary" data-close-dept-modal data-i18n="common_cancel">Cancel</button>
                <button type="submit" class="btn btn-primary" data-i18n="common_add">Add</button>
            </div>
        </form>
    </div>
</div>
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
    const addModal = document.getElementById('addDeptModal');
    const addForm = document.getElementById('addDeptForm');

    function openAddModal() {
        addModal.classList.remove('hidden');
        addModal.setAttribute('aria-hidden', 'false');
        const input = addForm.querySelector('input[name="name"]');
        if (input) {
            input.value = '';
            input.focus();
        }
    }

    function closeAddModal() {
        addModal.classList.add('hidden');
        addModal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('[data-open-dept-modal]').forEach(function (btn) {
        btn.addEventListener('click', openAddModal);
    });

    document.querySelectorAll('[data-close-dept-modal]').forEach(function (btn) {
        btn.addEventListener('click', closeAddModal);
    });

    addModal.addEventListener('click', function (e) {
        if (e.target === addModal) {
            closeAddModal();
        }
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !addModal.classList.contains('hidden')) {
            closeAddModal();
        }
    });

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
