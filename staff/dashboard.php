<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['ict']);

$conn = db();
$user = currentUser();
$userId = (int) $user['id'];

$assigned = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE assigned_to = {$userId}")->fetch_assoc()['c'];
$pending = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE assigned_to = {$userId} AND status = 'In Progress'")->fetch_assoc()['c'];
$completed = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE assigned_to = {$userId} AND status IN ('Resolved','Closed')")->fetch_assoc()['c'];
$newTasks = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE assigned_to = {$userId} AND status = 'Assigned'")->fetch_assoc()['c'];

$assignedToday = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE assigned_to = {$userId} AND DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
$completionRate = $assigned > 0 ? round($completed / $assigned * 100) : 0;

$hour = (int) date('G');
if ($hour < 12) {
    $greeting = 'Good Morning';
    $greetingI18n = 'admin_good_morning';
} elseif ($hour < 17) {
    $greeting = 'Good Afternoon';
    $greetingI18n = 'admin_good_afternoon';
} else {
    $greeting = 'Good Evening';
    $greetingI18n = 'admin_good_evening';
}

$dailyMap = function (string $where) use ($conn, $userId): array {
    $sql = "SELECT DATE(created_at) d, COUNT(*) c FROM tickets WHERE assigned_to = {$userId} AND {$where} AND created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY d";
    $map = [];
    foreach ($conn->query($sql) as $row) {
        $map[$row['d']] = (int) $row['c'];
    }
    return $map;
};

$assignedByDay = $dailyMap('1=1');
$pendingByDay = $dailyMap("status = 'In Progress'");
$newTasksByDay = $dailyMap("status = 'Assigned'");
$completedByDay = $dailyMap("status IN ('Resolved','Closed')");

$labels = [];
$sparkAssigned = [];
$sparkPending = [];
$sparkNew = [];
$sparkCompleted = [];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('M j', strtotime($day));
    $sparkAssigned[] = $assignedByDay[$day] ?? 0;
    $sparkPending[] = $pendingByDay[$day] ?? 0;
    $sparkNew[] = $newTasksByDay[$day] ?? 0;
    $sparkCompleted[] = $completedByDay[$day] ?? 0;
}

$recentSql = "SELECT t.tracking_code, t.priority, t.status, t.description, t.created_at,
                     u.full_name, d.name dept, c.name cat
              FROM tickets t
              JOIN users u ON t.employee_id = u.id
              JOIN departments d ON t.department_id = d.id
              JOIN categories c ON t.category_id = c.id
              WHERE t.assigned_to = {$userId}
              ORDER BY t.created_at DESC LIMIT 5";
$recentTickets = $conn->query($recentSql)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'ICT Staff Dashboard | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>

<div class="db-hero">
    <div class="db-hero-left">
        <div class="db-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v4"></path><path d="M12 18v4"></path><path d="m4.93 4.93 2.83 2.83"></path><path d="m16.24 16.24 2.83 2.83"></path><path d="M2 12h4"></path><path d="M18 12h4"></path><path d="m4.93 19.07 2.83-2.83"></path><path d="m16.24 7.76 2.83-2.83"></path></svg>
            <span data-i18n="<?= e($greetingI18n) ?>"><?= e($greeting) ?></span>
        </div>
        <h2 data-i18n="staff_dashboard_title">ICT Staff Dashboard</h2>
        <p class="db-sub-desc">Welcome back, <?= e($user['full_name']) ?>. Here's your workload at a glance.</p>
    </div>
    <div class="db-hero-actions">
        <span class="db-date-pill">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <?= e(date('l, F j, Y')) ?>
        </span>
        <a class="db-view-btn primary" href="my_tickets" data-i18n="staff_my_tickets_link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            My Assigned Tickets
        </a>
    </div>
</div>

