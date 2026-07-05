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

$pageTitle = 'ICT Staff Dashboard | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<div class="staff-dashboard-grid">
    <div class="stats-grid staff-stats-grid">
        <div class="stat"><h3><?= $assigned ?></h3><p data-i18n="staff_assigned_tickets">Assigned Tickets</p></div>
        <div class="stat"><h3><?= $pending ?></h3><p data-i18n="admin_in_progress">In Progress</p></div>
        <div class="stat"><h3><?= $newTasks ?></h3><p data-i18n="report_stepper_1">Assigned (New)</p></div>
        <div class="stat"><h3><?= $completed ?></h3><p data-i18n="staff_completed_tickets">Completed Tickets</p></div>
    </div>

    <section class="panel-card staff-workload-card">
        <h3 data-i18n="staff_workload_title">Quick Actions</h3>
        <p>Manage your assigned workload and update incident statuses.</p>
        <div class="module-cta-grid">
            <a class="btn btn-primary" href="my_tickets.php" data-i18n="staff_open_tickets">Assigned Tickets</a>
            <a class="btn btn-secondary" href="employees.php" data-i18n="subnav_users">Manage Employees</a>
            <a class="btn btn-secondary" href="settings.php" data-i18n="subnav_settings">Settings</a>
        </div>
    </section>
</div>

<section class="panel-card module-summary-card">
    <h3>Overview</h3>
    <div class="module-summary-grid">
        <?php
        // Include staff module cards to mirror the employee dashboard pattern
        require_once __DIR__ . '/modules/dashboard_card.php';
        require_once __DIR__ . '/modules/tickets_card.php';
        require_once __DIR__ . '/modules/employees_card.php';
        require_once __DIR__ . '/modules/settings_card.php';
        ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
