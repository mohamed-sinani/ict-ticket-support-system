<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['admin']);

$conn = db();
$user = currentUser();
$total = (int) $conn->query('SELECT COUNT(*) c FROM tickets')->fetch_assoc()['c'];
$submitted = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE status = 'Submitted'")->fetch_assoc()['c'];
$inProgress = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE status IN ('Assigned','In Progress')")->fetch_assoc()['c'];
$resolved = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE status IN ('Resolved','Closed')")->fetch_assoc()['c'];
$unassigned = (int) $conn->query('SELECT COUNT(*) c FROM tickets WHERE assigned_to IS NULL')->fetch_assoc()['c'];
$criticalOpen = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE priority = 'Critical' AND status IN ('Submitted','Assigned','In Progress')")->fetch_assoc()['c'];

$avgResSql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) avg_hours FROM tickets WHERE resolved_at IS NOT NULL";
$avgRes = $conn->query($avgResSql)->fetch_assoc()['avg_hours'];

$openPct = $total > 0 ? round(($submitted + $inProgress) / $total * 100) : 0;
$resolvedPct = $total > 0 ? round($resolved / $total * 100) : 0;

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

$trendSql = "SELECT DATE(created_at) d, COUNT(*) c FROM tickets WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY d ORDER BY d ASC";
$trendRes = $conn->query($trendSql);
$trendMap = [];
while ($row = $trendRes->fetch_assoc()) {
    $trendMap[$row['d']] = (int) $row['c'];
}

$dailyMap = function (string $where) use ($conn): array {
    $sql = "SELECT DATE(created_at) d, COUNT(*) c FROM tickets WHERE {$where} AND created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY d";
    $map = [];
    foreach ($conn->query($sql) as $row) {
        $map[$row['d']] = (int) $row['c'];
    }
    return $map;
};

$submittedByDay = $dailyMap("status = 'Submitted'");
$inProgressByDay = $dailyMap("status IN ('Assigned','In Progress')");
$resolvedByDay = $dailyMap("status IN ('Resolved','Closed')");

$labels = [];
$dataPoints = [];
$sparkTotal = [];
$sparkSubmitted = [];
$sparkInProgress = [];
$sparkResolved = [];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('M j', strtotime($day));
    $dataPoints[] = $trendMap[$day] ?? 0;
    $sparkTotal[] = $trendMap[$day] ?? 0;
    $sparkSubmitted[] = $submittedByDay[$day] ?? 0;
    $sparkInProgress[] = $inProgressByDay[$day] ?? 0;
    $sparkResolved[] = $resolvedByDay[$day] ?? 0;
}

$statusSql = "SELECT status, COUNT(*) c FROM tickets GROUP BY status";
$statusMap = [];
foreach ($conn->query($statusSql) as $row) {
    $statusMap[$row['status']] = (int) $row['c'];
}
$statusLabels = array_keys($statusMap);
$statusCounts = array_values($statusMap);

$catSql = "SELECT c.name, COUNT(*) c FROM tickets t JOIN categories c ON t.category_id = c.id GROUP BY c.name ORDER BY c DESC LIMIT 6";
$catRes = $conn->query($catSql);
$catLabels = [];
$catCounts = [];
while ($row = $catRes->fetch_assoc()) {
    $catLabels[] = $row['name'];
    $catCounts[] = (int) $row['c'];
}

$recentSql = "SELECT t.tracking_code, t.priority, t.status, t.description, t.created_at,
                     u.full_name, u.employee_number, d.name dept, c.name cat
              FROM tickets t
              JOIN users u ON t.employee_id = u.id
              JOIN departments d ON t.department_id = d.id
              JOIN categories c ON t.category_id = c.id
              ORDER BY t.created_at DESC LIMIT 5";
$recentTickets = $conn->query($recentSql)->fetch_all(MYSQLI_ASSOC);

$busiestDay = null;
$busiestCount = 0;
foreach ($trendMap as $day => $count) {
    if ($count > $busiestCount) {
        $busiestCount = $count;
        $busiestDay = date('l', strtotime($day));
    }
}

