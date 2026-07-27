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

<div class="dash-greeting">
    <h2 data-i18n="staff_dashboard_title">ICT Staff Dashboard</h2>
    <p>Welcome back, <?= e($user['full_name']) ?>. Here's your workload at a glance.</p>
</div>

<div class="stats-grid dash-stats">
    <div class="stat stat-accent-blue">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="stat-body">
            <h3><?= $assigned ?></h3>
            <p data-i18n="staff_assigned_tickets">Assigned Tickets</p>
        </div>
    </div>
    <div class="stat stat-accent-amber">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div class="stat-body">
            <h3><?= $pending ?></h3>
            <p data-i18n="admin_in_progress">In Progress</p>
        </div>
    </div>
    <div class="stat stat-accent-violet">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="stat-body">
            <h3><?= $newTasks ?></h3>
            <p>New Assigned</p>
        </div>
    </div>
    <div class="stat stat-accent-green">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-body">
            <h3><?= $completed ?></h3>
            <p data-i18n="staff_completed_tickets">Completed</p>
        </div>
    </div>
</div>

<div class="dash-quick-actions">
    <a class="dash-action-card dash-action-primary" href="my_tickets.php">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
        <span class="dash-action-label">My Assigned Tickets</span>
        <span class="dash-action-count"><?= $assigned ?> total</span>
    </a>
    <a class="dash-action-card" href="employees.php">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span class="dash-action-label">Employees</span>
        <span class="dash-action-count">Manage</span>
    </a>
    <a class="dash-action-card" href="settings.php">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        <span class="dash-action-label">Settings</span>
        <span class="dash-action-count">Profile</span>
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
