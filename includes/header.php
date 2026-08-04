<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = $pageTitle ?? APP_NAME;
$user = currentUser();
$flash = getFlash();

$isAdminDir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$isStaffDir = strpos($_SERVER['PHP_SELF'], '/staff/') !== false;
$isEmployeeDir = strpos($_SERVER['PHP_SELF'], '/employee/') !== false;

$isPanelDir = $isAdminDir || $isStaffDir || $isEmployeeDir;

if (isset($forcePanel) && $forcePanel) {
    $isPanelDir = true;
}

$isSubDir = $isAdminDir || $isStaffDir || $isEmployeeDir;
$baseUrl = (BASE_URL === '' ? '/' : rtrim(BASE_URL, '/') . '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" href="<?= $baseUrl ?>favicon.ico" type="image/x-icon">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/dashboard.css?v=<?= filemtime(__DIR__ . '/../assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/ai-support.css?v=<?= filemtime(__DIR__ . '/../assets/css/ai-support.css') ?>">
    <?php
    if (!empty($user['id'])) {
        $userCssRel = 'assets/css/users/user_' . (int) $user['id'] . '.css';
        $userCssHref = $baseUrl . $userCssRel;
        if (file_exists(__DIR__ . '/../' . $userCssRel)) {
            echo '<link rel="stylesheet" href="' . e($userCssHref) . '">';
        }
    }
    ?>
    <script>
        if (localStorage.getItem('app_theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</head>
<body class="<?= $isPanelDir ? 'admin-app-page' : '' ?>">

<header class="topbar">
    <div class="container nav-wrap">

        <a href="<?= $baseUrl ?>" class="brand">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
            <span class="brand-text">ICT Support</span>
        </a>

        <button class="menu-toggle" id="menuToggle" aria-label="Open menu" data-i18n-aria-label="menu_toggle" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav id="mainNav">
            <?php if ($user): ?>
                <span class="nav-user"><?= e($user['full_name']) ?> &mdash; <?= strtoupper(e($user['role'])) ?></span>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?= $baseUrl ?>admin/dashboard" data-i18n="nav_dashboard">Dashboard</a>
                    <a href="<?= $baseUrl ?>admin/settings" data-i18n="subnav_settings">Settings</a>
                <?php elseif ($user['role'] === 'ict'): ?>
                    <a href="<?= $baseUrl ?>staff/dashboard" data-i18n="nav_my_panel">My Panel</a>
                    <a href="<?= $baseUrl ?>staff/my_tickets" data-i18n="subnav_tickets_reported">Tickets Reported</a>
                    <a href="<?= $baseUrl ?>staff/settings" data-i18n="subnav_settings">Settings</a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>employee/dashboard" data-i18n="nav_dashboard">Dashboard</a>
                    <a href="<?= $baseUrl ?>report" data-i18n="nav_report_issue">Report Issue</a>
                    <a href="<?= $baseUrl ?>employee/my_tickets" data-i18n="subnav_my_tickets">My Tickets</a>
                    <a href="<?= $baseUrl ?>employee/settings" data-i18n="subnav_settings">Settings</a>
                <?php endif; ?>
                <a class="btn btn-secondary btn-link" href="<?= $baseUrl ?>logout" data-i18n="nav_logout">Logout</a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>report" target="_self" data-i18n="nav_report_issue">Report Issue</a>
                <a href="<?= $baseUrl ?>track" data-i18n="nav_check_status">Check Status</a>
                <a class="btn btn-primary btn-link" href="<?= $baseUrl ?>login" target="_self" data-i18n="nav_login">Login</a>
            <?php endif; ?>
            <button type="button" id="languageToggle" class="admin-icon-btn admin-lang-btn" aria-label="Switch language" data-language-toggle>
                <svg class="admin-lang-globe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                <span class="admin-lang-code" data-lang-code>EN</span>
            </button>
            <?php if ($user): ?>
            <button type="button" class="admin-icon-btn" id="themeToggle" aria-label="Toggle dark mode" data-theme-toggle>
                <svg data-theme-icon="moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                <svg data-theme-icon="sun" class="admin-icon-svg-hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
            </button>
            <?php endif; ?>
        </nav>

    </div>
</header>

<main class="container<?= $isPanelDir ? ' admin-main' : '' ?>">
<?php if ($flash): ?>
<div class="flash-toast flash-toast-<?= e($flash['type'] === 'error' ? 'danger' : $flash['type']) ?>" role="status" aria-live="polite" data-flash-toast>
    <div class="flash-toast-inner">
        <span class="flash-toast-title"><?= e($flash['type'] === 'error' ? 'Error' : 'Success') ?></span>
        <span class="flash-toast-message"><?= e((string) $flash['message']) ?></span>
    </div>
</div>
<?php endif; ?>
<script>
const appTranslations = {
    en: {
        menu_toggle: 'Open menu',
        nav_dashboard: 'Dashboard',
        nav_my_panel: 'My Panel',
        nav_logout: 'Logout',
        nav_report_issue: 'Report Issue',
        nav_check_status: 'Check Status',
        nav_register: 'Register',
        nav_login: 'Login',
        subnav_dashboard: 'Dashboard',
        subnav_departments: 'Departments',
        subnav_users: 'Users',
        subnav_tickets: 'Tickets',
        subnav_reports: 'Reports',
        subnav_settings: 'Settings',
        subnav_tickets_reported: 'Tickets Reported',
        subnav_my_tickets: 'My Tickets',
        footer_title: 'ICT Ticketing System',
        footer_subtitle: 'Institutional ICT Support & Issue Tracking System',
        home_hero_title: 'Report all Technical ICT issues by Submitting a Ticket',
        home_hero_text: 'Report ICT incidents quickly and track updates with your unique code. Admin and ICT staff can securely manage ticket lifecycles in one place.',
        home_register_btn: 'Register',
        home_report_btn: 'Report an Issue',
        home_track_btn: 'Check Issue Status',
        home_for_employees: 'For Employees',
        home_for_employees_text: 'Register with your employee badge, report your issue, and keep your tracking code.',
        home_for_ict: 'For ICT Staff',
        home_for_ict_text: 'Login to view assignments, update ticket status, and communicate resolutions.',
        home_for_admins: 'For Admins',
        home_for_admins_text: 'Manage users, departments, and institutional support performance analytics.',
        employee_dashboard_title: 'Employee Dashboard',
        login_title: 'Employee Login',
        login_email_label: 'Email',
        login_password_label: 'Password',
        login_submit: 'Login',
        login_registered_success: 'Registration successful. You can now login.',
        login_register_prompt: 'New employee?',
        login_register_link: 'Register with your badge',
        login_support_notice: 'If you face any problem, please contact the ICT support team: 0763364721',
        login_seed_info: 'Seed accounts (demo): admin@institution.edu / pass123 and ict1@institution.edu / pass123',
        register_title: 'Employee Registration',
        register_intro: 'Create your account using your official employee badge number.',
        register_badge_label: 'Employee badge / check number',
        register_phone_label: 'Phone Number',
        register_password_label: 'Password',
        register_confirm_password_label: 'Confirm Password',
        register_submit: 'Create Account',
        register_login_prompt: 'Already registered?',
        register_login_link: 'Login',
        register_error_required: 'Please complete all required fields.',
        register_error_password_match: 'Passwords do not match.',
        register_error_password_length: 'Password must be at least 8 characters.',
        register_error_duplicate: 'Email or employee badge already exists.',
        register_error_department: 'Please select a valid department.',
        thank_you_title: 'Thank you for registering',
        thank_you_intro: 'Your account has been created successfully.',
        thank_you_redirect_prefix: 'You will be redirected to the login page in',
        thank_you_fallback: 'If the redirect does not start automatically, use the button below.',
        thank_you_login_now: 'Go to Login',
        track_title: 'Check Issue Status',
        track_code_label: 'Tracking Code',
        track_code_placeholder: 'Example: ICT-AB12CD34-260426',
        track_submit_btn: 'Track Issue',
        track_searching: 'Searching...',
        track_result_status: 'Status:',
        track_result_category: 'Category:',
        track_result_department: 'Department:',
        track_result_timeline: 'Timeline',
        track_result_no_updates: 'No updates yet.',
        report_title: 'Report ICT Issue',
        report_intro: 'Complete all required steps. Invalid employee numbers are blocked automatically.',
        report_stepper_1: 'Verify',
        report_stepper_2: 'Issue Details',
        report_stepper_3: 'Evidence',
        report_stepper_4: 'Submit',
        report_stepper_5: 'Submitted',
        report_side_title: 'How it works',
        report_side_step1: 'Verify your identity with your employee number.',
        report_side_step2: 'Select your department and describe the issue.',
        report_side_step3: 'Attach a photo or screenshot as evidence.',
        report_side_step4: 'Submit and keep your tracking code to check status.',
        report_side_help_title: 'Need assistance?',
        report_side_help_text: 'Our ICT support team is ready to help you.',
        report_s1_title: 'Step 1: Employee Verification',
        report_s1_desc: 'Category: Identity and access validation.',
        report_employee_label: 'Employee Number / Badge ID',
        report_verify_continue: 'Verify & Continue',
        report_s2_title: 'Step 2: Issue Classification',
        report_s2_desc: 'Category: Department, issue type, and problem context.',
        report_department_label: 'Department',
        report_department_placeholder: 'Select Department',
        report_category_label: 'Issue Category',
        report_category_placeholder: 'Select Category',
        report_subcategory_label: 'Subcategory',
        report_subcategory_placeholder: 'Select Subcategory',
        report_description_label: 'Description (Optional)',
        report_description_placeholder: 'Provide any helpful context...',
        report_back: 'Back',
        report_continue: 'Continue',
        report_s3_title: 'Step 3: Supporting Evidence',
        report_s3_desc: 'Category: Required image proof or screenshots.',
        report_evidence_label: 'Attach Evidence (Required)',
        report_evidence_note: 'Mobile camera capture is supported on compatible devices.',
        report_s4_title: 'Step 4: Review and Submit',
        report_s4_desc: 'Category: Final confirmation and ticket creation.',
        report_submit_note: 'Submit your issue now. A unique tracking code will be generated and sent to your registered email.',
        report_submit_btn: 'Submit Ticket',
        common_add: 'Add',
        common_cancel: 'Cancel',
        common_back: 'Back',
        common_next: 'Next',
        modal_step_personal: 'Personal Info',
        modal_step_work: 'Work Info',
        modal_step_password: 'Password',
        common_update: 'Update',
        common_delete: 'Delete',
        common_filter: 'Filter',
        common_assign: 'Assign',
        common_tracking_code: 'Tracking Code',
        common_employee: 'Employee',
        common_department: 'Department',
        common_issue: 'Issue',
        common_current: 'Current',
        common_status: 'Status',
        common_priority: 'Priority',
        common_created: 'Created',
        common_members: 'Members',
        admin_edit_department: 'Edit Department',
        admin_edit_user: 'Edit User',
        staff_edit_employee: 'Edit Employee',
        common_reason: 'Reason',
        common_registered: 'Registered',
        subnav_approvals: 'Approvals',
        admin_approvals_title: 'Account Approvals',
        admin_approvals_subtitle: 'Review and approve employee registrations before they can access the system.',
        admin_approvals_pending: 'Pending Registrations',
        admin_approve: 'Approve',
        admin_reject: 'Reject',
        admin_reject_reason: 'Reason for rejection',
        admin_approvals_none: 'No pending registrations',
        admin_approvals_none_sub: 'New employee accounts will appear here for review.',
        admin_approvals_recent: 'Recently Reviewed',
        admin_approvals_no_reviewed: 'No accounts have been reviewed yet.',
        waiting_title: 'Account Awaiting Approval',
        waiting_intro: 'Your registration has been received and is waiting for review by the administrator.',
        waiting_notice: 'You will be able to log in as soon as your account is approved.',
        waiting_notice_sub: 'An email will be sent to you once the administrator approves or rejects your request.',
        waiting_support: 'If you believe this is taking too long, please contact the ICT support team: 0763364721',
        waiting_login_link: 'Try Login Again',
        nav_home: 'Home',
        thank_you_pending_title: 'Registration Received',
        thank_you_pending_intro: 'Your registration has been submitted and is now awaiting administrator approval.',
        thank_you_pending_hint: 'Once approved, you will receive an email with your login details and can start submitting tickets.',
        common_updated: 'Updated',
        common_all_tickets: 'All Issues',
        common_no_tickets: 'No tickets yet',
        common_tickets: 'tickets',
        common_full_name: 'Full Name',
        common_employee_number: 'Employee Number',
        common_phone_number: 'Phone Number',
        common_email: 'Email',
        common_job_title: 'Job Title',
        common_role: 'Role',
        common_role_employee: 'Employee',
        common_role_ict: 'ICT Staff',
        common_role_admin: 'Admin',
        common_name: 'Name',
        common_phone: 'Phone',
        common_action: 'Action',
        common_description: 'Description',
        common_hours: 'hours',
        admin_dashboard_title: 'Admin Dashboard',
        admin_total_tickets: 'Total Tickets',
        admin_submitted: 'Submitted',
        admin_in_progress: 'In Progress',
        admin_resolved_closed: 'Resolved / Closed',
        admin_avg_resolution_title: 'Average Resolution Time',
        admin_no_resolved: 'No resolved tickets yet',
        admin_departments_title: 'Departments Management',
        admin_add_department: 'Add Department',
        admin_department_name_placeholder: 'Department Name',
        admin_existing_departments: 'Existing Departments',
        admin_ticket_oversight_title: 'Ticket Oversight',
        admin_all_statuses: 'All Statuses',
        admin_all_departments: 'All Departments',
        admin_assigned_ict: 'Assigned ICT',
        admin_reassign: 'Reassign',
        admin_select_ict: 'Select ICT',
        admin_reports_title: 'Reports & Analytics',
        admin_settings_title: 'Settings',
        admin_settings_intro: 'Update your profile details and password.',
        admin_current_password_label: 'Current Password',
        admin_new_password_label: 'New Password',
        admin_confirm_password_label: 'Confirm New Password',
        admin_settings_save: 'Save Changes',
        admin_ict_performance: 'ICT Staff Performance',
        admin_ticket_trends: 'Ticket Trends (Last 14 Entries)',
        admin_common_categories: 'Common Issue Categories',
        admin_users_title: 'Users Management',
        admin_add_user: 'Add User',
        admin_password_label: 'Password (for Admin/ICT)',
        admin_create_user: 'Create User',
        admin_cancel_edit: 'Cancel Edit',
        admin_all_users: 'All Users',
        admin_employee_no: 'Employee No.',
        admin_total_users: 'Total Users',
        admin_pending_approvals: 'Pending Approvals',
        admin_administrators: 'Administrators',
        admin_employees: 'Employees',
        common_approval: 'Approval',
        staff_dashboard_title: 'ICT Staff Dashboard',
        staff_assigned_tickets: 'Assigned Tickets',
        staff_pending_tickets: 'Pending Tickets',
        staff_completed_tickets: 'Completed Tickets',
        staff_workload_title: 'Workload View',
        staff_workload_text: 'Your current active ticket load is',
        staff_open_tickets: 'Open My Assigned Tickets',
        staff_my_assigned_tickets: 'My Assigned Tickets',
        staff_comment_update: 'Comment / Update',
        staff_comment_placeholder: 'Visible in employee tracking timeline',
        staff_resolution_notes: 'Resolution Notes',
        staff_resolution_placeholder: 'Internal/closure notes',
        staff_save_update: 'Save Update',
        confirm_delete_department: 'Delete this department?',
        confirm_delete_user: 'Delete user?',
        otp_title: 'Enter Verification Code',
        otp_code_label: 'Verification Code',
        otp_verify_btn: 'Verify & Login',
        admin_good_morning: 'Good Morning',
        admin_good_afternoon: 'Good Afternoon',
        admin_good_evening: 'Good Evening',
        admin_dashboard_overview: 'Dashboard Overview',
        admin_daily_insight: 'Daily Insight',
        admin_badge_alltime: 'All Time',
        admin_badge_open: 'Open',
        admin_badge_active: 'Active',
        admin_badge_done: 'Done',
        admin_badge_new: 'New',
        admin_badge_14d: '14 Days',
        admin_tickets_status: 'Tickets by Status',
        admin_tickets_status_sub: 'Distribution across the full ticket lifecycle',
        admin_top_categories_sub: 'Where problems are concentrated',
        admin_no_categories: 'No categories yet',
        admin_ticket_trends_title: 'Ticket Trends',
        admin_ticket_trends_sub: 'New tickets over the last 14 days',
        admin_performance_title: 'Performance Snapshot',
        admin_performance_sub: 'Speed and completion overview',
        admin_open_rate: 'Open Rate',
        admin_recent_tickets: 'Recent Tickets',
        admin_recent_tickets_sub: 'Latest submissions across all departments',
        admin_view_all: 'View All',
        admin_view_all_tickets: 'View All Tickets',
        admin_insights_title: 'Insights',
        admin_insights_sub: 'Quick signals worth your attention',
        admin_no_tickets: 'No tickets yet',
        staff_new_assigned: 'New Assigned',
        staff_my_tickets_link: 'My Assigned Tickets',
        staff_performance_title: 'My Performance',
        staff_performance_sub: 'How you are tracking on assigned work',
        staff_completion_rate: 'Completion Rate',
        staff_open_now: 'Open Now',
        staff_insights_sub: 'Signals from your ticket queue',
        staff_recent_tickets: 'Recent Assigned Tickets',
        staff_recent_tickets_sub: 'Latest tickets in your queue',
        staff_quick_links: 'Quick Links',
        staff_quick_links_sub: 'Jump straight to what you need',
        staff_employees: 'Employees',
        employee_report_issue: 'Report an Issue',
        employee_activity_title: 'My Activity',
        employee_activity_sub: 'A snapshot of your request history',
        employee_insights_sub: 'What is happening with your requests',
        employee_recent_tickets: 'My Recent Tickets',
        employee_recent_tickets_sub: 'Your latest support requests',
        employee_no_tickets: 'No tickets yet',
        employee_no_tickets_hint: 'Report your first issue to get started.',
        reports_page_title: 'Reports & Analytics',
        reports_page_subtitle: 'Filter by date range to explore ticket performance across the institution.',
        reports_badge_range: 'Range',
        reports_filter_title: 'Filter Reports',
        reports_filter_sub: 'Narrow results by submission date',
        reports_trends_sub: 'Tickets in the selected period',
        reports_status_sub: 'Distribution within the selected range',
        reports_staff_sub: 'Tickets handled per ICT staff member',
        reports_categories_sub: 'Where problems are concentrated',
        reports_department_breakdown: 'Department Breakdown',
        reports_dept_sub: 'Tickets reported per department',
        reports_user_activity: 'User Activity Trends',
        reports_activity_sub: 'Most active reporters of tickets',
        reports_no_data: 'No data',
        reports_no_data_range: 'No data in selected range',
        tickets_page_subtitle: 'Review every ticket across the institution, assign ICT staff, and resolve issues.',
        admin_all_tickets: 'All Tickets',
        users_page_subtitle: 'Create, edit, and manage user accounts and their roles.',
        departments_page_subtitle: 'Organise the institution into departments for cleaner ticket routing.',
        staff_assigned_subtitle: 'Tickets assigned to you — update status and upload resolution photos.',
        employees_page_subtitle: 'Manage employee profiles and contact details.',
        my_tickets_employee_subtitle: 'Track the status and history of all your support requests.',
        settings_page_subtitle: 'Update your profile details and password.',
        common_export: 'Export CSV',
        common_clear: 'Clear',
        common_from: 'From',
        common_to: 'To'
    },
    sw: {
        menu_toggle: 'Fungua menyu',
        nav_dashboard: 'Dashibodi',
        nav_my_panel: 'Paneli Yangu',
        nav_logout: 'Toka',
        nav_report_issue: 'Ripoti Tatizo',
        nav_check_status: 'Angalia Hali',
        nav_register: 'Jisajili',
        nav_login: 'Ingia',
        subnav_dashboard: 'Dashibodi',
        subnav_departments: 'Idara',
        subnav_users: 'Watumiaji',
        subnav_tickets: 'Tiketi',
        subnav_reports: 'Ripoti',
        subnav_settings: 'Mipangilio',
        subnav_tickets_reported: 'Tiketi Zilizoripotiwa',
        subnav_my_tickets: 'Tiketi Zangu',
        footer_title: 'Mfumo wa Tiketi za ICT',
        footer_subtitle: 'Mfumo wa Usaidizi wa ICT na Ufuatiliaji wa Matatizo',
        home_hero_title: 'Mfumo wa Usaidizi wa ICT na Ufuatiliaji wa Matatizo',
        home_hero_text: 'Ripoti matukio ya ICT kwa haraka na fuatilia maendeleo kwa msimbo wako maalum. Admin na wafanyakazi wa ICT wanaweza kusimamia mzunguko wa tiketi kwa usalama.',
        home_register_btn: 'Jisajili',
        home_report_btn: 'Ripoti Tatizo',
        home_track_btn: 'Angalia Hali ya Tatizo',
        home_for_employees: 'Kwa Wafanyakazi',
        home_for_employees_text: 'Jisajili kwa namba yako ya kitambulisho cha mfanyakazi, ripoti tatizo, na hifadhi msimbo wako wa ufuatiliaji.',
        home_for_ict: 'Kwa Wafanyakazi wa ICT',
        home_for_ict_text: 'Ingia kuona majukumu, kusasisha hali ya tiketi, na kuwasilisha suluhisho.',
        home_for_admins: 'Kwa Wasimamizi',
        home_for_admins_text: 'Simamia watumiaji, idara, na takwimu za utendaji wa msaada wa taasisi.',
        employee_dashboard_title: 'Dashibodi ya Mfanyakazi',
        login_title: 'Kuingia kwa Mfanyakazi',
        login_email_label: 'Barua Pepe',
        login_password_label: 'Nenosiri',
        login_submit: 'Ingia',
        login_registered_success: 'Usajili umefanikiwa. Sasa unaweza kuingia.',
        login_register_prompt: 'Mfanyakazi mpya?',
        login_register_link: 'Jisajili kwa kitambulisho chako',
        login_support_notice: 'Ukipata tatizo lolote, tafadhali wasiliana na timu ya msaada wa ICT: 0763364721',
        login_seed_info: 'Akaunti za mfano: admin@institution.edu / pass123 na ict1@institution.edu / pass123',
        register_title: 'Usajili wa Mfanyakazi',
        register_intro: 'Unda akaunti kwa kutumia namba rasmi ya kitambulisho cha mfanyakazi.',
        register_badge_label: 'Namba ya Kitambulisho / Mfanyakazi',
        register_phone_label: 'Namba ya Simu',
        register_password_label: 'Nenosiri',
        register_confirm_password_label: 'Thibitisha Nenosiri',
        register_submit: 'Unda Akaunti',
        register_login_prompt: 'Tayari umesajiliwa?',
        register_login_link: 'Ingia',
        register_error_required: 'Tafadhali jaza taarifa zote muhimu.',
        register_error_password_match: 'Nenosiri halifanani.',
        register_error_password_length: 'Nenosiri lazima liwe na angalau herufi 8.',
        register_error_duplicate: 'Barua pepe au namba ya mfanyakazi tayari ipo.',
        register_error_department: 'Tafadhali chagua idara sahihi.',
        thank_you_title: 'Asante kwa kujisajili',
        thank_you_intro: 'Akaunti yako imeundwa kwa mafanikio.',
        thank_you_redirect_prefix: 'Utaelekezwa kwenye ukurasa wa kuingia baada ya',
        thank_you_fallback: 'Ikiwa uelekezaji haujaanza kiotomatiki, tumia kitufe kilicho hapa chini.',
        thank_you_login_now: 'Nenda kuingia',
        track_title: 'Angalia Hali ya Tatizo',
        track_code_label: 'Msimbo wa Ufuatiliaji',
        track_code_placeholder: 'Mfano: ICT-AB12CD34-260426',
        track_submit_btn: 'Fuatilia Tatizo',
        track_searching: 'Inatafuta...',
        track_result_status: 'Hali:',
        track_result_category: 'Kategoria:',
        track_result_department: 'Idara:',
        track_result_timeline: 'Muda wa Matukio',
        track_result_no_updates: 'Bado hakuna masasisho.',
        report_title: 'Ripoti Tatizo la ICT',
        report_intro: 'Kamilisha hatua zote muhimu. Namba zisizo sahihi za mfanyakazi zitazuiwa kiotomatiki.',
        report_stepper_1: 'Hakiki',
        report_stepper_2: 'Maelezo ya Tatizo',
        report_stepper_3: 'Ushahidi',
        report_stepper_4: 'Tuma',
        report_stepper_5: 'Imetumwa',
        report_side_title: 'Jinsi Inavyofanya Kazi',
        report_side_step1: 'Thibitisha utambulisho wako kwa nambari yako ya mfanyakazi.',
        report_side_step2: 'Chagua idara yako na ueleze tatizo.',
        report_side_step3: 'Ambatanisha picha au skrini kama ushahidi.',
        report_side_step4: 'Tuma na uhifadhi msimbo wako wa ufuatiliaji kuangalia hali.',
        report_side_help_title: 'Unahitaji Usaidizi?',
        report_side_help_text: 'Timu yetu ya usaidizi wa ICT iko tayari kukusaidia.',
        report_s1_title: 'Hatua ya 1: Uhakiki wa Mfanyakazi',
        report_s1_desc: 'Kategoria: Uthibitisho wa utambulisho na ruhusa.',
        report_employee_label: 'Namba ya Mfanyakazi / Kitambulisho',
        report_verify_continue: 'Hakiki na Endelea',
        report_s2_title: 'Hatua ya 2: Uainishaji wa Tatizo',
        report_s2_desc: 'Kategoria: Idara, aina ya tatizo, na maelezo ya tukio.',
        report_department_label: 'Idara',
        report_department_placeholder: 'Chagua Idara',
        report_category_label: 'Kategoria ya Tatizo',
        report_category_placeholder: 'Chagua Kategoria',
        report_subcategory_label: 'Kategoria Ndogo',
        report_subcategory_placeholder: 'Chagua Kategoria Ndogo',
        report_description_label: 'Maelezo (Si Lazima)',
        report_description_placeholder: 'Weka maelezo muhimu hapa...',
        report_back: 'Rudi',
        report_continue: 'Endelea',
        report_s3_title: 'Hatua ya 3: Ushahidi wa Kusaidia',
        report_s3_desc: 'Kategoria: Picha au ushahidi unaohitajika.',
        report_evidence_label: 'Ambatisha Ushahidi (Inahitajika)',
        report_evidence_note: 'Upigaji picha kwa kamera ya simu unaungwa mkono kwenye vifaa vinavyoruhusiwa.',
        report_s4_title: 'Hatua ya 4: Kagua na Tuma',
        report_s4_desc: 'Kategoria: Uthibitisho wa mwisho na uundaji wa tiketi.',
        report_submit_note: 'Tuma tatizo lako sasa. Namba ya ufuatiliaji itatengenezwa na kutumwa kwenye barua pepe yako.',
        report_submit_btn: 'Tuma Tiketi',
        common_add: 'Ongeza',
        common_cancel: 'Ghairi',
        common_back: 'Rudi',
        common_next: 'Endelea',
        modal_step_personal: 'Taarifa Binafsi',
        modal_step_work: 'Taarifa za Kazi',
        modal_step_password: 'Nenosiri',
        common_update: 'Sasisha',
        common_delete: 'Futa',
        common_filter: 'Chuja',
        common_assign: 'Panga',
        common_tracking_code: 'Msimbo wa Ufuatiliaji',
        common_employee: 'Mfanyakazi',
        common_department: 'Idara',
        common_issue: 'Tatizo',
        common_current: 'Ya Sasa',
        common_status: 'Hali',
        common_priority: 'Kipaumbele',
        common_created: 'Imeundwa',
        common_members: 'Wanachama',
        admin_edit_department: 'Hariri Idara',
        admin_edit_user: 'Hariri Mtumiaji',
        staff_edit_employee: 'Hariri Mfanyakazi',
        common_reason: 'Sababu',
        common_registered: 'Alisajiliwa',
        subnav_approvals: 'Idhini',
        admin_approvals_title: 'Idhini za Akaunti',
        admin_approvals_subtitle: 'Kagua na uidhinishe usajili wa wafanyakazi kabla ya kufikia mfumo.',
        admin_approvals_pending: 'Usajili Unaosubiri',
        admin_approve: 'Idhinisha',
        admin_reject: 'Kataa',
        admin_reject_reason: 'Sababu ya kukataa',
        admin_approvals_none: 'Hakuna usajili unaosubiri',
        admin_approvals_none_sub: 'Akaunti mpya za wafanyakazi zitaonekana hapa kwa ukaguzi.',
        admin_approvals_recent: 'Zilizokaguliwa Hivi Karibuni',
        admin_approvals_no_reviewed: 'Hakuna akaunti zilizokaguliwa bado.',
        waiting_title: 'Akaunti Inasubiri Idhini',
        waiting_intro: 'Usajili wako umepokelewa na unasubiri ukaguzi wa msimamizi.',
        waiting_notice: 'Utaweza kuingia mara tu akaunti yako itakapoidhinishwa.',
        waiting_notice_sub: 'Barua pepe itatumwa kwako mara tu msimamizi atakapoidhinisha au kukataa ombi lako.',
        waiting_support: 'Ikiwa unafikiri hii inachukua muda mrefu, tafadhali wasiliana na timu ya usaidizi wa ICT: 0763364721',
        waiting_login_link: 'Jaribu Kuingia Tena',
        nav_home: 'Nyumbani',
        thank_you_pending_title: 'Usajili Umepokelewa',
        thank_you_pending_intro: 'Usajili wako umewasilishwa na sasa unasubiri idhini ya msimamizi.',
        thank_you_pending_hint: 'Mara tu utakapoidhinishwa, utapokea barua pepe na utaweza kuanza kuwasilisha tiketi.',
        common_updated: 'Imesasishwa',
        common_all_tickets: 'Masuala Yote',
        common_no_tickets: 'Hakuna tiketi bado',
        common_tickets: 'tiketi',
        common_full_name: 'Jina Kamili',
        common_employee_number: 'Namba ya Mfanyakazi',
        common_phone_number: 'Namba ya Simu',
        common_email: 'Barua Pepe',
        common_job_title: 'Cheo cha Kazi',
        common_role: 'Wajibu',
        common_role_employee: 'Mfanyakazi',
        common_role_ict: 'Mfanyakazi wa ICT',
        common_role_admin: 'Admin',
        common_name: 'Jina',
        common_phone: 'Simu',
        common_action: 'Kitendo',
        common_description: 'Maelezo',
        common_hours: 'saa',
        admin_dashboard_title: 'Dashibodi ya Admin',
        admin_total_tickets: 'Jumla ya Tiketi',
        admin_submitted: 'Zilizowasilishwa',
        admin_in_progress: 'Zinaendelea',
        admin_resolved_closed: 'Zimetatuliwa / Zimefungwa',
        admin_avg_resolution_title: 'Wastani wa Muda wa Utatuzi',
        admin_no_resolved: 'Bado hakuna tiketi zilizotatuliwa',
        admin_departments_title: 'Usimamizi wa Idara',
        admin_add_department: 'Ongeza Idara',
        admin_department_name_placeholder: 'Jina la Idara',
        admin_existing_departments: 'Idara Zilizopo',
        admin_ticket_oversight_title: 'Usimamizi wa Tiketi',
        admin_all_statuses: 'Hali Zote',
        admin_all_departments: 'Idara Zote',
        admin_assigned_ict: 'ICT Aliyepangiwa',
        admin_reassign: 'Panga Tena',
        admin_select_ict: 'Chagua ICT',
        admin_reports_title: 'Ripoti na Takwimu',
        admin_settings_title: 'Mipangilio',
        admin_settings_intro: 'Sasisha taarifa zako binafsi na nenosiri.',
        admin_current_password_label: 'Nenosiri la Sasa',
        admin_new_password_label: 'Nenosiri Jipya',
        admin_confirm_password_label: 'Thibitisha Nenosiri Jipya',
        admin_settings_save: 'Hifadhi Mabadiliko',
        admin_ict_performance: 'Utendaji wa Wafanyakazi wa ICT',
        admin_ticket_trends: 'Mwelekeo wa Tiketi (Ingizo 14 za Mwisho)',
        admin_common_categories: 'Kategoria za Matatizo ya Kawaida',
        admin_users_title: 'Usimamizi wa Watumiaji',
        admin_add_user: 'Ongeza Mtumiaji',
        admin_password_label: 'Nenosiri (kwa Admin/ICT)',
        admin_create_user: 'Unda Mtumiaji',
        admin_cancel_edit: 'Ghairi Uhariri',
        admin_all_users: 'Watumiaji Wote',
        admin_employee_no: 'Na. ya Mfanyakazi',
        admin_total_users: 'Jumla ya Watumiaji',
        admin_pending_approvals: 'Idhini Zinazosubiri',
        admin_administrators: 'Wasimamizi',
        admin_employees: 'Wafanyakazi',
        common_approval: 'Idhini',
        staff_dashboard_title: 'Dashibodi ya Wafanyakazi wa ICT',
        staff_assigned_tickets: 'Tiketi Zilizopangiwa',
        staff_pending_tickets: 'Tiketi Zinazosubiri',
        staff_completed_tickets: 'Tiketi Zilizokamilika',
        staff_workload_title: 'Muonekano wa Mzigo wa Kazi',
        staff_workload_text: 'Mzigo wako wa tiketi zinazoendelea kwa sasa ni',
        staff_open_tickets: 'Fungua Tiketi Zangu Zilizopangiwa',
        staff_my_assigned_tickets: 'Tiketi Zangu Zilizopangiwa',
        staff_comment_update: 'Maoni / Sasisho',
        staff_comment_placeholder: 'Inaonekana kwenye muda wa ufuatiliaji wa mfanyakazi',
        staff_resolution_notes: 'Maelezo ya Utatuzi',
        staff_resolution_placeholder: 'Maelezo ya ndani/kufunga',
        staff_save_update: 'Hifadhi Sasisho',
        confirm_delete_department: 'Futa idara hii?',
        confirm_delete_user: 'Futa mtumiaji?',
        otp_title: 'Weka Msimbo wa Uthibitisho',
        otp_code_label: 'Msimbo wa Uthibitisho',
        otp_verify_btn: 'Thibitisha & Ingia',
        admin_good_morning: 'Habari za Asubuhi',
        admin_good_afternoon: 'Habari za Mchana',
        admin_good_evening: 'Habari za Jioni',
        admin_dashboard_overview: 'Muhtasari wa Dashibodi',
        admin_daily_insight: 'Uchambuzi wa Kila Siku',
        admin_badge_alltime: 'Muda Wote',
        admin_badge_open: 'Wazi',
        admin_badge_active: 'Inayoendelea',
        admin_badge_done: 'Imekamilika',
        admin_badge_new: 'Mpya',
        admin_badge_14d: 'Siku 14',
        admin_tickets_status: 'Tiketi kwa Hali',
        admin_tickets_status_sub: 'Usambazaji katika mzunguko mzima wa tiketi',
        admin_top_categories_sub: 'Ambapo matatizo yamejilimbikizia',
        admin_no_categories: 'Bado hakuna kategoria',
        admin_ticket_trends_title: 'Mwelekeo wa Tiketi',
        admin_ticket_trends_sub: 'Tiketi mpya kwa siku 14 zilizopita',
        admin_performance_title: 'Muhtasari wa Utendaji',
        admin_performance_sub: 'Muhtasari wa kasi na ukamilishaji',
        admin_open_rate: 'Kiwango cha Wazi',
        admin_recent_tickets: 'Tiketi za Karibuni',
        admin_recent_tickets_sub: 'Mawasilisho ya hivi karibuni kutoka idara zote',
        admin_view_all: 'Angalia Zote',
        admin_view_all_tickets: 'Angalia Tiketi Zote',
        admin_insights_title: 'Uchambuzi',
        admin_insights_sub: 'Ishara za haraka zinazostahili umakini wako',
        admin_no_tickets: 'Bado hakuna tiketi',
        staff_new_assigned: 'Zilizopangiwa Mpya',
        staff_my_tickets_link: 'Tiketi Zangu Zilizopangiwa',
        staff_performance_title: 'Utendaji Wangu',
        staff_performance_sub: 'Jinsi unavyofuatilia kazi zilizopangiwa',
        staff_completion_rate: 'Kiwango cha Ukamilishaji',
        staff_open_now: 'Wazi Sasa',
        staff_insights_sub: 'Ishara kutoka foleni yako ya tiketi',
        staff_recent_tickets: 'Tiketi za Karibuni Zilizopangiwa',
        staff_recent_tickets_sub: 'Tiketi za hivi karibuni kwenye foleni yako',
        staff_quick_links: 'Viungo vya Haraka',
        staff_quick_links_sub: 'Rukia moja kwa moja unachohitaji',
        staff_employees: 'Wafanyakazi',
        employee_report_issue: 'Ripoti Tatizo',
        employee_activity_title: 'Shughuli Zangu',
        employee_activity_sub: 'Muhtasari wa historia ya maombi yako',
        employee_insights_sub: 'Kinachotokea kwa maombi yako',
        employee_recent_tickets: 'Tiketi Zangu za Karibuni',
        employee_recent_tickets_sub: 'Maombi yako ya hivi karibuni ya usaidizi',
        employee_no_tickets: 'Bado hakuna tiketi',
        employee_no_tickets_hint: 'Ripoti tatizo lako la kwanza kuanza.',
        reports_page_title: 'Ripoti na Takwimu',
        reports_page_subtitle: 'Chuja kwa muda wa tarehe kuona utendaji wa tiketi katika taasisi nzima.',
        reports_badge_range: 'Kipindi',
        reports_filter_title: 'Chuja Ripoti',
        reports_filter_sub: 'Punguza matokeo kwa tarehe ya kuwasilishwa',
        reports_trends_sub: 'Tiketi katika kipindi kilichochaguliwa',
        reports_status_sub: 'Usambazaji katika kipindi kilichochaguliwa',
        reports_staff_sub: 'Tiketi zinazoshughulikiwa na kila mfanyakazi wa ICT',
        reports_categories_sub: 'Ambapo matatizo yamejilimbikizia',
        reports_department_breakdown: 'Mgawanyo kwa Idara',
        reports_dept_sub: 'Tiketi zilizoripotiwa kwa kila idara',
        reports_user_activity: 'Mielekeo ya Shughuli za Watumiaji',
        reports_activity_sub: 'Wanaoripoti tiketi zaidi',
        reports_no_data: 'Hakuna data',
        reports_no_data_range: 'Hakuna data katika kipindi kilichochaguliwa',
        tickets_page_subtitle: 'Pitia kila tiketi katika taasisi nzima, panga wafanyakazi wa ICT, na tatua matatizo.',
        admin_all_tickets: 'Tiketi Zote',
        users_page_subtitle: 'Unda, hariri, na simamia akaunti za watumiaji na majukumu yao.',
        departments_page_subtitle: 'Panga taasisi kwa idara ili mwelekeo wa tiketi uwe rahisi.',
        staff_assigned_subtitle: 'Tiketi ulizopangiwa — sasisha hali na pakia picha za utatuzi.',
        employees_page_subtitle: 'Simamia taarifa za wafanyakazi na mawasiliano yao.',
        my_tickets_employee_subtitle: 'Fuatilia hali na historia ya maombi yako yote ya usaidizi.',
        settings_page_subtitle: 'Sasisha taarifa zako binafsi na nenosiri.',
        common_export: 'Hamisha CSV',
        common_clear: 'Futa',
        common_from: 'Kuanzia',
        common_to: 'Hadi'
    }
};

let appLanguage = localStorage.getItem('app_lang') || 'en';
if (!Object.prototype.hasOwnProperty.call(appTranslations, appLanguage)) {
    appLanguage = 'en';
}

function appTranslate(key, fallback) {
    const current = appTranslations[appLanguage] || appTranslations.en;
    return current[key] || appTranslations.en[key] || fallback || '';
}

function applyAppLanguage(lang) {
    appLanguage = Object.prototype.hasOwnProperty.call(appTranslations, lang) ? lang : 'en';
    localStorage.setItem('app_lang', appLanguage);
    document.documentElement.setAttribute('lang', appLanguage);

    document.querySelectorAll('[data-i18n]').forEach(function (el) {
        const key = el.getAttribute('data-i18n');
        el.textContent = appTranslate(key, el.textContent);
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(function (el) {
        const key = el.getAttribute('data-i18n-placeholder');
        el.setAttribute('placeholder', appTranslate(key, el.getAttribute('placeholder') || ''));
    });

    document.querySelectorAll('[data-i18n-aria-label]').forEach(function (el) {
        const key = el.getAttribute('data-i18n-aria-label');
        el.setAttribute('aria-label', appTranslate(key, el.getAttribute('aria-label') || ''));
    });

    document.querySelectorAll('[data-language-toggle]').forEach(function (toggle) {
        const code = toggle.querySelector('[data-lang-code]');
        if (code) code.textContent = appLanguage === 'en' ? 'EN' : 'SW';
        toggle.setAttribute('aria-label', appLanguage === 'en' ? 'Switch to Swahili' : 'Switch to English');
    });

    window.dispatchEvent(new CustomEvent('app-language-changed', { detail: { language: appLanguage } }));
}

window.appI18n = {
    getLanguage: function () {
        return appLanguage;
    },
    setLanguage: applyAppLanguage,
    t: appTranslate
};

document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            const nav = document.getElementById('mainNav');
            const isOpen = nav.classList.toggle('active');
            this.setAttribute('aria-expanded', String(isOpen));
        });
    }

    const adminShell = document.querySelector('.admin-shell');
    const adminToggles = document.querySelectorAll('[data-admin-menu-toggle]');
    if (adminShell && adminToggles.length > 0) {
        const backdrop = document.createElement('div');
        backdrop.className = 'admin-sidebar-backdrop';
        document.body.appendChild(backdrop);

        const setSidebar = (open) => {
            adminShell.classList.toggle('sidebar-open', open);
            adminToggles.forEach(t => t.setAttribute('aria-expanded', String(open)));
        };

        const toggleSidebar = () => setSidebar(!adminShell.classList.contains('sidebar-open'));

        adminToggles.forEach(btn => btn.addEventListener('click', toggleSidebar));
        backdrop.addEventListener('click', () => setSidebar(false));

        document.addEventListener('click', (e) => {
            if (!adminShell.classList.contains('sidebar-open')) return;
            const sidebar = adminShell.querySelector('.admin-sidebar');
            if (!sidebar) return;
            if (sidebar.contains(e.target)) return;
            if (e.target.closest && e.target.closest('[data-admin-menu-toggle]')) return;
            setSidebar(false);
        });
    }

    document.querySelectorAll('[data-language-toggle]').forEach(function (languageToggle) {
        languageToggle.addEventListener('click', function () {
            applyAppLanguage(appLanguage === 'en' ? 'sw' : 'en');
        });
    });

    function syncThemeIcons() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        document.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
            const moon = b.querySelector('[data-theme-icon="moon"]');
            const sun = b.querySelector('[data-theme-icon="sun"]');
            if (moon) moon.classList.toggle('admin-icon-svg-hidden', isDark);
            if (sun) sun.classList.toggle('admin-icon-svg-hidden', !isDark);
        });
    }

    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (isDark) {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('app_theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('app_theme', 'dark');
            }
            syncThemeIcons();
        });
    });
    syncThemeIcons();

    document.querySelectorAll('[data-profile-menu]').forEach(function (wrap) {
        const btn = wrap.querySelector('.admin-profile-icon');
        const menu = wrap.querySelector('.admin-profile-menu');
        if (!btn || !menu) return;

        function setProfileMenu(open) {
            menu.classList.toggle('hidden', !open);
            btn.setAttribute('aria-expanded', String(open));
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            setProfileMenu(menu.classList.contains('hidden'));
        });
        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) setProfileMenu(false);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setProfileMenu(false);
        });
    });

    const flashToast = document.querySelector('[data-flash-toast]');
    if (flashToast) {
        window.setTimeout(function () {
            flashToast.classList.add('flash-toast-hide');
            window.setTimeout(function () {
                flashToast.remove();
            }, 260);
        }, 2000);
    }
});

applyAppLanguage(appLanguage);
</script>