$topCategory = $catLabels[0] ?? null;
$avgHr = $avgRes ? round((float) $avgRes, 1) : null;

$pageTitle = 'Admin Dashboard | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>

<div class="db-hero">
    <div class="db-hero-left">
        <div class="db-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v4"></path><path d="M12 18v4"></path><path d="m4.93 4.93 2.83 2.83"></path><path d="m16.24 16.24 2.83 2.83"></path><path d="M2 12h4"></path><path d="M18 12h4"></path><path d="m4.93 19.07 2.83-2.83"></path><path d="m16.24 7.76 2.83-2.83"></path></svg>
            <span data-i18n="<?= e($greetingI18n) ?>"><?= e($greeting) ?></span>
        </div>
        <h1 data-i18n="admin_dashboard_overview">Dashboard Overview</h1>
        <p class="db-sub-desc">Welcome back, <?= e($user['full_name'] ?? 'Admin') ?>. Here's how support is performing across the institution.</p>
    </div>
    <div class="db-hero-actions">
        <span class="db-date-pill">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <?= e(date('l, F j, Y')) ?>
        </span>
        <a class="db-view-btn primary" href="tickets" data-i18n="admin_view_all_tickets">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            View All Tickets
        </a>
    </div>
</div>

<div class="db-ai-tip">
    <div class="db-ai-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l1.6 4.6L18 8.2l-4.4 1.6L12 14.4l-1.6-4.6L6 8.2l4.4-1.6L12 2z"></path><path d="M19 14l.9 2.6 2.6.9-2.6.9L19 21l-.9-2.6-2.6-.9 2.6-.9L19 14z"></path></svg>
    </div>
    <div class="db-ai-content">
        <div class="db-ai-label-row">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.6 4.6L18 8.2l-4.4 1.6L12 14.4l-1.6-4.6L6 8.2l4.4-1.6L12 2z"></path></svg>
            <span data-i18n="admin_daily_insight">Daily Insight</span>
        </div>
        <p class="db-ai-text">
            <?php if ($unassigned > 0): ?>
                <strong><?= $unassigned ?></strong> submitted tickets are awaiting assignment.
            <?php elseif ($busiestDay && $busiestCount > 0): ?>
                Most activity arrived on <strong><?= e($busiestDay) ?></strong> with <strong><?= $busiestCount ?></strong> ticket(s).
            <?php elseif ($criticalOpen > 0): ?>
                <strong><?= $criticalOpen ?></strong> critical ticket(s) need attention right now.
            <?php else: ?>
                No open tickets right now — everything is up to date. Nice work!
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="db-stats-grid">
    <div class="db-stat-card c-blue">
        <div class="db-stat-top">
            <div class="db-stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="admin_tickets_status">Tickets by Status</h3>
                    <p class="db-panel-subtitle" data-i18n="admin_tickets_status_sub">Distribution across the full ticket lifecycle</p>
                </div>
            </div>
            <span class="db-chart-badge" data-i18n="admin_badge_alltime">All Time</span>
        </div>
        <div class="db-panel-body">
            <div class="db-status-body">
                <div class="db-donut-wrap">
                    <canvas id="statusDonut" aria-label="Tickets status distribution" role="img"></canvas>
                    <div class="db-donut-center">
                        <span class="db-donut-value"><?= $total ?></span>
                        <span class="db-donut-label" data-i18n="common_tickets">tickets</span>
                    </div>
                </div>
                <div class="db-legend" id="statusLegend"></div>
            </div>
        </div>
    </div>

    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon violet">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="3" x2="6" y2="15"></line><circle cx="18" cy="6" r="3"></circle><circle cx="6" cy="18" r="3"></circle><path d="M18 9a9 9 0 0 1-9 9"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="admin_common_categories">Top Issue Categories</h3>
                    <p class="db-panel-subtitle" data-i18n="admin_top_categories_sub">Where problems are concentrated</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body">
            <?php if ($catLabels): ?>
            <div class="db-trend-list">
                <?php
                $catMax = max($catCounts);
                foreach ($catLabels as $idx => $catName):
                    $count = $catCounts[$idx];
                    $pct = $catMax > 0 ? round($count / $catMax * 100) : 0;
                ?>
                <div class="db-trend-item">
                    <div class="db-trend-info">
                        <span class="db-trend-name"><?= e($catName) ?></span>
                        <span class="db-trend-meta">
                            <span class="db-trend-count"><?= $count ?></span>
                            <span><?= $pct ?>%</span>
                        </span>
                    </div>
                    <div class="db-trend-bar">
                        <div class="db-trend-fill <?= $idx % 2 === 1 ? 'green' : '' ?>" style="width:<?= max($pct, 3) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="db-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10"></path><path d="M12 20V4"></path><path d="M6 20v-6"></path></svg>
                <h4 data-i18n="admin_no_categories">No categories yet</h4>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="db-trend-row">
    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon cyan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m7 14 4-4 3 3 5-6"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="admin_ticket_trends_title">Ticket Trends</h3>
                    <p class="db-panel-subtitle" data-i18n="admin_ticket_trends_sub">New tickets over the last 14 days</p>
                </div>
            </div>
            <span class="db-chart-badge" data-i18n="admin_badge_14d">14 Days</span>
        </div>
        <div class="db-panel-body">
            <div class="db-chart-wrap">
                <canvas id="ticketTrends" aria-label="Ticket trends chart" role="img"></canvas>
            </div>
        </div>
    </div>

    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="admin_performance_title">Performance Snapshot</h3>
                    <p class="db-panel-subtitle" data-i18n="admin_performance_sub">Speed and completion overview</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body">
            <div class="db-kpi-grid">
                <div class="db-kpi-card">
                    <div class="db-kpi-header">
                        <span class="db-kpi-icon clock">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </span>
                        <span class="db-kpi-label" data-i18n="admin_avg_resolution_title">Avg Resolution Time</span>
                    </div>
                    <div class="db-kpi-value"><?= $avgHr !== null ? $avgHr . 'h' : '--' ?></div>
                    <div class="db-kpi-bar"><div class="db-kpi-fill" style="width:<?= $resolvedPct ?>%"></div></div>
                    <span class="db-kpi-sub"><?= $resolvedPct ?>% <?= $resolved ?> resolved</span>
                </div>
                <div class="db-kpi-card">
                    <div class="db-kpi-header">
                        <span class="db-kpi-icon trend">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                        </span>
                        <span class="db-kpi-label" data-i18n="admin_open_rate">Open Rate</span>
                    </div>
                    <div class="db-kpi-value"><?= $openPct ?>%</div>
                    <div class="db-kpi-bar"><div class="db-kpi-fill amber" style="width:<?= $openPct ?>%"></div></div>
                    <span class="db-kpi-sub"><?= $submitted + $inProgress ?> open tickets</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="db-activity-row">
    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="admin_recent_tickets">Recent Tickets</h3>
                    <p class="db-panel-subtitle" data-i18n="admin_recent_tickets_sub">Latest submissions across all departments</p>
                </div>
            </div>
            <a class="db-view-btn" href="tickets" data-i18n="admin_view_all">
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
                <div class="db-panel-icon slate">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l1.6 4.6L18 8.2l-4.4 1.6L12 14.4l-1.6-4.6L6 8.2l4.4-1.6L12 2z"></path><path d="M19 14l.9 2.6 2.6.9-2.6.9L19 21l-.9-2.6-2.6-.9 2.6-.9L19 14z"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="admin_insights_title">Insights</h3>
                    <p class="db-panel-subtitle" data-i18n="admin_insights_sub">Quick signals worth your attention</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body">
            <div class="db-insights-list">
                <?php if ($unassigned > 0): ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
                    </span>
                    <span><strong><?= $unassigned ?></strong> tickets are still unassigned.</span>
                </div>
                <?php endif; ?>
                <?php if ($criticalOpen > 0): ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 22 22 22 12 2"></polygon><line x1="12" y1="8" x2="12" y2="16"></line><line x1="12" y1="20" x2="12.01" y2="20"></line></svg>
                    </span>
                    <span><strong><?= $criticalOpen ?></strong> critical ticket(s) currently open.</span>
                </div>
                <?php endif; ?>
                <?php if ($topCategory !== null): ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="3" x2="6" y2="15"></line><circle cx="18" cy="6" r="3"></circle><circle cx="6" cy="18" r="3"></circle><path d="M18 9a9 9 0 0 1-9 9"></path></svg>
                    </span>
                    <span><strong><?= e($topCategory) ?></strong> is the top reported category.</span>
                </div>
                <?php endif; ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </span>
                    <span><strong><?= $resolvedPct ?>%</strong> of all tickets have been resolved.</span>
                </div>
                <?php if ($avgHr !== null): ?>
                <div class="db-insight-item">
                    <span class="db-insight-icon slate">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </span>
                    <span>Tickets are resolved in about <strong><?= $avgHr ?></strong> hours on average.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const labels = <?= json_encode($labels) ?>;
    const points = <?= json_encode($dataPoints) ?>;
    const statusLabels = <?= json_encode($statusLabels) ?>;
    const statusCounts = <?= json_encode($statusCounts) ?>;
    const catLabels = <?= json_encode($catLabels) ?>;
    const catCounts = <?= json_encode($catCounts) ?>;

    const palette = {
        blue:    '#309CC5',
        blueBg:  'rgba(48,156,197,0.10)',
        amber:   '#f59e0b',
        green:   '#22c55e',
        violet:  '#8b5cf6',
        red:     '#ef4444',
        cyan:    '#06b6d4',
        slate:   '#94a3b8'
    };

    const statusColors = {
        'Submitted':  palette.blue,
        'Assigned':   palette.cyan,
        'In Progress': palette.violet,
        'Resolved':   palette.green,
        'Closed':     palette.slate
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

    const ctx = document.getElementById('ticketTrends');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Tickets',
                data: points,
                borderColor: palette.blue,
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx: c, chartArea} = chart;
                    if (!chartArea) return palette.blueBg;
                    const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(48,156,197,0.18)');
                    gradient.addColorStop(0.6, 'rgba(48,156,197,0.04)');
                    gradient.addColorStop(1, 'rgba(48,156,197,0)');
                    return gradient;
                },
                tension: 0.4,
                fill: true,
                borderWidth: 2.5,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: palette.blue,
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, font: { size: 11 }, color: '#94a3b8' },
                    grid: { color: 'rgba(226,232,240,0.5)', drawBorder: false }
                },
                x: {
                    ticks: { font: { size: 11 }, color: '#94a3b8', maxRotation: 0 },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { size: 12, weight: '600' },
                    bodyFont: { size: 12 },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(c) { return c.parsed.y + ' ticket' + (c.parsed.y !== 1 ? 's' : ''); }
                    }
                }
            }
        }
    });

    const ctx2 = document.getElementById('statusDonut');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: statusLabels.map(function(l) { return statusColors[l] || palette.slate; }),
                borderWidth: 3,
                borderColor: '#ffffff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { size: 12, weight: '600' },
                    bodyFont: { size: 12 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(c) { return c.label + ': ' + c.parsed + ' ticket' + (c.parsed !== 1 ? 's' : ''); }
                    }
                }
            }
        }
    });

    const legendWrap = document.getElementById('statusLegend');
    if (legendWrap && statusLabels.length) {
        const total = statusCounts.reduce(function (a, b) { return a + b; }, 0);
        statusLabels.forEach(function (name, i) {
            const count = statusCounts[i];
            const pct = total > 0 ? Math.round(count / total * 100) : 0;
            const row = document.createElement('div');
            row.className = 'db-legend-row';
            row.innerHTML =
                '<span class="db-legend-dot" style="background:' + (statusColors[name] || palette.slate) + '"></span>' +
                '<span class="db-legend-name">' + name + '</span>' +
                '<span class="db-legend-count">' + count + '</span>' +
                '<span class="db-legend-pct">' + pct + '%</span>';
            legendWrap.appendChild(row);
        });
    }

})();
</script>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
