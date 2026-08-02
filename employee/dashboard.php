<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['employee']);

$conn = db();
$user = currentUser();
$userId = (int) $user['id'];

$total = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE employee_id = {$userId}")->fetch_assoc()['c'];
$submitted = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE employee_id = {$userId} AND status = 'Submitted'")->fetch_assoc()['c'];
$inProgress = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE employee_id = {$userId} AND status IN ('Assigned','In Progress')")->fetch_assoc()['c'];
$resolved = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE employee_id = {$userId} AND status IN ('Resolved','Closed')")->fetch_assoc()['c'];

$openNow = $submitted + $inProgress;
$openRate = $total > 0 ? round($openNow / $total * 100) : 0;
$resolvedRate = $total > 0 ? round($resolved / $total * 100) : 0;

$avgResSql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) avg_hours FROM tickets WHERE employee_id = {$userId} AND resolved_at IS NOT NULL";
$avgRes = $conn->query($avgResSql)->fetch_assoc()['avg_hours'];
$avgHr = $avgRes ? round((float) $avgRes, 1) : null;

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
    $sql = "SELECT DATE(created_at) d, COUNT(*) c FROM tickets WHERE employee_id = {$userId} AND {$where} AND created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY d";
    $map = [];
    foreach ($conn->query($sql) as $row) {
        $map[$row['d']] = (int) $row['c'];
    }
    return $map;
};

$totalByDay = $dailyMap('1=1');
$submittedByDay = $dailyMap("status = 'Submitted'");
$inProgressByDay = $dailyMap("status IN ('Assigned','In Progress')");
$resolvedByDay = $dailyMap("status IN ('Resolved','Closed')");

$labels = [];
$sparkTotal = [];
$sparkSubmitted = [];
$sparkInProgress = [];
$sparkResolved = [];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('M j', strtotime($day));
    $sparkTotal[] = $totalByDay[$day] ?? 0;
    $sparkSubmitted[] = $submittedByDay[$day] ?? 0;
    $sparkInProgress[] = $inProgressByDay[$day] ?? 0;
    $sparkResolved[] = $resolvedByDay[$day] ?? 0;
}

$recentSql = "SELECT t.tracking_code, t.priority, t.status, t.description, t.created_at, d.name dept, c.name cat
              FROM tickets t
              JOIN departments d ON t.department_id = d.id
              JOIN categories c ON t.category_id = c.id
              WHERE t.employee_id = {$userId}
              ORDER BY t.created_at DESC LIMIT 5";
$recentTickets = $conn->query($recentSql)->fetch_all(MYSQLI_ASSOC);

$latestStatus = null;
if ($recentTickets) {
    $latestStatus = $recentTickets[0]['status'];
}

$pageTitle = 'Employee Dashboard | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>

<div class="db-hero">
    <div class="db-hero-left">
        <div class="db-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v4"></path><path d="M12 18v4"></path><path d="m4.93 4.93 2.83 2.83"></path><path d="m16.24 16.24 2.83 2.83"></path><path d="M2 12h4"></path><path d="M18 12h4"></path><path d="m4.93 19.07 2.83-2.83"></path><path d="m16.24 7.76 2.83-2.83"></path></svg>
            <span data-i18n="<?= e($greetingI18n) ?>"><?= e($greeting) ?></span>
        </div>
        <h2 data-i18n="employee_dashboard_title">Employee Dashboard</h2>
        <p class="db-sub-desc">Welcome back, <?= e($user['full_name']) ?>. Track and manage your support requests.</p>
    </div>
    <div class="db-hero-actions">
        <span class="db-date-pill">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <?= e(date('l, F j, Y')) ?>
        </span>
        <a class="db-view-btn primary" href="<?= $baseUrl ?>report" data-i18n="employee_report_issue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
            Report an Issue
        </a>
    </div>
</div>

