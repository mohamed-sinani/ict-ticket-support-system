<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['admin']);

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { setFlash('Invalid security token. Please try again.', 'error'); redirect('tickets.php'); }
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $ictId = (int) ($_POST['ict_id'] ?? 0);

    if ($ticketId > 0 && $ictId > 0) {
        $status = STATUS_ASSIGNED;
        $stmt = $conn->prepare('UPDATE tickets SET assigned_to = ?, status = ? WHERE id = ?');
        $stmt->bind_param('isi', $ictId, $status, $ticketId);
        $stmt->execute();

        $detailsSql = 'SELECT t.tracking_code, t.status, emp.full_name AS employee_name,
                              d.name AS department_name, c.name AS category_name, sc.name AS subcategory_name,
                              ict.full_name AS assigned_name, ict.email AS assigned_email
                       FROM tickets t
                       JOIN users emp ON emp.id = t.employee_id
                       JOIN users ict ON ict.id = t.assigned_to
                       LEFT JOIN departments d ON d.id = t.department_id
                       LEFT JOIN categories c ON c.id = t.category_id
                       LEFT JOIN subcategories sc ON sc.id = t.subcategory_id
                       WHERE t.id = ? LIMIT 1';
        $detailsStmt = $conn->prepare($detailsSql);
        $detailsStmt->bind_param('i', $ticketId);
        $detailsStmt->execute();
        $ticketDetails = $detailsStmt->get_result()->fetch_assoc();

        if ($ticketDetails && !empty($ticketDetails['assigned_email'])) {
            $subject = 'ICT Ticket Assigned - ' . $ticketDetails['tracking_code'];
            $message = "Hello {$ticketDetails['assigned_name']},\n\nA ticket has been assigned to you by admin.\nTracking code: {$ticketDetails['tracking_code']}\nEmployee: {$ticketDetails['employee_name']}\nDepartment: " . ($ticketDetails['department_name'] ?? 'Not specified') . "\nIssue: " . trim((string) ($ticketDetails['category_name'] ?? '') . ' - ' . (string) ($ticketDetails['subcategory_name'] ?? ''), ' -') . "\nStatus: {$ticketDetails['status']}\n\nPlease open your assigned tickets page to continue working on it.\n";
            $htmlMessage = buildTicketAssignmentEmail($ticketDetails);
            $sent = sendNotificationEmail($ticketDetails['assigned_email'], $subject, $message, $htmlMessage);

            $notifStmt = $conn->prepare('INSERT INTO notifications (ticket_id, recipient_email, subject, message, is_sent) VALUES (?, ?, ?, ?, ?)');
            $isSent = $sent ? 1 : 0;
            $notifStmt->bind_param('isssi', $ticketId, $ticketDetails['assigned_email'], $subject, $message, $isSent);
            $notifStmt->execute();
        }

        addTicketTimeline($ticketId, currentUser()['id'], 'Ticket assigned/reassigned by admin.');
        setFlash('Ticket assigned successfully.');
    } else {
        setFlash('Unable to assign ticket.', 'error');
    }

    redirect('tickets.php');
}

$statusFilter = trim($_GET['status'] ?? '');
$departmentFilter = (int) ($_GET['department_id'] ?? 0);

$where = '1=1';
$params = [];
$types = '';

