<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['admin']);

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { setFlash('Invalid security token. Please try again.', 'error'); redirect('approvals'); }
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    $userStmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ? AND role = 'employee' LIMIT 1");
    $userStmt->bind_param('i', $id);
    $userStmt->execute();
    $target = $userStmt->get_result()->fetch_assoc();

    if (!$target) {
        setFlash('Unable to find the requested account.', 'error');
        redirect('approvals');
    }

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE users SET approval_status = 'approved', approved_at = NOW(), review_reason = NULL WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $plainMessage = 'Hello ' . $target['full_name'] . ",\n\n"
            . 'Your ICT Support account has been approved. You can now log in and start submitting tickets.' . "\n\n"
            . 'Log in here: ' . absoluteUrl('login');
        $htmlMessage = buildAccountApprovedEmail($target['full_name']);
        sendNotificationEmail($target['email'], 'Your ICT Support Account Has Been Approved', $plainMessage, $htmlMessage);

        setFlash('Account approved and notification email sent.');
    }

    if ($action === 'reject') {
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            setFlash('Please provide a reason for rejection.', 'error');
            redirect('approvals');
        }

        $stmt = $conn->prepare("UPDATE users SET approval_status = 'rejected', review_reason = ? WHERE id = ?");
        $stmt->bind_param('si', $reason, $id);
        $stmt->execute();

        $plainMessage = 'Hello ' . $target['full_name'] . ",\n\n"
            . 'Unfortunately, your ICT Support account registration was not approved.' . "\n\n"
            . 'Reason: ' . $reason . "\n\n"
            . 'If you believe this is a mistake, please contact the ICT support team.';
        $htmlMessage = buildAccountRejectedEmail($target['full_name'], $reason);
        sendNotificationEmail($target['email'], 'Update on Your ICT Support Account Registration', $plainMessage, $htmlMessage);

        setFlash('Account rejected and notification email sent.');
    }

    redirect('approvals');
}

$pending = $conn->query(
    "SELECT u.id, u.full_name, u.employee_number, u.phone, u.email, u.job_title, u.created_at,
            d.name AS department_name
     FROM users u
     LEFT JOIN departments d ON d.id = u.department_id
     WHERE u.approval_status = 'pending'
     ORDER BY u.created_at ASC"
)->fetch_all(MYSQLI_ASSOC);

$reviewed = $conn->query(
    "SELECT u.id, u.full_name, u.employee_number, u.email, u.approval_status, u.approved_at, u.review_reason, u.created_at
     FROM users u
     WHERE u.approval_status IN ('approved', 'rejected')
     ORDER BY u.created_at DESC
     LIMIT 15"
)->fetch_all(MYSQLI_ASSOC);

