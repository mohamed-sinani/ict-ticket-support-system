<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['admin']);

$conn = db();
$total = (int) $conn->query('SELECT COUNT(*) c FROM tickets')->fetch_assoc()['c'];
$submitted = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE status = 'Submitted'")->fetch_assoc()['c'];
$inProgress = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE status IN ('Assigned','In Progress')")->fetch_assoc()['c'];
$resolved = (int) $conn->query("SELECT COUNT(*) c FROM tickets WHERE status IN ('Resolved','Closed')")->fetch_assoc()['c'];

$avgResSql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) avg_hours FROM tickets WHERE resolved_at IS NOT NULL";
$avgRes = $conn->query($avgResSql)->fetch_assoc()['avg_hours'];

$openPct = $total > 0 ? round(($submitted + $inProgress) / $total * 100) : 0;
$resolvedPct = $total > 0 ? round($resolved / $total * 100) : 0;

$pageTitle = 'Admin Dashboard | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>

<div class="dash-greeting">
    <h2 data-i18n="admin_dashboard_title">Admin Dashboard</h2>
    <p>Welcome back. Here's what's happening with support tickets today.</p>
</div>

<div class="stats-grid dash-stats">
    <div class="stat stat-accent-blue">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <div class="stat-body">
            <h3><?= $total ?></h3>
            <p data-i18n="admin_total_tickets">Total Tickets</p>
        </div>
    </div>
    <div class="stat stat-accent-amber">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-body">
            <h3><?= $submitted ?></h3>
            <p data-i18n="admin_submitted">Submitted</p>
        </div>
    </div>
    <div class="stat stat-accent-violet">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div class="stat-body">
            <h3><?= $inProgress ?></h3>
            <p data-i18n="admin_in_progress">In Progress</p>
        </div>
    </div>
    <div class="stat stat-accent-green">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-body">
            <h3><?= $resolved ?></h3>
            <p data-i18n="admin_resolved_closed">Resolved / Closed</p>
        </div>
    </div>
</div>

<div class="dash-kpi-row">
    <div class="panel-card dash-kpi-card">
        <div class="kpi-header">
            <span class="kpi-icon kpi-icon-clock">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <span class="kpi-label" data-i18n="admin_avg_resolution_title">Avg Resolution Time</span>
        </div>
        <div class="kpi-value">
            <?php if ($avgRes): ?>
                <?= number_format((float) $avgRes, 1) ?>h
            <?php else: ?>
                --
            <?php endif; ?>
        </div>
        <div class="kpi-bar"><div class="kpi-bar-fill" style="width:<?= $resolvedPct ?>%"></div></div>
        <span class="kpi-sub"><?= $resolvedPct ?>% resolved</span>
    </div>
    <div class="panel-card dash-kpi-card">
        <div class="kpi-header">
            <span class="kpi-icon kpi-icon-trend">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            </span>
            <span class="kpi-label">Open Rate</span>
        </div>
        <div class="kpi-value"><?= $openPct ?>%</div>
        <div class="kpi-bar"><div class="kpi-bar-fill kpi-bar-amber" style="width:<?= $openPct ?>%"></div></div>
        <span class="kpi-sub"><?= $submitted + $inProgress ?> open tickets</span>
    </div>
</div>

<?php
$trendSql = "SELECT DATE(created_at) d, COUNT(*) c FROM tickets WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY d ORDER BY d ASC";
$trendRes = $conn->query($trendSql);
$trendMap = [];
while ($row = $trendRes->fetch_assoc()) {
    $trendMap[$row['d']] = (int) $row['c'];
}

$labels = [];
$dataPoints = [];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('M j', strtotime($day));
    $dataPoints[] = $trendMap[$day] ?? 0;
}

$statusSql = "SELECT status, COUNT(*) c FROM tickets GROUP BY status";
$statusRes = $conn->query($statusSql);
$statusMap = [];
while ($row = $statusRes->fetch_assoc()) {
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
?>

<div class="chart-grid">
    <div class="panel-card chart-card chart-card-wide">
        <h3 data-i18n="admin_ticket_trends">Ticket Trends (Last 14 Days)</h3>
        <canvas id="ticketTrends" aria-label="Ticket trends chart" role="img"></canvas>
    </div>
    <div class="panel-card chart-card chart-card-side">
        <h3 data-i18n="admin_ict_performance">Tickets by Status</h3>
        <canvas id="statusDonut" aria-label="Tickets status distribution" role="img"></canvas>
    </div>
</div>

<?php if ($catLabels): ?>
<div class="panel-card chart-card" style="margin-top:1rem;">
    <h3>Top Issue Categories</h3>
    <canvas id="categoryBar" aria-label="Top categories chart" role="img" style="height:200px!important;"></canvas>
</div>
<?php endif; ?>

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
        blue:    '#2563eb',
        blueBg:  'rgba(37,99,235,0.10)',
        amber:   '#f59e0b',
        amberBg: 'rgba(245,158,11,0.10)',
        green:   '#22c55e',
        greenBg: 'rgba(34,197,94,0.10)',
        violet:  '#8b5cf6',
        red:     '#ef4444',
        cyan:    '#06b6d4',
        slate:   '#94a3b8'
    };

    const statusColors = {
        'Submitted':  palette.amber,
        'Assigned':   palette.violet,
        'In Progress': palette.blue,
        'Resolved':   palette.green,
        'Closed':     palette.slate
    };

    /* ── Ticket Trends Line ────────────────────────────────── */
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

    /* ── Status Donut ──────────────────────────────────────── */
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
            cutout: '62%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16,
                        font: { size: 12, weight: '600' }
                    }
                },
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

    <?php if ($catLabels): ?>
    /* ── Category Bar ──────────────────────────────────────── */
    const ctx3 = document.getElementById('categoryBar');
    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: catLabels,
            datasets: [{
                label: 'Tickets',
                data: catCounts,
                backgroundColor: [
                    palette.blue, palette.amber, palette.green,
                    palette.violet, palette.red, palette.cyan
                ],
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 42
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0, font: { size: 11 }, color: '#94a3b8' },
                    grid: { color: 'rgba(226,232,240,0.4)', drawBorder: false }
                },
                y: {
                    ticks: { font: { size: 12, weight: '600' }, color: '#1e293b' },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 8,
                    titleFont: { size: 12, weight: '600' },
                    bodyFont: { size: 12 },
                    displayColors: false,
                    callbacks: {
                        label: function(c) { return c.parsed.x + ' ticket' + (c.parsed.x !== 1 ? 's' : ''); }
                    }
                }
            }
        }
    });
    <?php endif; ?>
})();
</script>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
