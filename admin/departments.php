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

$departments = $conn->query(
    'SELECT d.id, d.name,
        (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id) AS member_count,
        (SELECT COUNT(*) FROM tickets t WHERE t.department_id = d.id) AS ticket_count
     FROM departments d
     ORDER BY d.name ASC'
)->fetch_all(MYSQLI_ASSOC);
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
                    <div class="dept-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l7-4 7 4v14"></path><path d="M9 21v-8h6v8"></path></svg>
                    </div>
                    <div class="dept-card-info">
                        <h4 class="dept-card-name"><?= e($dept['name']) ?></h4>
                        <p class="dept-card-meta">
                            <span><?= (int) $dept['member_count'] ?> <span data-i18n="common_members">members</span></span>
                            <span class="dept-card-dot"></span>
                            <span><?= (int) $dept['ticket_count'] ?> <span data-i18n="common_tickets">tickets</span></span>
                        </p>
                    </div>
                    <div class="dept-actions">
                        <button type="button" class="btn btn-icon" data-open-dept-edit
                            data-id="<?= (int) $dept['id'] ?>"
                            data-name="<?= e($dept['name']) ?>"
                            aria-label="Edit department" title="Edit department">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"></path></svg>
                        </button>
                        <button type="button" class="btn btn-icon danger js-delete-dept"
                            data-form-id="delete-form-<?= (int) $dept['id'] ?>"
                            data-dept-name="<?= e($dept['name']) ?>"
                            aria-label="Delete department" title="Delete department">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </button>
                        <form method="POST" class="inline-delete-form" id="delete-form-<?= (int) $dept['id'] ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $dept['id'] ?>">
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
<div id="deptModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="deptModalTitle">
    <div class="confirm-modal db-modal-card" role="document">
        <div class="modal-head">
            <h3 id="deptModalTitle" data-i18n="admin_add_department">Add Department</h3>
            <button type="button" class="modal-close" data-close-dept-modal aria-label="Close">&times;</button>
        </div>
        <p class="modal-desc" id="deptModalDesc">Create a new department for ticket routing</p>
        <form method="POST" id="deptForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" id="deptAction" value="create">
            <input type="hidden" name="id" id="deptId" value="0">
            <label class="modal-label">
                <span data-i18n="admin_department_name_placeholder">Department Name</span>
                <input type="text" name="name" id="deptName" required placeholder="e.g. Academics">
            </label>
            <div class="confirm-actions">
                <button type="button" class="btn btn-secondary" data-close-dept-modal data-i18n="common_cancel">Cancel</button>
                <button type="submit" class="btn btn-primary" id="deptSubmitBtn" data-i18n="common_add">Add</button>
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
    const deptModal = document.getElementById('deptModal');
    const deptForm = document.getElementById('deptForm');
    const deptAction = document.getElementById('deptAction');
    const deptId = document.getElementById('deptId');
    const deptName = document.getElementById('deptName');
    const deptTitle = document.getElementById('deptModalTitle');
    const deptDesc = document.getElementById('deptModalDesc');
    const deptSubmit = document.getElementById('deptSubmitBtn');

    function openAddModal() {
        deptForm.reset();
        deptAction.value = 'create';
        deptId.value = '0';
        deptTitle.textContent = window.appI18n ? window.appI18n.t('admin_add_department', 'Add Department') : 'Add Department';
        deptDesc.textContent = 'Create a new department for ticket routing';
        deptSubmit.textContent = window.appI18n ? window.appI18n.t('common_add', 'Add') : 'Add';
        deptModal.classList.remove('hidden');
        deptModal.setAttribute('aria-hidden', 'false');
        if (deptName) deptName.focus();
    }

    function openEditModal(btn) {
        deptForm.reset();
        deptAction.value = 'update';
        deptId.value = btn.getAttribute('data-id') || '0';
        deptName.value = btn.getAttribute('data-name') || '';
        deptTitle.textContent = window.appI18n ? window.appI18n.t('admin_edit_department', 'Edit Department') : 'Edit Department';
        deptDesc.textContent = 'Update the department name below.';
        deptSubmit.textContent = window.appI18n ? window.appI18n.t('common_update', 'Update') : 'Update';
        deptModal.classList.remove('hidden');
        deptModal.setAttribute('aria-hidden', 'false');
        if (deptName) deptName.focus();
    }

    function closeDeptModal() {
        deptModal.classList.add('hidden');
        deptModal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('[data-open-dept-modal]').forEach(function (btn) {
        btn.addEventListener('click', openAddModal);
    });

    document.querySelectorAll('[data-open-dept-edit]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openEditModal(btn);
        });
    });

    document.querySelectorAll('[data-close-dept-modal]').forEach(function (btn) {
        btn.addEventListener('click', closeDeptModal);
    });

    deptModal.addEventListener('click', function (e) {
        if (e.target === deptModal) {
            closeDeptModal();
        }
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !deptModal.classList.contains('hidden')) {
            closeDeptModal();
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
