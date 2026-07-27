<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = $pageTitle ?? APP_NAME;
$user = currentUser();
$flash = getFlash();

/* Detect whether we are inside a role dashboard path. */
$isAdminDir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$isStaffDir = strpos($_SERVER['PHP_SELF'], '/staff/') !== false;
$isEmployeeDir = strpos($_SERVER['PHP_SELF'], '/employee/') !== false;

/* Admin, staff and employee pages all use the admin/panel app layout so
    they share the same sidebar, topbar-less appearance and color scheme. */
$isPanelDir = $isAdminDir || $isStaffDir || $isEmployeeDir;

/* Allow callers to force the panel layout even when the file is not in a
   subdirectory (e.g. report.php should appear inside the panel for
   logged-in users). This does not change asset path calculation. */
if (isset($forcePanel) && $forcePanel) {
    $isPanelDir = true;
}

/* For correct relative asset paths, treat any role subdirectory as a subdir. */
$isSubDir = $isAdminDir || $isStaffDir || $isEmployeeDir;
$baseUrl = $isSubDir ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
    <?php
    // Load a user-specific stylesheet when present so each user can have
    // their own look & feel without changing the shared CSS. Files are
    // stored under `assets/css/users/user_{id}.css` and are optional.
    if (!empty($user['id'])) {
        $userCssRel = 'assets/css/users/user_' . (int) $user['id'] . '.css';
        $userCssHref = $baseUrl . $userCssRel;
        // Check file existence using filesystem path relative to includes/
        if (file_exists(__DIR__ . '/../' . $userCssRel)) {
            echo '<link rel="stylesheet" href="' . e($userCssHref) . '">';
        }
    }
    ?>
</head>
<body class="<?= $isPanelDir ? 'admin-app-page' : '' ?>">

<!-- Page Header -->
<header class="topbar">
    <div class="container nav-wrap">

        <!-- App brand -->
        <a href="<?= $baseUrl ?>index.php" class="brand">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
            <span class="brand-text">ICT Support</span>
        </a>

        <!-- Mobile hamburger button -->
        <button class="menu-toggle" id="menuToggle" aria-label="Open menu" data-i18n-aria-label="menu_toggle" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Main navigation -->
        <nav id="mainNav">
            <?php if ($user): ?>
                <span class="nav-user"><?= e($user['full_name']) ?> &mdash; <?= strtoupper(e($user['role'])) ?></span>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?= $baseUrl ?>admin/dashboard.php" data-i18n="nav_dashboard">Dashboard</a>
                    <a href="<?= $baseUrl ?>admin/settings.php" data-i18n="subnav_settings">Settings</a>
                <?php elseif ($user['role'] === 'ict'): ?>
                    <a href="<?= $baseUrl ?>staff/dashboard.php" data-i18n="nav_my_panel">My Panel</a>
                    <a href="<?= $baseUrl ?>staff/my_tickets.php" data-i18n="subnav_tickets_reported">Tickets Reported</a>
                    <a href="<?= $baseUrl ?>staff/settings.php" data-i18n="subnav_settings">Settings</a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>employee/dashboard.php" data-i18n="nav_dashboard">Dashboard</a>
                    <a href="<?= $baseUrl ?>report.php" data-i18n="nav_report_issue">Report Issue</a>
                    <a href="<?= $baseUrl ?>employee/my_tickets.php" data-i18n="subnav_my_tickets">My Tickets</a>
                    <a href="<?= $baseUrl ?>employee/settings.php" data-i18n="subnav_settings">Settings</a>
                <?php endif; ?>
                <a class="btn btn-secondary btn-link" href="<?= $baseUrl ?>logout.php" data-i18n="nav_logout">Logout</a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>report.php" target="_self" data-i18n="nav_report_issue">Report Issue</a>
                <a href="<?= $baseUrl ?>track.php" data-i18n="nav_check_status">Check Status</a>
                <a class="btn btn-primary btn-link" href="<?= $baseUrl ?>login.php" target="_self" data-i18n="nav_login">Login</a>
            <?php endif; ?>
            <button type="button" id="languageToggle" class="btn btn-secondary btn-link" aria-label="Switch language" data-language-toggle>SW</button>
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
        report_stepper_1: '1. Verify',
        report_stepper_2: '2. Issue Details',
        report_stepper_3: '3. Evidence',
        report_stepper_4: '4. Submit',
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
        common_update: 'Update',
        common_delete: 'Delete',
        common_filter: 'Filter',
        common_assign: 'Assign',
        common_tracking_code: 'Tracking Code',
        common_employee: 'Employee',
        common_department: 'Department',
        common_issue: 'Issue',
        common_status: 'Status',
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
        admin_all_users: 'All Users',
        admin_employee_no: 'Employee No.',
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
        otp_verify_btn: 'Verify & Login'
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
        report_stepper_1: '1. Hakiki',
        report_stepper_2: '2. Maelezo ya Tatizo',
        report_stepper_3: '3. Ushahidi',
        report_stepper_4: '4. Tuma',
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
        common_update: 'Sasisha',
        common_delete: 'Futa',
        common_filter: 'Chuja',
        common_assign: 'Panga',
        common_tracking_code: 'Msimbo wa Ufuatiliaji',
        common_employee: 'Mfanyakazi',
        common_department: 'Idara',
        common_issue: 'Tatizo',
        common_status: 'Hali',
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
        admin_all_users: 'Watumiaji Wote',
        admin_employee_no: 'Na. ya Mfanyakazi',
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
        otp_verify_btn: 'Thibitisha & Ingia'
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
        toggle.textContent = appLanguage === 'en' ? 'EN / SW' : 'SW / EN';
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

    const toggleSidebar = () => {
        const isOpen = adminShell.classList.toggle('sidebar-open');
        adminToggles.forEach(t => t.setAttribute('aria-expanded', String(isOpen)));
    };

    adminToggles.forEach(btn => btn.addEventListener('click', toggleSidebar));
    backdrop.addEventListener('click', toggleSidebar);
}

document.querySelectorAll('[data-language-toggle]').forEach(function (languageToggle) {
    languageToggle.addEventListener('click', function () {
        applyAppLanguage(appLanguage === 'en' ? 'sw' : 'en');
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

applyAppLanguage(appLanguage);
</script>