<div class="db-ai-tip">
    <div class="db-ai-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6"></path><path d="M9 16h6"></path><path d="M8 3h8a2 2 0 0 1 2 2v14l-4-4H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path></svg>
    </div>
    <div class="db-ai-content">
        <div class="db-ai-label-row">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.6 4.6L18 8.2l-4.4 1.6L12 14.4l-1.6-4.6L6 8.2l4.4-1.6L12 2z"></path></svg>
            <span data-i18n="staff_workload_title">Workload View</span>
        </div>
        <p class="db-ai-text">
            <?php if ($newTasks > 0): ?>
                You have <strong><?= $newTasks ?></strong> new task(s) waiting to be started.
            <?php elseif ($pending > 0): ?>
                You're currently working on <strong><?= $pending ?></strong> ticket(s) in progress.
            <?php elseif ($assigned > 0): ?>
                You've completed <strong><?= $completed ?></strong> of <strong><?= $assigned ?></strong> assigned ticket(s).
            <?php else: ?>
                No assigned tickets right now — enjoy the calm, or check back soon.
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="db-stats-grid">
    <div class="db-stat-card c-blue">
        <div class="db-stat-top">
            <div class="db-stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            </div>
            <span class="db-period-badge alltime" data-i18n="admin_badge_alltime">All Time</span>
        </div>
        <div class="db-stat-value"><?= $assigned ?></div>
        <div class="db-stat-label" data-i18n="staff_assigned_tickets">Assigned Tickets</div>
        <div class="db-sparkline-wrap"><canvas id="sparkAssigned" role="img" aria-label="Assigned tickets trend"></canvas></div>
    </div>
    <div class="db-stat-card c-cyan">
        <div class="db-stat-top">
            <div class="db-stat-icon cyan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
            </div>
            <span class="db-period-badge new" data-i18n="admin_badge_new">New</span>
        </div>
        <div class="db-stat-value"><?= $newTasks ?></div>
        <div class="db-stat-label" data-i18n="staff_new_assigned">New Assigned</div>
        <div class="db-sparkline-wrap"><canvas id="sparkNew" role="img" aria-label="New assignments trend"></canvas></div>
    </div>
    <div class="db-stat-card c-amber">
        <div class="db-stat-top">
            <div class="db-stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            </div>
            <span class="db-period-badge open" data-i18n="admin_badge_active">Active</span>
        </div>
        <div class="db-stat-value"><?= $pending ?></div>
        <div class="db-stat-label" data-i18n="admin_in_progress">In Progress</div>
        <div class="db-sparkline-wrap"><canvas id="sparkPending" role="img" aria-label="In progress trend"></canvas></div>
    </div>
    <div class="db-stat-card c-green">
        <div class="db-stat-top">
            <div class="db-stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <span class="db-period-badge resolved" data-i18n="admin_badge_done">Done</span>
        </div>
        <div class="db-stat-value"><?= $completed ?></div>
        <div class="db-stat-label" data-i18n="staff_completed_tickets">Completed</div>
        <div class="db-sparkline-wrap"><canvas id="sparkCompleted" role="img" aria-label="Completed tickets trend"></canvas></div>
    </div>
</div>

