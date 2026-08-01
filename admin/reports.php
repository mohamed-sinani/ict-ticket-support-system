<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['admin']);

$conn = db();

$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$where = '';
$params = [];
$types = '';

if ($startDate !== '') {
    $where .= ' AND t.created_at >= ?';
    $params[] = $startDate . ' 00:00:00';
    $types .= 's';
}
if ($endDate !== '') {
    $where .= ' AND t.created_at <= ?';
    $params[] = $endDate . ' 23:59:59';
    $types .= 's';
}

function runReport(mysqli $conn, string $sql, array $params, string $types): array
{
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function countTickets(mysqli $conn, string $condition, array $params, string $types): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM tickets t WHERE {$condition}");
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['c'];
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ict_reports_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Report', 'Label', 'Value']);

    $rows = runReport(
        $conn,
        "SELECT u.full_name, COUNT(t.id) AS handled
         FROM users u
         LEFT JOIN tickets t ON t.assigned_to = u.id
         WHERE u.role = 'ict'" . $where . ' GROUP BY u.id ORDER BY handled DESC',
        $params,
        $types
    );
    foreach ($rows as $row) {
        fputcsv($output, ['ICT Staff Performance', $row['full_name'], (int) $row['handled']]);
    }

    $rows = runReport(
        $conn,
        "SELECT DATE(created_at) AS day, COUNT(*) AS total FROM tickets t WHERE 1=1{$where} GROUP BY DATE(created_at) ORDER BY day ASC",
        $params,
        $types
    );
    foreach ($rows as $row) {
        fputcsv($output, ['Ticket Trends', $row['day'], (int) $row['total']]);
    }

    $rows = runReport(
        $conn,
        "SELECT c.name AS category, COUNT(t.id) AS total FROM categories c LEFT JOIN tickets t ON t.category_id = c.id WHERE 1=1{$where} GROUP BY c.id ORDER BY total DESC",
        $params,
        $types
    );
    foreach ($rows as $row) {
        fputcsv($output, ['Common Issue Categories', $row['category'], (int) $row['total']]);
    }

    $rows = runReport(
        $conn,
        "SELECT d.name AS department, COUNT(t.id) AS total FROM departments d LEFT JOIN tickets t ON t.department_id = d.id WHERE 1=1{$where} GROUP BY d.id ORDER BY total DESC",
        $params,
        $types
    );
    foreach ($rows as $row) {
        fputcsv($output, ['Department Breakdown', $row['department'], (int) $row['total']]);
    }

    fclose($output);
    exit;
}

$totalInRange = countTickets($conn, '1=1' . $where, $params, $types);
$openInRange = countTickets($conn, "1=1{$where} AND t.status IN ('Submitted','Assigned','In Progress')", $params, $types);
$resolvedInRange = countTickets($conn, "1=1{$where} AND t.status IN ('Resolved','Closed')", $params, $types);
$openPct = $totalInRange > 0 ? round($openInRange / $totalInRange * 100) : 0;
$resolvedPct = $totalInRange > 0 ? round($resolvedInRange / $totalInRange * 100) : 0;

$avgRows = runReport(
    $conn,
    "SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at)) a FROM tickets t WHERE t.resolved_at IS NOT NULL" . $where,
    $params,
    $types
);
$avgRes = $avgRows[0]['a'] ?? null;
$avgHr = $avgRes ? round((float) $avgRes, 1) : null;

$staffPerformance = runReport(
    $conn,
    "SELECT u.full_name, COUNT(t.id) AS handled
     FROM users u
     LEFT JOIN tickets t ON t.assigned_to = u.id
     WHERE u.role = 'ict'" . $where . ' GROUP BY u.id ORDER BY handled DESC',
    $params,
    $types
);

if (empty($startDate) && empty($endDate)) {
    $trendSql = "SELECT DATE(created_at) AS day, COUNT(*) AS total FROM tickets t WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY DATE(created_at) ORDER BY day ASC";
} else {
    $trendSql = "SELECT DATE(created_at) AS day, COUNT(*) AS total FROM tickets t WHERE 1=1{$where} GROUP BY DATE(created_at) ORDER BY day ASC";
}
$ticketTrends = runReport($conn, $trendSql, $params, $types);

$commonIssues = runReport(
    $conn,
    "SELECT c.name AS category, COUNT(t.id) AS total
     FROM categories c
     LEFT JOIN tickets t ON t.category_id = c.id WHERE 1=1{$where}
     GROUP BY c.id ORDER BY total DESC",
    $params,
    $types
);

