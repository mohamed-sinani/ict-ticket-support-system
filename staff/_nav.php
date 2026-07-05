<?php
$currentStaffPage = basename($_SERVER['PHP_SELF']);
$appRoot = app_base_path();
$staffTitle = trim(explode('|', $pageTitle ?? 'ICT Dashboard')[0]);
$staffUser = currentUser();

$avatarInitials = '';
if (!empty($staffUser['full_name'])) {
    $parts = preg_split('/\s+/', trim($staffUser['full_name']));
    $first = strtoupper($parts[0][0] ?? '');
    $second = isset($parts[1]) ? strtoupper($parts[1][0]) : strtoupper($parts[0][1] ?? '');
    $avatarInitials = trim(substr($first . $second, 0, 2));
} else {
    $avatarInitials = strtoupper(substr($staffUser['email'] ?? 'I', 0, 2));
}

$staffModules = [
    ['href' => $appRoot . '/staff/dashboard.php', 'label' => 'Dashboard', 'i18n' => 'subnav_dashboard', 'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect>'],
    ['href' => $appRoot . '/staff/my_tickets.php', 'label' => 'Assigned Tickets', 'i18n' => 'staff_assigned_tickets', 'icon' => '<path d="M2 9a3 3 0 0 0 0 6v3a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-3a3 3 0 0 0 0-6V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"></path><path d="M13 5v2"></path><path d="M13 17v2"></path><path d="M13 11v2"></path>'],
    ['href' => $appRoot . '/staff/employees.php', 'label' => 'Employees', 'i18n' => 'subnav_users', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>'],
    ['href' => $appRoot . '/staff/settings.php', 'label' => 'Settings', 'i18n' => 'subnav_settings', 'icon' => '<path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7z"></path><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V22a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1H3a2 2 0 1 1 0-4h.05a1.7 1.7 0 0 0 1.55-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.7 1.7 0 0 0 1.87.34H9a1.7 1.7 0 0 0 1-1.55V3a2 2 0 1 1 4 0v.05a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87V9c0 .68.4 1.29 1.03 1.55H21a2 2 0 1 1 0 4h-.05A1.7 1.7 0 0 0 19.4 15z"></path>'],
];
?>
<div class="admin-shell">
    <aside class="admin-sidebar" aria-label="Staff navigation">
        <div class="admin-sidebar-head">
            <a href="<?= $appRoot . '/staff/dashboard.php' ?>" class="admin-sidebar-brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                <span>ICT Support</span>
            </a>
        </div>
        <div class="admin-menu-panel" data-admin-menu-panel>
            <nav class="admin-module-nav">
                <?php foreach ($staffModules as $module): ?>
                    <a href="<?= e($module['href']) ?>" class="<?= $currentStaffPage === basename($module['href']) ? 'active' : '' ?>">
                        <svg class="admin-module-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $module['icon'] ?></svg>
                        <span data-i18n="<?= e($module['i18n']) ?>"><?= e($module['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <a href="<?= $appRoot . '/logout.php' ?>" class="admin-logout" data-i18n="nav_logout">Logout</a>
        </div>
    </aside>
    <section class="admin-content">
        <div class="admin-topbar">
            <button type="button" class="admin-menu-toggle" aria-label="Open menu" data-admin-menu-toggle>
                <span></span><span></span><span></span>
            </button>
            <div>
                <h1><?= e($staffTitle) ?></h1>
                <p><?= e($staffUser['full_name'] ?? 'ICT Staff') ?></p>
            </div>
            <div class="admin-topbar-actions">
                <button type="button" class="admin-profile-icon" aria-label="Profile" title="<?= e($staffUser['full_name'] ?? 'ICT Staff') ?>">
                    <?= e($avatarInitials) ?>
                </button>
                <button type="button" class="btn btn-secondary btn-link" aria-label="Switch language" data-language-toggle>SW</button>
            </div>
        </div>