<div class="db-bottom-row">
    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="staff_performance_title">My Performance</h3>
                    <p class="db-panel-subtitle" data-i18n="staff_performance_sub">How you're tracking on assigned work</p>
                </div>
            </div>
            <span class="db-chart-badge" data-i18n="admin_badge_alltime">All Time</span>
        </div>
        <div class="db-panel-body">
            <div class="db-kpi-grid">
                <div class="db-kpi-card">
                    <div class="db-kpi-header">
                        <span class="db-kpi-icon green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </span>
                        <span class="db-kpi-label" data-i18n="staff_completion_rate">Completion Rate</span>
                    </div>
                    <div class="db-kpi-value"><?= $completionRate ?>%</div>
                    <div class="db-kpi-bar"><div class="db-kpi-fill green" style="width:<?= $completionRate ?>%"></div></div>
                    <span class="db-kpi-sub"><?= $completed ?> of <?= $assigned ?> resolved</span>
                </div>
                <div class="db-kpi-card">
                    <div class="db-kpi-header">
                        <span class="db-kpi-icon clock">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </span>
                        <span class="db-kpi-label" data-i18n="staff_open_now">Open Now</span>
                    </div>
                    <div class="db-kpi-value"><?= $pending + $newTasks ?></div>
                    <div class="db-kpi-bar"><div class="db-kpi-fill amber" style="width:<?= $assigned > 0 ? round(($pending + $newTasks) / $assigned * 100) : 0 ?>%"></div></div>
                    <span class="db-kpi-sub"><?= $assignedToday ?> assigned today</span>
                </div>
            </div>
        </div>
    </div>

    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon cyan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6"></path><path d="M9 16h6"></path><path d="M8 3h8a2 2 0 0 1 2 2v14l-4-4H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="admin_insights_title">Insights</h3>
                    <p class="db-panel-subtitle" data-i18n="staff_insights_sub">Signals from your ticket queue</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body">
            <div class="db-insights-list">
                <?php if ($newTasks > 0): ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    </span>
                    <span><strong><?= $newTasks ?></strong> task(s) are waiting to be started.</span>
                </div>
                <?php endif; ?>
                <?php if ($pending > 0): ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    </span>
                    <span><strong><?= $pending ?></strong> ticket(s) currently in progress.</span>
                </div>
                <?php endif; ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </span>
                    <span>You've resolved <strong><?= $completed ?></strong> ticket(s) overall.</span>
                </div>
                <?php if ($assignedToday > 0): ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon slate">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </span>
                    <span><strong><?= $assignedToday ?></strong> new ticket(s) landed today.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="db-activity-row">
    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon violet">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="staff_recent_tickets">Recent Assigned Tickets</h3>
                    <p class="db-panel-subtitle" data-i18n="staff_recent_tickets_sub">Latest tickets in your queue</p>
                </div>
            </div>
            <a class="db-view-btn" href="my_tickets" data-i18n="admin_view_all">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                View All
            </a>
        </div>
        <div class="db-panel-body pad-none">
            <div class="db-ticket-list">
                <?php if ($recentTickets): foreach ($recentTickets as $t): ?>
                <div class="db-ticket-item">
                    <div class="db-t-avatar"><?= e(strtoupper(substr($t['full_name'], 0, 1))) ?></div>
                    <div class="db-t-content">
                        <div class="db-t-top">
                            <span class="db-t-name"><?= e($t['full_name']) ?></span>
                            <span class="db-t-code"><?= e($t['tracking_code']) ?></span>
                        </div>
                        <p class="db-t-desc"><?= e((string) $t['description']) ?></p>
                        <div class="db-t-tags">
                            <span class="db-tag db-tag-dept"><?= e($t['dept']) ?></span>
                            <span class="db-tag db-tag-cat"><?= e($t['cat']) ?></span>
                            <span class="db-tag db-tag-prio <?= in_array($t['priority'], ['High', 'Critical'], true) ? 'high' : '' ?>"><?= e($t['priority']) ?></span>
                            <span class="db-tag db-tag-prio <?= $t['status'] === 'In Progress' ? 'high' : '' ?>" style="background:rgba(48,156,197,0.08);color:#309CC5;"><?= e($t['status']) ?></span>
                        </div>
                    </div>
                    <span class="db-t-date"><?= e(date('M j, Y', strtotime($t['created_at']))) ?></span>
                </div>
                <?php endforeach; else: ?>
                <div class="db-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <h4 data-i18n="admin_no_tickets">No tickets yet</h4>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="staff_quick_links">Quick Links</h3>
                    <p class="db-panel-subtitle" data-i18n="staff_quick_links_sub">Jump straight to what you need</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body">
            <div class="db-actions-grid" style="grid-template-columns:1fr;margin-bottom:0;">
                <a class="db-action-card db-action-primary" href="my_tickets">
                    <span class="db-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    </span>
                    <span class="db-action-label" data-i18n="staff_my_assigned_tickets">My Assigned Tickets</span>
                    <span class="db-action-sub"><?= $assigned ?> total</span>
                </a>
                <a class="db-action-card" href="employees">
                    <span class="db-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </span>
                    <span class="db-action-label" data-i18n="staff_employees">Employees</span>
                    <span class="db-action-sub">Browse and manage</span>
                </a>
                <a class="db-action-card" href="settings">
                    <span class="db-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    </span>
                    <span class="db-action-label" data-i18n="subnav_settings">Settings</span>
                    <span class="db-action-sub">Profile</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const labels = <?= json_encode($labels) ?>;

    const palette = {
        blue:    '#309CC5',
        amber:   '#f59e0b',
        green:   '#22c55e',
        violet:  '#8b5cf6',
        cyan:    '#06b6d4'
    };

    function drawSpark(canvasId, data, color) {
        const el = document.getElementById(canvasId);
        if (!el) return;
        new Chart(el, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    borderColor: color,
                    backgroundColor: 'rgba(0,0,0,0)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 0,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 900 },
                scales: { x: { display: false }, y: { display: false } },
                plugins: { legend: { display: false }, tooltip: { enabled: false } }
            }
        });
    }

    drawSpark('sparkAssigned', <?= json_encode($sparkAssigned) ?>, palette.blue);
    drawSpark('sparkNew', <?= json_encode($sparkNew) ?>, palette.cyan);
    drawSpark('sparkPending', <?= json_encode($sparkPending) ?>, palette.amber);
    drawSpark('sparkCompleted', <?= json_encode($sparkCompleted) ?>, palette.green);
})();
</script>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
