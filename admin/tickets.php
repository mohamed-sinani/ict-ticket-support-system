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
<h2 data-i18n="admin_ticket_oversight_title">Ticket Oversight</h2>
<section class="panel-card">
    <form method="GET" class="inline-form">
        <select name="status">
            <option value="" data-i18n="admin_all_statuses">All Statuses</option>
            <?php foreach (TICKET_STATUSES as $status): ?>
                <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e($status) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="department_id">
            <option value="" data-i18n="admin_all_departments">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= (int) $dept['id'] ?>" <?= $departmentFilter === (int) $dept['id'] ? 'selected' : '' ?>><?= e($dept['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-secondary" type="submit" data-i18n="common_filter">Filter</button>
    </form>
</section>

<section class="panel-card">
    <div class="table-wrap">
    <table class="admin-tickets-table">
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
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td><?= e($t['tracking_code']) ?></td>
                    <td><?= e((string) $t['employee_name']) ?></td>
                    <td><?= e((string) $t['department_name']) ?></td>
                    <td><?= e((string) $t['category_name']) ?> - <?= e((string) $t['subcategory_name']) ?></td>
                    <td><?= e($t['status']) ?></td>
                    <td>
                        <?php if (!empty($t['evidence_path'])): ?>
                            <button
                                type="button"
                                class="btn btn-secondary evidence-view-btn"
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
                        <form method="POST" class="ticket-assign-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="ticket_id" value="<?= (int) $t['id'] ?>">
                            <select name="ict_id" required>
                                <option value="" data-i18n="admin_select_ict">Select ICT</option>
                                <?php foreach ($ictUsers as $ict): ?>
                                    <option value="<?= (int) $ict['id'] ?>" <?= (int) $t['assigned_to'] === (int) $ict['id'] ? 'selected' : '' ?>><?= e($ict['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary" type="submit" data-i18n="common_assign"><?= !empty($t['assigned_to']) ? 'Reassign' : 'Assign' ?></button>
                        </form>
                    </td>
                    <td>
                        <a href="<?= e(app_base_path() . '/admin/resolve_ticket.php?id=' . (int) $t['id']) ?>" class="btn btn-secondary" style="width:100%;justify-content:center;">Resolve</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
</section>
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
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