$deptBreakdown = runReport(
    $conn,
    "SELECT d.name AS department, COUNT(t.id) AS total
     FROM departments d
     LEFT JOIN tickets t ON t.department_id = d.id WHERE 1=1{$where}
     GROUP BY d.id ORDER BY total DESC",
    $params,
    $types
);

$userActivity = runReport(
    $conn,
    "SELECT u.full_name, u.role, COUNT(t.id) AS ticket_count
     FROM users u
     LEFT JOIN tickets t ON t.employee_id = u.id WHERE 1=1{$where}
     GROUP BY u.id",
    $params,
    $types
);
usort($userActivity, function ($a, $b) {
    return (int) $b['ticket_count'] - (int) $a['ticket_count'];
});

$statusDist = runReport(
    $conn,
    "SELECT status, COUNT(*) c FROM tickets t WHERE 1=1{$where} GROUP BY status",
    $params,
    $types
);
$statusLabels = array_column($statusDist, 'status');
$statusCounts = array_map('intval', array_column($statusDist, 'c'));

$trendLabels = [];
$trendData = [];
foreach ($ticketTrends as $row) {
    $trendLabels[] = date('M j', strtotime($row['day']));
    $trendData[] = (int) $row['total'];
}

$hasRange = $startDate !== '' || $endDate !== '';
if ($startDate !== '' && $endDate !== '') {
    $rangeLabel = date('M j, Y', strtotime($startDate)) . ' – ' . date('M j, Y', strtotime($endDate));
} elseif ($startDate !== '') {
    $rangeLabel = 'From ' . date('M j, Y', strtotime($startDate));
} elseif ($endDate !== '') {
    $rangeLabel = 'Until ' . date('M j, Y', strtotime($endDate));
} else {
    $rangeLabel = 'All Time';
}

$exportUrl = 'reports.php?export=csv' . ($startDate !== '' ? '&start_date=' . rawurlencode($startDate) : '') . ($endDate !== '' ? '&end_date=' . rawurlencode($endDate) : '');

$pageTitle = 'Reports & Analytics | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>

<div class="db-hero">
    <div class="db-hero-left">
        <div class="db-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"></path><path d="m7 14 4-4 3 3 5-6"></path></svg>
            <span data-i18n="admin_reports_title">Reports &amp; Analytics</span>
        </div>
        <h1 data-i18n="reports_page_title">Reports &amp; Analytics</h1>
        <p class="db-sub-desc" data-i18n="reports_page_subtitle">Filter by date range to explore ticket performance across the institution.</p>
    </div>
    <div class="db-hero-actions">
        <span class="db-date-pill">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="3" x2="6" y2="15"></line><circle cx="18" cy="6" r="3"></circle><circle cx="6" cy="18" r="3"></circle><path d="M18 9a9 9 0 0 1-9 9"></path></svg>
            <?= e($rangeLabel) ?>
        </span>
        <a class="db-view-btn primary" href="<?= e($exportUrl) ?>" data-i18n="common_export">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export CSV
        </a>
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
        <div class="db-stat-value"><?= $totalInRange ?></div>
        <div class="db-stat-label" data-i18n="admin_total_tickets">Total Tickets</div>
    </div>
    <div class="db-stat-card c-amber">
        <div class="db-stat-top">
            <div class="db-stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <span class="db-period-badge open" data-i18n="admin_badge_open">Open</span>
        </div>
        <div class="db-stat-value"><?= $openInRange ?></div>
        <div class="db-stat-label" data-i18n="admin_badge_open">Open</div>
        <div class="db-stat-sub"><span class="db-kpi-bar" style="display:block;height:4px;margin:6px 0 0;"><span class="db-kpi-fill amber" style="display:block;width:<?= $openPct ?>%"></span></span></div>
    </div>
    <div class="db-stat-card c-green">
        <div class="db-stat-top">
            <div class="db-stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <span class="db-period-badge resolved" data-i18n="admin_badge_done">Done</span>
        </div>
        <div class="db-stat-value"><?= $resolvedInRange ?></div>
        <div class="db-stat-label" data-i18n="admin_resolved_closed">Resolved / Closed</div>
        <div class="db-stat-sub"><span class="db-kpi-bar" style="display:block;height:4px;margin:6px 0 0;"><span class="db-kpi-fill green" style="display:block;width:<?= $resolvedPct ?>%"></span></span></div>
    </div>
    <div class="db-stat-card c-violet">
        <div class="db-stat-top">
            <div class="db-stat-icon purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <span class="db-period-badge month" data-i18n="reports_badge_range">Range</span>
        </div>
        <div class="db-stat-value"><?= $avgHr !== null ? $avgHr . 'h' : '--' ?></div>
        <div class="db-stat-label" data-i18n="admin_avg_resolution_title">Avg Resolution Time</div>
    </div>
