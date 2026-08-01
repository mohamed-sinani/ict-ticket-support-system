<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['employee']);

$conn = db();
$user = currentUser();
$userId = (int) $user['id'];

$sql = "SELECT t.tracking_code, t.status, t.created_at, t.updated_at, t.priority,
               d.name AS department_name, c.name AS category_name, sc.name AS subcategory_name,
               t.description
        FROM tickets t
        LEFT JOIN departments d ON d.id = t.department_id
        LEFT JOIN categories c ON c.id = t.category_id
        LEFT JOIN subcategories sc ON sc.id = t.subcategory_id
        WHERE t.employee_id = ?
        ORDER BY t.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Tickets | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<?php
$statusClassMap = [
    'Submitted'   => 'submitted',
    'Assigned'    => 'assigned',
    'In Progress' => 'inprogress',
    'Resolved'    => 'resolved',
    'Closed'      => 'closed',
];
$priorityClassMap = [
    'low'      => 'low',
    'medium'   => 'medium',
    'high'     => 'high',
    'critical' => 'critical',
];
?>
<div class="db-hero">
    <div class="db-hero-left">
        <div class="db-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
            <span data-i18n="subnav_my_tickets">My Tickets</span>
        </div>
        <h1 data-i18n="subnav_my_tickets">My Tickets</h1>
        <p class="db-sub-desc" data-i18n="my_tickets_employee_subtitle">Track the status and history of all issues you have reported.</p>
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            </div>
            <div>
                <h3 class="db-panel-title" data-i18n="common_all_tickets">All Issues</h3>
                <p class="db-panel-subtitle"><?= count($tickets) ?> issue(s) submitted by you</p>
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
                        <th data-i18n="common_issue">Issue</th>
                        <th data-i18n="common_status">Status</th>
                        <th data-i18n="common_priority">Priority</th>
                        <th data-i18n="common_created">Created</th>
                        <th data-i18n="common_updated">Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tickets): ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><span class="db-mono"><?= e($ticket['tracking_code']) ?></span></td>
                                <td>
                                    <div class="db-cell-main"><?= e((string) $ticket['category_name']) ?> &middot; <?= e((string) $ticket['subcategory_name']) ?></div>
                                    <div class="small-text"><?= e((string) $ticket['department_name']) ?></div>
                                </td>
                                <td><span class="db-status-pill <?= $statusClassMap[(string) $ticket['status']] ?? '' ?>"><?= e($ticket['status']) ?></span></td>
                                <td><span class="db-prio-pill <?= $priorityClassMap[strtolower((string) $ticket['priority'])] ?? '' ?>"><?= e($ticket['priority']) ?></span></td>
                                <td><?= e((string) $ticket['created_at']) ?></td>
                                <td><?= e((string) $ticket['updated_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="db-empty">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <h4 data-i18n="common_no_tickets">No tickets yet</h4>
                                    <p>No issues found yet.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