$counts = [
    'pending' => (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE approval_status = 'pending'")->fetch_assoc()['c'],
    'approved' => (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE approval_status = 'approved'")->fetch_assoc()['c'],
    'rejected' => (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE approval_status = 'rejected'")->fetch_assoc()['c'],
];

$pageTitle = 'Account Approvals | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<div class="db-hero">
    <div class="db-hero-left">
        <div class="db-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span data-i18n="subnav_approvals">Approvals</span>
        </div>
        <h1 data-i18n="admin_approvals_title">Account Approvals</h1>
        <p class="db-sub-desc" data-i18n="admin_approvals_subtitle">Review and approve employee registrations before they can access the system.</p>
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
            <div class="db-panel-icon violet">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div>
                <h3 class="db-panel-title" data-i18n="admin_approvals_pending">Pending Registrations</h3>
                <p class="db-panel-subtitle"><?= count($pending) ?> employee registration(s) awaiting review</p>
            </div>
        </div>
        <span class="db-chart-badge"><?= count($pending) ?></span>
    </div>
    <div class="db-panel-body pad-none">
        <div class="db-table-wrap">
            <table class="db-table">
                <thead>
                    <tr>
                        <th data-i18n="common_name">Name</th>
                        <th data-i18n="admin_employee_no">Employee No.</th>
                        <th data-i18n="common_email">Email</th>
                        <th data-i18n="common_phone">Phone</th>
                        <th data-i18n="common_department">Department</th>
                        <th data-i18n="common_job_title">Job Title</th>
                        <th data-i18n="common_registered">Registered</th>
                        <th data-i18n="common_action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $u): ?>
                        <tr>
                            <td>
                                <div class="db-cell-main"><?= e($u['full_name']) ?></div>
                            </td>
                            <td><span class="db-mono"><?= e((string) $u['employee_number']) ?></span></td>
                            <td><?= e($u['email']) ?></td>
                            <td><?= e((string) $u['phone']) ?></td>
                            <td><?= e((string) $u['department_name']) ?></td>
                            <td><?= e((string) $u['job_title']) ?></td>
                            <td><span class="small-text"><?= e(date('M j, Y', strtotime((string) $u['created_at']))) ?></span></td>
                            <td>
                                <div class="table-actions">
                                    <form method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm" data-i18n="admin_approve">Approve</button>
                                    </form>
                                    <button type="button" class="btn btn-danger btn-sm" data-open-reject
                                        data-id="<?= (int) $u['id'] ?>"
                                        data-name="<?= e($u['full_name']) ?>"
                                        data-email="<?= e($u['email']) ?>"
                                        data-i18n="admin_reject">Reject</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pending)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="db-empty">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <h4 data-i18n="admin_approvals_none">No pending registrations</h4>
                                    <p data-i18n="admin_approvals_none_sub">New employee accounts will appear here for review.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="db-panel">
    <div class="db-panel-header">
        <div class="db-panel-header-left">
            <div class="db-panel-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div>
                <h3 class="db-panel-title" data-i18n="admin_approvals_recent">Recently Reviewed</h3>
                <p class="db-panel-subtitle"><?= $counts['approved'] ?> approved &middot; <?= $counts['rejected'] ?> rejected</p>
            </div>
        </div>
    </div>
    <div class="db-panel-body pad-none">
        <div class="db-table-wrap">
            <table class="db-table">
                <thead>
                    <tr>
                        <th data-i18n="common_name">Name</th>
                        <th data-i18n="admin_employee_no">Employee No.</th>
                        <th data-i18n="common_email">Email</th>
                        <th data-i18n="common_status">Status</th>
                        <th data-i18n="common_reason">Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviewed as $u): ?>
                        <tr>
                            <td><?= e($u['full_name']) ?></td>
                            <td><span class="db-mono"><?= e((string) $u['employee_number']) ?></span></td>
                            <td><?= e($u['email']) ?></td>
                            <td><span class="db-status-pill <?= $u['approval_status'] === 'approved' ? 'resolved' : 'rejected' ?>"><?= e($u['approval_status']) ?></span></td>
                            <td class="small-text"><?= e((string) $u['review_reason']) ?: '&mdash;' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($reviewed)): ?>
                        <tr><td colspan="5" class="small-text" data-i18n="admin_approvals_no_reviewed">No accounts have been reviewed yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<div id="rejectModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="rejectModalTitle">
    <div class="confirm-modal db-modal-card" role="document">
        <div class="modal-head">
            <h3 id="rejectModalTitle" data-i18n="admin_reject">Reject Registration</h3>
            <button type="button" class="modal-close" data-close-reject-modal aria-label="Close">&times;</button>
        </div>
        <p class="modal-desc" id="rejectModalDesc">Provide a reason for rejecting this registration. The employee will be notified by email.</p>
        <form method="POST" id="rejectForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="id" id="rejectUserId" value="0">
            <label class="modal-label">
                <span data-i18n="admin_reject_reason">Reason for rejection</span>
                <textarea name="reason" id="rejectReason" rows="3" required placeholder="e.g. Employee number could not be verified"></textarea>
            </label>
            <div class="confirm-actions">
                <button type="button" class="btn btn-secondary" data-close-reject-modal data-i18n="common_cancel">Cancel</button>
                <button type="submit" class="btn btn-danger" data-i18n="admin_reject">Reject</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('rejectModal');
    const form = document.getElementById('rejectForm');
    if (!modal || !form) return;

    function openRejectModal(btn) {
        document.getElementById('rejectUserId').value = btn.getAttribute('data-id') || '0';
        document.getElementById('rejectReason').value = '';
        document.getElementById('rejectModalDesc').textContent = 'Rejecting ' + (btn.getAttribute('data-name') || 'this account') + ' (' + (btn.getAttribute('data-email') || '') + '). Provide a reason below.';
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.getElementById('rejectReason').focus();
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('[data-open-reject]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openRejectModal(btn);
        });
    });
    document.querySelectorAll('[data-close-reject-modal]').forEach(function (btn) {
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
