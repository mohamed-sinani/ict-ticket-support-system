<?php
$currentEmployeePage = basename($_SERVER['PHP_SELF']);
$appRoot = app_base_path();
$employeeTitle = trim(explode('|', $pageTitle ?? 'Employee Dashboard')[0]);
$employeeUser = currentUser();

$avatarInitials = '';
if (!empty($employeeUser['full_name'])) {
    $parts = preg_split('/\s+/', trim($employeeUser['full_name']));
    $first = strtoupper($parts[0][0] ?? '');
    $second = isset($parts[1]) ? strtoupper($parts[1][0]) : strtoupper($parts[0][1] ?? '');
    $avatarInitials = trim(substr($first . $second, 0, 2));
} else {
    $avatarInitials = strtoupper(substr($employeeUser['email'] ?? 'E', 0, 2));
}

$employeeModules = [
    ['href' => $appRoot . '/employee/dashboard.php', 'label' => 'Dashboard', 'i18n' => 'subnav_dashboard', 'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect>'],
    ['href' => $appRoot . '/report.php', 'label' => 'Report an Issue', 'i18n' => 'nav_report_issue', 'icon' => '<path d="M12 2l3 7 7 3-7 3-3 7-3-7-7-3 7-3 3-7z"></path>'],
    ['href' => $appRoot . '/employee/my_tickets.php', 'label' => 'My Tickets', 'i18n' => 'subnav_my_tickets', 'icon' => '<path d="M2 9a3 3 0 0 0 0 6v3a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-3a3 3 0 0 0 0-6V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"></path><path d="M13 5v2"></path><path d="M13 17v2"></path><path d="M13 11v2"></path>'],
    ['href' => $appRoot . '/employee/settings.php', 'label' => 'Settings', 'i18n' => 'subnav_settings', 'icon' => '<path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7z"></path><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V22a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1H3a2 2 0 1 1 0-4h.05a1.7 1.7 0 0 0 1.55-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.7 1.7 0 0 0 1.87.34H9a1.7 1.7 0 0 0 1-1.55V3a2 2 0 1 1 4 0v.05a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87V9c0 .68.4 1.29 1.03 1.55H21a2 2 0 1 1 0 4h-.05A1.7 1.7 0 0 0 19.4 15z"></path>'],
];
?>
<div class="admin-shell">
    <aside class="admin-sidebar" aria-label="Employee navigation">
        <div class="admin-sidebar-head">
            <a href="<?= e($appRoot . '/employee/dashboard.php') ?>" class="admin-sidebar-brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                <span>ICT Support</span>
            </a>
        </div>
        <div class="admin-menu-panel" data-admin-menu-panel>
            <nav class="admin-module-nav">
                <?php foreach ($employeeModules as $module): ?>
                    <a href="<?= e($module['href']) ?>" class="<?= $currentEmployeePage === basename($module['href']) ? 'active' : '' ?>">
                        <svg class="admin-module-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $module['icon'] ?></svg>
                        <span data-i18n="<?= e($module['i18n']) ?>"><?= e($module['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <a href="<?= e($appRoot . '/logout.php') ?>" class="admin-logout" data-i18n="nav_logout">Logout</a>
        </div>
    </aside>
    <section class="admin-content">
        <div class="admin-topbar">
                <button type="button" class="admin-menu-toggle" aria-label="Open menu" data-admin-menu-toggle>
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <h1><?= e($employeeTitle) ?></h1>
                    <p><?= e($employeeUser['full_name'] ?? 'Employee') ?></p>
                </div>
            <div class="admin-topbar-actions">
                <div class="admin-profile-wrap" data-profile-menu>
                    <button type="button" class="admin-profile-icon" aria-label="Profile" aria-haspopup="true" aria-expanded="false" title="<?= e($employeeUser['full_name'] ?? 'Employee') ?>">
                        <svg class="admin-avatar-svg" viewBox="0 0 64 64" width="28" height="28" aria-hidden="true">
                            <defs>
                                <radialGradient id="avh3" cx="35%" cy="30%" r="80%">
                                    <stop offset="0%" stop-color="#e6f6fb"></stop>
                                    <stop offset="100%" stop-color="#94a3b8"></stop>
                                </radialGradient>
                                <linearGradient id="avb3" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#7bc9e8"></stop>
                                    <stop offset="100%" stop-color="#1d7aa3"></stop>
                                </linearGradient>
                            </defs>
                            <circle cx="32" cy="23" r="13" fill="url(#avh3)" stroke="rgba(15,23,42,0.15)" stroke-width="1.5"></circle>
                            <path d="M9 58c0-13.5 10.5-22 23-22s23 8.5 23 22v6H9z" fill="url(#avb3)"></path>
                        </svg>
                    </button>
                    <div class="admin-profile-menu hidden" role="menu" aria-label="Account menu">
                        <div class="admin-profile-menu-head">
                            <div class="admin-profile-menu-name"><?= e($employeeUser['full_name'] ?? 'Employee') ?></div>
                            <div class="admin-profile-menu-role"><?= e(ucfirst((string) ($employeeUser['role'] ?? ''))) ?></div>
                        </div>
                        <a href="<?= e($appRoot . '/employee/settings.php') ?>" class="admin-profile-menu-item" role="menuitem">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V22a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1H3a2 2 0 1 1 0-4h.05a1.7 1.7 0 0 0 1.55-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.7 1.7 0 0 0 1.87.34H9a1.7 1.7 0 0 0 1-1.55V3a2 2 0 1 1 4 0v.05a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 15a1.7 1.7 0 0 0 1.55 1H21a2 2 0 1 1 0 4h-.05A1.7 1.7 0 0 0 19.4 15z"></path></svg>
                            <span data-i18n="subnav_settings">Settings</span>
                        </a>
                        <a href="<?= e($appRoot . '/logout.php') ?>" class="admin-profile-menu-item danger" role="menuitem" data-i18n="nav_logout">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                            Logout
                        </a>
                    </div>
                </div>
                <button type="button" class="admin-icon-btn admin-lang-btn" aria-label="Switch language" data-language-toggle>
                    <svg class="admin-lang-globe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    <span class="admin-lang-code" data-lang-code>EN</span>
                </button>
            </div>
        </div>
    
