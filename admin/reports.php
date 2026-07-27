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

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ict_reports_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Report', 'Label', 'Value']);

    $sql = "SELECT u.full_name, COUNT(t.id) AS handled
            FROM users u
            LEFT JOIN tickets t ON t.assigned_to = u.id
            WHERE u.role = 'ict'";
    if ($where !== '') {
        $sql .= str_replace('t.', '', $where);
    }
    $sql .= ' GROUP BY u.id ORDER BY handled DESC';
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        fputcsv($output, ['ICT Staff Performance', $row['full_name'], (int) $row['handled']]);
    }

    $sql = "SELECT DATE(created_at) AS day, COUNT(*) AS total FROM tickets WHERE 1=1{$where} GROUP BY DATE(created_at) ORDER BY day ASC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        fputcsv($output, ['Ticket Trends', $row['day'], (int) $row['total']]);
    }

    $sql = "SELECT c.name AS category, COUNT(t.id) AS total FROM categories c LEFT JOIN tickets t ON t.category_id = c.id WHERE 1=1{$where} GROUP BY c.id ORDER BY total DESC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        fputcsv($output, ['Common Issue Categories', $row['category'], (int) $row['total']]);
    }

    $sql = "SELECT d.name AS department, COUNT(t.id) AS total FROM departments d LEFT JOIN tickets t ON t.department_id = d.id WHERE 1=1{$where} GROUP BY d.id ORDER BY total DESC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        fputcsv($output, ['Department Breakdown', $row['department'], (int) $row['total']]);
    }

    fclose($output);
    exit;
}