</div>

<section class="db-panel" style="margin-bottom:20px;">
    <div class="db-panel-header">
        <div class="db-panel-header-left">
            <div class="db-panel-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            </div>
            <div>
                <h3 class="db-panel-title" data-i18n="reports_filter_title">Filter Reports</h3>
                <p class="db-panel-subtitle" data-i18n="reports_filter_sub">Narrow results by submission date</p>
            </div>
        </div>
        <?php if ($hasRange): ?>
            <a class="db-view-btn" href="reports.php" data-i18n="common_clear">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                Clear
            </a>
        <?php endif; ?>
    </div>
    <div class="db-panel-body">
        <form method="GET" class="db-filter-bar">
            <label class="db-filter-field">
                <span data-i18n="common_from">From</span>
                <input type="date" name="start_date" value="<?= e($startDate) ?>">
            </label>
            <label class="db-filter-field">
                <span data-i18n="common_to">To</span>
                <input type="date" name="end_date" value="<?= e($endDate) ?>">
            </label>
            <button type="submit" class="db-view-btn primary" data-i18n="common_filter">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                Filter
            </button>
        </form>
    </div>
</section>

<div class="db-trend-row">
    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon cyan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m7 14 4-4 3 3 5-6"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="admin_ticket_trends_title">Ticket Trends</h3>
                    <p class="db-panel-subtitle" data-i18n="reports_trends_sub"><?= $hasRange ? 'Tickets in the selected period' : 'Tickets over the last 14 days' ?></p>
                </div>
            </div>
            <span class="db-chart-badge" data-i18n="<?= $hasRange ? 'reports_badge_range' : 'admin_badge_14d' ?>"><?= $hasRange ? 'Range' : '14 Days' ?></span>
        </div>
        <div class="db-panel-body">
            <?php if (!empty($trendData)): ?>
            <div class="db-chart-wrap">
                <canvas id="trendChart" aria-label="Ticket trends chart" role="img"></canvas>
            </div>
            <?php else: ?>
            <div class="db-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m7 14 4-4 3 3 5-6"></path></svg>
                <h4 data-i18n="reports_no_data_range">No data in selected range</h4>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon violet">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="admin_tickets_status">Tickets by Status</h3>
                    <p class="db-panel-subtitle" data-i18n="reports_status_sub">Distribution within the selected range</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body">
            <?php if (!empty($statusLabels)): ?>
            <div class="db-status-body">
                <div class="db-donut-wrap">
                    <canvas id="statusChart" aria-label="Tickets status distribution" role="img"></canvas>
                    <div class="db-donut-center">
                        <span class="db-donut-value"><?= $totalInRange ?></span>
                        <span class="db-donut-label" data-i18n="common_tickets">tickets</span>
                    </div>
                </div>
                <div class="db-legend" id="statusLegend"></div>
            </div>
            <?php else: ?>
            <div class="db-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                <h4 data-i18n="reports_no_data_range">No data in selected range</h4>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="db-activity-row">
    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="admin_ict_performance">ICT Staff Performance</h3>
                    <p class="db-panel-subtitle" data-i18n="reports_staff_sub">Tickets handled per ICT staff member</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body pad-none">
            <div class="db-table-wrap">
                <table class="db-table">
                    <thead>
                        <tr>
                            <th data-i18n="common_name">Name</th>
                            <th class="num" data-i18n="common_tickets">Tickets</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staffPerformance)): ?>
                            <tr><td colspan="2" class="small-text" data-i18n="reports_no_data">No data</td></tr>
                        <?php else: ?>
                            <?php foreach ($staffPerformance as $idx => $row): ?>
                            <tr>
                                <td><span class="db-rank-dot"><?= $idx + 1 ?></span><?= e($row['full_name'] ?? 'Unassigned') ?></td>
                                <td class="num"><?= (int) $row['handled'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="3" x2="6" y2="15"></line><circle cx="18" cy="6" r="3"></circle><circle cx="6" cy="18" r="3"></circle><path d="M18 9a9 9 0 0 1-9 9"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="admin_common_categories">Common Issue Categories</h3>
                    <p class="db-panel-subtitle" data-i18n="reports_categories_sub">Where problems are concentrated</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body pad-none">
            <div class="db-table-wrap">
                <table class="db-table">
                    <thead>
                        <tr>
                            <th data-i18n="report_category_label">Issue Category</th>
                            <th class="num" data-i18n="common_tickets">Tickets</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($commonIssues)): ?>
                            <tr><td colspan="2" class="small-text" data-i18n="reports_no_data">No data</td></tr>
                        <?php else: ?>
                            <?php foreach ($commonIssues as $row): ?>
                            <tr>
                                <td><?= e($row['category']) ?></td>
                                <td class="num"><?= (int) $row['total'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="db-activity-row">
    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l7-4 7 4v14"></path><path d="M9 21v-8h6v8"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="reports_department_breakdown">Department Breakdown</h3>
                    <p class="db-panel-subtitle" data-i18n="reports_dept_sub">Tickets reported per department</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body pad-none">
            <div class="db-table-wrap">
                <table class="db-table">
                    <thead>
                        <tr>
                            <th data-i18n="common_department">Department</th>
                            <th class="num" data-i18n="common_tickets">Tickets</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($deptBreakdown)): ?>
                            <tr><td colspan="2" class="small-text" data-i18n="reports_no_data">No data</td></tr>
                        <?php else: ?>
                            <?php foreach ($deptBreakdown as $row): ?>
                            <tr>
                                <td><?= e($row['department'] ?? 'Unassigned') ?></td>
                                <td class="num"><?= (int) $row['total'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="db-panel">
        <div class="db-panel-header">
            <div class="db-panel-header-left">
                <div class="db-panel-icon red">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div>
                    <h3 class="db-panel-title" data-i18n="reports_user_activity">User Activity Trends</h3>
                    <p class="db-panel-subtitle" data-i18n="reports_activity_sub">Most active reporters of tickets</p>
                </div>
            </div>
        </div>
        <div class="db-panel-body pad-none">
            <div class="db-table-wrap">
                <table class="db-table">
                    <thead>
                        <tr>
                            <th data-i18n="common_full_name">Full Name</th>
                            <th data-i18n="common_role">Role</th>
                            <th class="num" data-i18n="common_tickets">Tickets</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($userActivity)): ?>
                            <tr><td colspan="3" class="small-text" data-i18n="reports_no_data">No data</td></tr>
                        <?php else: ?>
                            <?php foreach (array_slice($userActivity, 0, 20) as $row): ?>
                            <tr>
                                <td><?= e($row['full_name'] ?? 'Unknown') ?></td>
                                <td><span class="db-role-pill <?= e($row['role'] ?? '') ?>"><?= e($row['role'] ?? '-') ?></span></td>
                                <td class="num"><?= (int) $row['ticket_count'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const trendLabels = <?= json_encode($trendLabels) ?>;
    const trendData = <?= json_encode($trendData) ?>;
    const statusLabels = <?= json_encode($statusLabels) ?>;
    const statusCounts = <?= json_encode($statusCounts) ?>;

    const palette = {
        blue:    '#2563eb',
        cyan:    '#06b6d4',
        violet:  '#8b5cf6',
        green:   '#22c55e',
        amber:   '#f59e0b',
        slate:   '#94a3b8'
    };

    const statusColors = {
        'Submitted':   palette.blue,
        'Assigned':    palette.cyan,
        'In Progress': palette.violet,
        'Resolved':    palette.green,
        'Closed':      palette.slate
    };

    const trendEl = document.getElementById('trendChart');
    if (trendEl) {
        new Chart(trendEl, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Tickets',
                    data: trendData,
                    borderColor: palette.blue,
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx: c, chartArea} = chart;
                        if (!chartArea) return 'rgba(37,99,235,0.10)';
                        const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        gradient.addColorStop(0, 'rgba(37,99,235,0.18)');
                        gradient.addColorStop(0.6, 'rgba(37,99,235,0.04)');
                        gradient.addColorStop(1, 'rgba(37,99,235,0)');
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
    }

    const statusEl = document.getElementById('statusChart');
    if (statusEl && statusLabels.length) {
        new Chart(statusEl, {
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
