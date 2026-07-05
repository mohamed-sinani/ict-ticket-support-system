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

$pageTitle = 'Admin Dashboard | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<h2 data-i18n="admin_dashboard_title">Admin Dashboard</h2>
<div class="stats-grid">
    <div class="stat"><h3><?= $total ?></h3><p data-i18n="admin_total_tickets">Total Tickets</p></div>
    <div class="stat"><h3><?= $submitted ?></h3><p data-i18n="admin_submitted">Submitted</p></div>
    <div class="stat"><h3><?= $inProgress ?></h3><p data-i18n="admin_in_progress">In Progress</p></div>
    <div class="stat"><h3><?= $resolved ?></h3><p data-i18n="admin_resolved_closed">Resolved / Closed</p></div>
</div>
<div class="panel-card">
    <h3 data-i18n="admin_avg_resolution_title">Average Resolution Time</h3>
    <p>
        <?php if ($avgRes): ?>
            <span><?= number_format((float) $avgRes, 2) ?></span> <span data-i18n="common_hours">hours</span>
        <?php else: ?>
            <span data-i18n="admin_no_resolved">No resolved tickets yet</span>
        <?php endif; ?>
    </p>
</div>
<?php
// Prepare chart data: ticket trends (last 14 days) and status distribution
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
?>

<div class="panel-card chart-grid">
    <div class="chart-card">
        <h3 data-i18n="admin_ticket_trends">Ticket Trends (Last 14 Days)</h3>
        <canvas id="ticketTrends" aria-label="Ticket trends chart" role="img"></canvas>
    </div>
    <div class="chart-card">
        <h3 data-i18n="admin_ict_performance">Tickets by Status</h3>
        <canvas id="statusDonut" aria-label="Tickets status distribution" role="img"></canvas>
    </div>
</div>

<!-- Load Chart.js from CDN and render charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const labels = <?= json_encode($labels) ?>;
        const points = <?= json_encode($dataPoints) ?>;
        const statusLabels = <?= json_encode($statusLabels) ?>;
        const statusCounts = <?= json_encode($statusCounts) ?>;

        // Ticket Trends Line Chart
        const ctx = document.getElementById('ticketTrends').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Tickets',
                    data: points,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.08)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: '#2563eb'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision:0 } }
                },
                plugins: { legend: { display: false } }
            }
        });

        // Status Donut Chart
        const ctx2 = document.getElementById('statusDonut').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: ['#60a5fa', '#93c5fd', '#34d399', '#f87171', '#fbbf24', '#c7c7c7'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    })();
</script>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