function buildReport(string $baseSql, string $where, array $params, string $types, mysqli $conn): array
{
    $sql = $baseSql . $where;
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$staffPerformance = buildReport(
    "SELECT u.full_name, COUNT(t.id) AS handled
     FROM users u
     LEFT JOIN tickets t ON t.assigned_to = u.id
     WHERE u.role = 'ict'",
    $where, $params, $types, $conn
);

if (!empty($params)) {
    $trendWhere = $where;
} else {
    $trendWhere = $where;
}

$trendSql = "SELECT DATE(created_at) AS day, COUNT(*) AS total FROM tickets WHERE 1=1{$trendWhere} GROUP BY DATE(created_at) ORDER BY day ASC";
if (empty($params) && empty($startDate) && empty($endDate)) {
    $trendSql = "SELECT DATE(created_at) AS day, COUNT(*) AS total FROM tickets WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY DATE(created_at) ORDER BY day ASC";
}
$trendStmt = $conn->prepare($trendSql);
if (!empty($params)) {
    $trendStmt->bind_param($types, ...$params);
}
$trendStmt->execute();
$ticketTrends = $trendStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$commonIssues = buildReport(
    "SELECT c.name AS category, COUNT(t.id) AS total
     FROM categories c
     LEFT JOIN tickets t ON t.category_id = c.id WHERE 1=1",
    $where, $params, $types, $conn
);

$deptBreakdown = buildReport(
    "SELECT d.name AS department, COUNT(t.id) AS total
     FROM departments d
     LEFT JOIN tickets t ON t.department_id = d.id WHERE 1=1",
    $where, $params, $types, $conn
);

$userActivity = buildReport(
    "SELECT u.full_name, u.role, COUNT(t.id) AS ticket_count
     FROM users u
     LEFT JOIN tickets t ON t.employee_id = u.id WHERE 1=1",
    $where, $params, $types, $conn
);
usort($userActivity, function ($a, $b) {
    return (int) $b['ticket_count'] - (int) $a['ticket_count'];
});

$statusSql = "SELECT status, COUNT(*) c FROM tickets WHERE 1=1{$where} GROUP BY status";
$statusStmt = $conn->prepare($statusSql);
if (!empty($params)) {
    $statusStmt->bind_param($types, ...$params);
}
$statusStmt->execute();
$statusDist = $statusStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$statusLabels = array_column($statusDist, 'status');
$statusCounts = array_map('intval', array_column($statusDist, 'c'));

$trendLabels = [];
$trendData = [];
foreach ($ticketTrends as $row) {
    $trendLabels[] = date('M j', strtotime($row['day']));
    $trendData[] = (int) $row['total'];
}

$pageTitle = 'Reports & Analytics | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<h2 data-i18n="admin_reports_title">Reports & Analytics</h2>

<section class="panel-card" style="margin-bottom:1rem;">
    <form method="GET" class="reports-filter-form">
        <div class="reports-filter-fields">
            <label><span>From</span>
                <input type="date" name="start_date" value="<?= e($startDate) ?>">
            </label>
            <label><span>To</span>
                <input type="date" name="end_date" value="<?= e($endDate) ?>">
            </label>
            <button type="submit" class="btn btn-primary" data-i18n="common_filter">Filter</button>
            <?php if ($startDate !== '' || $endDate !== ''): ?>
                <a href="reports.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </div>
        <a href="reports.php?export=csv<?= $startDate !== '' ? '&start_date=' . e($startDate) : '' ?><?= $endDate !== '' ? '&end_date=' . e($endDate) : '' ?>" class="btn btn-secondary reports-export-btn">Export CSV</a>
    </form>
</section>

<div class="admin-report-grid">

    <section class="panel-card chart-card">
        <h3>Ticket Trends</h3>
        <div style="position:relative;height:220px;">
            <canvas id="trendChart" aria-label="Ticket trends chart" role="img"></canvas>
        </div>
    </section>

    <section class="panel-card chart-card">
        <h3>Tickets by Status</h3>
        <div style="position:relative;height:220px;">
            <canvas id="statusChart" aria-label="Status distribution" role="img"></canvas>
        </div>
    </section>

    <section class="panel-card">
        <h3 data-i18n="admin_ict_performance">ICT Staff Performance</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th data-i18n="common_name">Name</th>
                        <th data-i18n="common_tickets">Tickets</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($staffPerformance)): ?>
                        <tr><td colspan="2" class="small-text">No data</td></tr>
                    <?php else: ?>
                        <?php foreach ($staffPerformance as $row): ?>
                            <tr>
                                <td><?= e($row['full_name'] ?? 'Unassigned') ?></td>
                                <td><?= (int) $row['handled'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel-card">
        <h3>Ticket Trends<?= empty($startDate) && empty($endDate) ? ' (Last 14 Days)' : '' ?></h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th data-i18n="common_tickets">Tickets</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ticketTrends)): ?>
                        <tr><td colspan="2" class="small-text">No data in selected range</td></tr>
                    <?php else: ?>
                        <?php foreach ($ticketTrends as $row): ?>
                            <tr>
                                <td><?= e($row['day']) ?></td>
                                <td><?= (int) $row['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel-card">
        <h3 data-i18n="admin_common_categories">Common Issue Categories</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th data-i18n="report_category_label">Issue Category</th>
                        <th data-i18n="common_tickets">Tickets</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commonIssues)): ?>
                        <tr><td colspan="2" class="small-text">No data</td></tr>
                    <?php else: ?>
                        <?php foreach ($commonIssues as $row): ?>
                            <tr>
                                <td><?= e($row['category']) ?></td>
                                <td><?= (int) $row['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel-card">
        <h3>Department Breakdown</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th data-i18n="common_tickets">Tickets</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deptBreakdown)): ?>
                        <tr><td colspan="2" class="small-text">No data</td></tr>
                    <?php else: ?>
                        <?php foreach ($deptBreakdown as $row): ?>
                            <tr>
                                <td><?= e($row['department'] ?? 'Unassigned') ?></td>
                                <td><?= (int) $row['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel-card">
        <h3>User Activity Trends</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th data-i18n="common_full_name">Full Name</th>
                        <th>Role</th>
                        <th data-i18n="common_tickets">Tickets</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($userActivity)): ?>
                        <tr><td colspan="3" class="small-text">No data</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($userActivity, 0, 20) as $row): ?>
                            <tr>
                                <td><?= e($row['full_name'] ?? 'Unknown') ?></td>
                                <td><?= e($row['role'] ?? '-') ?></td>
                                <td><?= (int) $row['ticket_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const trendLabels = <?= json_encode($trendLabels) ?>;
    const trendData = <?= json_encode($trendData) ?>;
    const statusLabels = <?= json_encode($statusLabels) ?>;
    const statusCounts = <?= json_encode($statusCounts) ?>;

    if (document.getElementById('trendChart')) {
        new Chart(document.getElementById('trendChart'), {
            type: 'bar',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Tickets',
                    data: trendData,
                    backgroundColor: 'rgba(37,99,235,0.6)',
                    borderColor: '#2563eb',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    if (document.getElementById('statusChart')) {
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: ['#60a5fa', '#93c5fd', '#34d399', '#f87171', '#fbbf24'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
})();
</script>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