if ($statusFilter !== '') {
    $where .= ' AND t.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}
if ($departmentFilter > 0) {
    $where .= ' AND t.department_id = ?';
    $params[] = $departmentFilter;
    $types .= 'i';
}

$sql = "SELECT t.*, d.name AS department_name, c.name AS category_name, sc.name AS subcategory_name,
           emp.full_name AS employee_name,
           ict.full_name AS ict_name,
           att.file_path AS evidence_path,
           att.file_name AS evidence_name
        FROM tickets t
        LEFT JOIN departments d ON d.id = t.department_id
        LEFT JOIN categories c ON c.id = t.category_id
        LEFT JOIN subcategories sc ON sc.id = t.subcategory_id
        LEFT JOIN users emp ON emp.id = t.employee_id
        LEFT JOIN users ict ON ict.id = t.assigned_to
    LEFT JOIN attachments att ON att.id = (
        SELECT a2.id
        FROM attachments a2
        WHERE a2.ticket_id = t.id
        ORDER BY a2.created_at DESC, a2.id DESC
        LIMIT 1
    )
        WHERE {$where}
        ORDER BY t.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$departments = getDepartments();
$ictUsers = $conn->query("SELECT id, full_name FROM users WHERE role = 'ict' ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Ticket Oversight | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<div class="db-hero">
    <div class="db-hero-left">
        <div class="db-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9a3 3 0 0 0 0 6v3a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-3a3 3 0 0 0 0-6V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"></path><path d="M13 5v2"></path><path d="M13 17v2"></path><path d="M13 11v2"></path></svg>
            <span data-i18n="subnav_tickets">Tickets</span>
        </div>
        <h1 data-i18n="admin_ticket_oversight_title">Ticket Oversight</h1>
        <p class="db-sub-desc" data-i18n="tickets_page_subtitle">Review every ticket across the institution, assign ICT staff, and resolve issues.</p>
    </div>
    <div class="db-hero-actions">
        <span class="db-date-pill">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <?= e(date('l, F j, Y')) ?>
        </span>
    </div>
</div>

<section class="db-panel" style="margin-bottom:20px;">
    <div class="db-panel-header">
        <div class="db-panel-header-left">
            <div class="db-panel-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            </div>
            <div>
                <h3 class="db-panel-title" data-i18n="reports_filter_title">Filter Tickets</h3>
                <p class="db-panel-subtitle" data-i18n="reports_filter_sub">Narrow results by submission date</p>
            </div>
        </div>
        <?php if ($statusFilter !== '' || $departmentFilter > 0): ?>
            <a class="db-view-btn" href="tickets" data-i18n="common_clear">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                Clear
            </a>
        <?php endif; ?>
    </div>
    <div class="db-panel-body">
        <form method="GET" class="db-filter-bar">
            <label class="db-filter-field">
                <span data-i18n="common_status">Status</span>
                <select name="status">
                    <option value="" data-i18n="admin_all_statuses">All Statuses</option>
                    <?php foreach (TICKET_STATUSES as $status): ?>
                        <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="db-filter-field">
                <span data-i18n="common_department">Department</span>
                <select name="department_id">
                    <option value="" data-i18n="admin_all_departments">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int) $dept['id'] ?>" <?= $departmentFilter === (int) $dept['id'] ? 'selected' : '' ?>><?= e($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="db-view-btn primary" type="submit" data-i18n="common_filter">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                Filter
            </button>
        </form>
    </div>
</section>

<section class="db-panel">
    <div class="db-panel-header">
        <div class="db-panel-header-left">
            <div class="db-panel-icon violet">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            </div>
            <div>
                <h3 class="db-panel-title" data-i18n="admin_all_tickets">All Tickets</h3>
                <p class="db-panel-subtitle"><?= count($tickets) ?> ticket(s) match the current filters</p>
            </div>
        </div>
        <span class="db-chart-badge"><?= count($tickets) ?></span>
    </div>
    <div class="db-panel-body pad-none">
        <div class="db-table-wrap">
            <table class="db-table">
                <thead>
                    <tr>
                        <th data-i18n="common_tracking_code">Tracking Code</th>
                        <th data-i18n="common_employee">Employee</th>
                        <th data-i18n="common_department">Department</th>
                        <th data-i18n="common_issue">Issue</th>
                        <th data-i18n="common_status">Status</th>
                        <th>Evidence</th>
                        <th data-i18n="admin_assigned_ict">Assigned ICT</th>
                        <th data-i18n="admin_reassign">Reassign</th>
                        <th data-i18n="staff_save_update">Resolve</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="db-empty">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 0 0 6v3a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-3a3 3 0 0 0 0-6V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"></path><path d="M13 5v2"></path><path d="M13 17v2"></path><path d="M13 11v2"></path></svg>
                                    <h4 data-i18n="admin_no_tickets">No tickets yet</h4>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($tickets as $t): ?>
                        <?php $statusClass = strtolower(str_replace(' ', '', $t['status'])); ?>
                        <tr>
                            <td><span class="db-mono"><?= e($t['tracking_code']) ?></span></td>
                            <td><?= e((string) $t['employee_name']) ?></td>
                            <td><?= e((string) $t['department_name']) ?></td>
                            <td><?= e((string) $t['category_name']) ?> - <?= e((string) $t['subcategory_name']) ?></td>
                            <td><span class="db-status-pill <?= e($statusClass) ?>"><?= e($t['status']) ?></span></td>
                            <td>
                                <?php if (!empty($t['evidence_path'])): ?>
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm evidence-view-btn"
                                        data-evidence-url="<?= e(absoluteUrl((string) $t['evidence_path'])) ?>"
                                        data-evidence-name="<?= e((string) $t['evidence_name']) ?>"
                                        data-ticket-code="<?= e((string) $t['tracking_code']) ?>"
                                        data-ticket-employee="<?= e((string) $t['employee_name']) ?>"
                                        data-ticket-issue="<?= e((string) $t['category_name']) ?> - <?= e((string) $t['subcategory_name']) ?>"
                                        data-ticket-status="<?= e($t['status']) ?>"
                                    >View Photo</button>
                                <?php else: ?>
                                    <span class="evidence-empty">No photo</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) $t['ict_name']) ?></td>
                            <td>
                                <button type="button" class="btn btn-primary btn-sm assign-open-btn"
                                    data-ticket-id="<?= (int) $t['id'] ?>"
                                    data-ticket-code="<?= e($t['tracking_code']) ?>"
                                    data-ticket-employee="<?= e((string) $t['employee_name']) ?>"
                                    data-ticket-issue="<?= e((string) $t['category_name']) ?> - <?= e((string) $t['subcategory_name']) ?>"
                                    data-current-ict="<?= e((string) $t['ict_name']) ?>"
                                    data-current-ict-id="<?= (int) $t['assigned_to'] ?>"
                                    data-has-assignment="<?= !empty($t['assigned_to']) ? '1' : '0' ?>"
                                ><?= !empty($t['assigned_to']) ? 'Reassign' : 'Assign' ?></button>
                            </td>
                            <td>
                                <a href="<?= e(app_base_path() . '/admin/resolve_ticket?id=' . (int) $t['id']) ?>" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;">Resolve</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