<div class="db-ai-tip">
    <div class="db-ai-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
    </div>
    <div class="db-ai-content">
        <div class="db-ai-label-row">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.6 4.6L18 8.2l-4.4 1.6L12 14.4l-1.6-4.6L6 8.2l4.4-1.6L12 2z"></path></svg>
            <span data-i18n="admin_daily_insight">Daily Insight</span>
        </div>
        <p class="db-ai-text">
            <?php if ($latestStatus === 'Resolved' || $latestStatus === 'Closed'): ?>
                Your latest ticket was <strong>resolved</strong> — check the resolution note in My Tickets.
            <?php elseif ($latestStatus === 'In Progress'): ?>
                ICT is currently <strong>working on</strong> your latest ticket.
            <?php elseif ($latestStatus === 'Assigned'): ?>
                Your latest ticket has been <strong>assigned</strong> to an ICT staff member.
            <?php elseif ($latestStatus === 'Submitted'): ?>
                Your latest ticket is <strong>submitted</strong> and awaiting assignment.
            <?php elseif ($openNow > 0): ?>
                You have <strong><?= $openNow ?></strong> open ticket(s) being handled.
            <?php else: ?>
                You have no open tickets right now. Need help? Report a new issue.
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
        <div class="db-stat-value"><?= $total ?></div>
        <div class="db-stat-label" data-i18n="admin_total_tickets">Total Tickets</div>
        <div class="db-sparkline-wrap"><canvas id="sparkTotal" role="img" aria-label="Total tickets trend"></canvas></div>
    </div>
    <div class="db-stat-card c-amber">
        <div class="db-stat-top">
            <div class="db-stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <span class="db-period-badge open" data-i18n="admin_badge_open">Open</span>
        </div>
        <div class="db-stat-value"><?= $submitted ?></div>
        <div class="db-stat-label" data-i18n="admin_submitted">Submitted</div>
        <div class="db-sparkline-wrap"><canvas id="sparkSubmitted" role="img" aria-label="Submitted tickets trend"></canvas></div>
    </div>
    <div class="db-stat-card c-violet">
        <div class="db-stat-top">
            <div class="db-stat-icon purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            </div>
            <span class="db-period-badge month" data-i18n="admin_badge_active">Active</span>
        </div>
        <div class="db-stat-value"><?= $inProgress ?></div>
        <div class="db-stat-label" data-i18n="admin_in_progress">In Progress</div>
        <div class="db-sparkline-wrap"><canvas id="sparkInProgress" role="img" aria-label="In progress tickets trend"></canvas></div>
    </div>
    <div class="db-stat-card c-green">
        <div class="db-stat-top">
            <div class="db-stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <span class="db-period-badge resolved" data-i18n="admin_badge_done">Done</span>
        </div>
        <div class="db-stat-value"><?= $resolved ?></div>
        <div class="db-stat-label" data-i18n="admin_resolved_closed">Resolved / Closed</div>
        <div class="db-sparkline-wrap"><canvas id="sparkResolved" role="img" aria-label="Resolved tickets trend"></canvas></div>
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
                    <h3 class="db-panel-title" data-i18n="employee_activity_title">My Activity</h3>
                    <p class="db-panel-subtitle" data-i18n="employee_activity_sub">A snapshot of your request history</p>
                </div>
            </div>
            <span class="db-chart-badge" data-i18n="admin_badge_alltime">All Time</span>
        </div>
        <div class="db-panel-body">
            <div class="db-kpi-grid">
                <div class="db-kpi-card">
                    <div class="db-kpi-header">
                        <span class="db-kpi-icon trend">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                        </span>
                        <span class="db-kpi-label" data-i18n="admin_open_rate">Open Rate</span>
                    </div>
                    <div class="db-kpi-value"><?= $openRate ?>%</div>
                    <div class="db-kpi-bar"><div class="db-kpi-fill amber" style="width:<?= $openRate ?>%"></div></div>
                    <span class="db-kpi-sub"><?= $openNow ?> open now</span>
                </div>
                <div class="db-kpi-card">
                    <div class="db-kpi-header">
                        <span class="db-kpi-icon clock">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </span>
                        <span class="db-kpi-label" data-i18n="admin_avg_resolution_title">Avg Resolution Time</span>
                    </div>
                    <div class="db-kpi-value"><?= $avgHr !== null ? $avgHr . 'h' : '--' ?></div>
                    <div class="db-kpi-bar"><div class="db-kpi-fill green" style="width:<?= $resolvedRate ?>%"></div></div>
                    <span class="db-kpi-sub"><?= $resolved ?> resolved</span>
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
                    <p class="db-panel-subtitle" data-i18n="employee_insights_sub">What's happening with your requests</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body">
            <div class="db-insights-list">
                <?php if ($submitted > 0): ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </span>
                    <span><strong><?= $submitted ?></strong> ticket(s) still awaiting assignment.</span>
                </div>
                <?php endif; ?>
                <?php if ($inProgress > 0): ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    </span>
                    <span><strong><?= $inProgress ?></strong> ticket(s) are being worked on.</span>
                </div>
                <?php endif; ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </span>
                    <span><strong><?= $resolved ?></strong> of your ticket(s) have been resolved.</span>
                </div>
                <?php if ($avgHr !== null): ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon slate">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </span>
                    <span>Your tickets resolve in about <strong><?= $avgHr ?></strong> hours on average.</span>
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
                    <h3 class="db-panel-title" data-i18n="employee_recent_tickets">My Recent Tickets</h3>
                    <p class="db-panel-subtitle" data-i18n="employee_recent_tickets_sub">Your latest support requests</p>
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
                    <div class="db-t-avatar" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);"><?= e(strtoupper(substr($t['tracking_code'], 4, 1))) ?></div>
                    <div class="db-t-content">
                        <div class="db-t-top">
                            <span class="db-t-name"><?= e($t['cat']) ?></span>
                            <span class="db-t-code"><?= e($t['tracking_code']) ?></span>
                        </div>
                        <p class="db-t-desc"><?= e((string) $t['description']) ?></p>
                        <div class="db-t-tags">
                            <span class="db-tag db-tag-dept"><?= e($t['dept']) ?></span>
                            <span class="db-tag db-tag-prio <?= in_array($t['priority'], ['High', 'Critical'], true) ? 'high' : '' ?>"><?= e($t['priority']) ?></span>
                            <span class="db-tag db-tag-prio <?= in_array($t['status'], ['Resolved', 'Closed'], true) ? '' : 'high' ?>" style="background:<?= in_array($t['status'], ['Resolved', 'Closed'], true) ? 'rgba(34,197,94,0.1)' : 'rgba(48,156,197,0.08)' ?>;color:<?= in_array($t['status'], ['Resolved', 'Closed'], true) ? '#166534' : '#309CC5' ?>;"><?= e($t['status']) ?></span>
                        </div>
                    </div>
                    <span class="db-t-date"><?= e(date('M j, Y', strtotime($t['created_at']))) ?></span>
                </div>
                <?php endforeach; else: ?>
                <div class="db-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <h4 data-i18n="employee_no_tickets">No tickets yet</h4>
                    <p data-i18n="employee_no_tickets_hint">Report your first issue to get started.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon green">
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
                <a class="db-action-card db-action-primary" href="<?= $baseUrl ?>report">
                    <span class="db-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    </span>
                    <span class="db-action-label" data-i18n="employee_report_issue">Report an Issue</span>
                    <span class="db-action-sub">New ticket</span>
                </a>
                <a class="db-action-card" href="my_tickets">
                    <span class="db-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </span>
                    <span class="db-action-label" data-i18n="subnav_my_tickets">My Tickets</span>
                    <span class="db-action-sub"><?= $total ?> total</span>
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
        violet:  '#8b5cf6'
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

    drawSpark('sparkTotal', <?= json_encode($sparkTotal) ?>, palette.blue);
    drawSpark('sparkSubmitted', <?= json_encode($sparkSubmitted) ?>, palette.amber);
    drawSpark('sparkInProgress', <?= json_encode($sparkInProgress) ?>, palette.violet);
    drawSpark('sparkResolved', <?= json_encode($sparkResolved) ?>, palette.green);
})();
</script>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
