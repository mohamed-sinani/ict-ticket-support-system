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

$pageTitle = 'Employee Dashboard | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<h2 data-i18n="employee_dashboard_title">Employee Dashboard</h2>
<div class="staff-dashboard-grid">
    <div class="stats-grid staff-stats-grid">
        <div class="stat"><h3><?= $total ?></h3><p>Total Tickets</p></div>
        <div class="stat"><h3><?= $submitted ?></h3><p>Submitted</p></div>
        <div class="stat"><h3><?= $inProgress ?></h3><p>In Progress</p></div>
        <div class="stat"><h3><?= $resolved ?></h3><p>Resolved</p></div>
    </div>

    <section class="panel-card staff-workload-card">
        <h3>Quick Actions</h3>
        <p>Report issues, track your tickets, and manage your profile.</p>
        <div class="module-cta-grid">
            <a class="btn btn-primary" href="<?= $baseUrl ?? '' ?>report.php">Report an Issue</a>
            <a class="btn btn-secondary" href="my_tickets.php">My Tickets</a>
        </div>
    </section>
</div>

<section class="panel-card module-summary-card">
    <h3>Overview</h3>
    <div class="module-summary-grid">
        <?php
        // Include employee module cards (keeps dashboard tidy and modular)
        require_once __DIR__ . '/modules/report_card.php';
        require_once __DIR__ . '/modules/my_tickets_card.php';
        require_once __DIR__ . '/modules/settings_card.php';
        ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