</section>
</div>
<div id="assignModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="assignModalTitle">
    <div class="confirm-modal db-modal-card" role="document">
        <div class="modal-head">
            <h3 id="assignModalTitle" data-i18n="admin_reassign">Reassign</h3>
            <button type="button" class="modal-close" data-close-assign-modal aria-label="Close">&times;</button>
        </div>
        <p class="modal-desc">Select an ICT staff member to assign this ticket.</p>
        <div class="assign-summary">
            <div class="assign-summary-item">
                <span data-i18n="common_tracking_code">Tracking Code</span>
                <strong id="assignTicketCode">-</strong>
            </div>
            <div class="assign-summary-item">
                <span data-i18n="common_employee">Employee</span>
                <strong id="assignTicketEmployee">-</strong>
            </div>
            <div class="assign-summary-item">
                <span data-i18n="common_issue">Issue</span>
                <strong id="assignTicketIssue">-</strong>
            </div>
            <div class="assign-summary-item">
                <span data-i18n="common_current">Current</span>
                <strong id="assignTicketCurrent">None</strong>
            </div>
        </div>
        <form method="POST" id="assignForm">
            <?= csrf_field() ?>
            <input type="hidden" name="ticket_id" id="assignTicketId">
            <label class="modal-label">
                <span data-i18n="admin_select_ict">ICT Staff</span>
                <select name="ict_id" id="assignIctSelect" required>
                    <option value="" data-i18n="admin_select_ict">Select ICT</option>
                    <?php foreach ($ictUsers as $ict): ?>
                        <option value="<?= (int) $ict['id'] ?>"><?= e($ict['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="confirm-actions">
                <button type="button" class="btn btn-secondary" data-close-assign-modal data-i18n="common_cancel">Cancel</button>
                <button type="submit" class="btn btn-primary" data-i18n="common_assign">Assign</button>
            </div>
        </form>
    </div>
</div>
<div id="evidenceDrawer" class="evidence-drawer hidden" aria-hidden="true">
    <div class="evidence-drawer-backdrop" data-evidence-close></div>
    <aside class="evidence-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="evidenceDrawerTitle">
        <div class="evidence-drawer-header">
            <div>
                <div class="evidence-drawer-kicker">Ticket Evidence</div>
                <h3 id="evidenceDrawerTitle">Preview Photo</h3>
            </div>
            <button type="button" class="evidence-drawer-close" data-evidence-close aria-label="Close preview">&times;</button>
        </div>
        <div class="evidence-drawer-meta">
            <p><strong>Tracking Code:</strong> <span id="evidenceTicketCode">-</span></p>
            <p><strong>Employee:</strong> <span id="evidenceTicketEmployee">-</span></p>
            <p><strong>Issue:</strong> <span id="evidenceTicketIssue">-</span></p>
            <p><strong>Status:</strong> <span id="evidenceTicketStatus">-</span></p>
        </div>
        <div class="evidence-drawer-preview">
            <img id="evidenceTicketImage" alt="Ticket evidence preview" src="">
        </div>
        <p class="evidence-drawer-name" id="evidenceTicketName"></p>
    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const drawer = document.getElementById('evidenceDrawer');
    const image = document.getElementById('evidenceTicketImage');
    const nameBox = document.getElementById('evidenceTicketName');
    const codeBox = document.getElementById('evidenceTicketCode');
    const employeeBox = document.getElementById('evidenceTicketEmployee');
    const issueBox = document.getElementById('evidenceTicketIssue');
    const statusBox = document.getElementById('evidenceTicketStatus');

    function closeDrawer() {
        if (!drawer) return;
        drawer.classList.add('hidden');
        drawer.setAttribute('aria-hidden', 'true');
        image.src = '';
    }

    document.querySelectorAll('.evidence-view-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            image.src = button.dataset.evidenceUrl || '';
            nameBox.textContent = button.dataset.evidenceName || '';
            codeBox.textContent = button.dataset.ticketCode || '-';
            employeeBox.textContent = button.dataset.ticketEmployee || '-';
            issueBox.textContent = button.dataset.ticketIssue || '-';
            statusBox.textContent = button.dataset.ticketStatus || '-';
            drawer.classList.remove('hidden');
            drawer.setAttribute('aria-hidden', 'false');
        });
    });

    drawer.querySelectorAll('[data-evidence-close]').forEach(function (el) {
        el.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const assignModal = document.getElementById('assignModal');
    if (!assignModal) return;
    const assignForm = document.getElementById('assignForm');
    const assignSelect = document.getElementById('assignIctSelect');

    function openAssignModal() {
        assignModal.classList.remove('hidden');
        assignModal.setAttribute('aria-hidden', 'false');
        if (assignSelect) assignSelect.focus();
    }
    function closeAssignModal() {
        assignModal.classList.add('hidden');
        assignModal.setAttribute('aria-hidden', 'true');
        if (assignForm) assignForm.reset();
    }

    document.querySelectorAll('.assign-open-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const idBox = document.getElementById('assignTicketId');
            const codeBox = document.getElementById('assignTicketCode');
            const empBox = document.getElementById('assignTicketEmployee');
            const issueBox = document.getElementById('assignTicketIssue');
            const currentBox = document.getElementById('assignTicketCurrent');
            if (idBox) idBox.value = btn.dataset.ticketId || '';
            if (codeBox) codeBox.textContent = btn.dataset.ticketCode || '-';
            if (empBox) empBox.textContent = btn.dataset.ticketEmployee || '-';
            if (issueBox) issueBox.textContent = btn.dataset.ticketIssue || '-';
            if (currentBox) currentBox.textContent = btn.dataset.hasAssignment === '1' ? (btn.dataset.currentIct || '-') : 'None';
            if (assignSelect) {
                assignSelect.value = btn.dataset.hasAssignment === '1' ? (btn.dataset.currentIctId || '') : '';
            }
            openAssignModal();
        });
    });

    document.querySelectorAll('[data-close-assign-modal]').forEach(function (el) {
        el.addEventListener('click', closeAssignModal);
    });
    assignModal.addEventListener('click', function (e) {
        if (e.target === assignModal) closeAssignModal();
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !assignModal.classList.contains('hidden')) closeAssignModal();
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
