<?php
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';
$pageTitle = 'Home | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<!-- Hero section -->
<section class="hero">
    <div class="container">
        <h1 data-i18n="home_hero_title">Report all Technical ICT issues by Submitting a Ticket</h1>
        <p data-i18n="home_hero_text">Report ICT incidents quickly and track updates with your unique code. Admin and ICT staff can securely manage ticket lifecycles in one place.</p>
        <div class="hero-actions">
            <a href="report.php" target="_self" class="btn btn-primary" data-i18n="home_report_btn">Report an Issue</a>
            <a href="track.php" class="btn btn-secondary" data-i18n="home_track_btn">Check Issue Status</a>
        </div>
    </div>
</section>

<!-- Info panels -->
<section class="panel-grid container">
    <article class="panel-card">
        <h3 data-i18n="home_for_employees">For Employees</h3>
        <p data-i18n="home_for_employees_text">No login needed. Verify your badge, submit your issue, and keep your tracking code.</p>
    </article>
    <article class="panel-card">
        <h3 data-i18n="home_for_ict">For ICT Staff</h3>
        <p data-i18n="home_for_ict_text">Login to view assignments, update ticket status, and communicate resolutions.</p>
    </article>
    <article class="panel-card">
        <h3 data-i18n="home_for_admins">For Admins</h3>
        <p data-i18n="home_for_admins_text">Manage users, departments, and institutional support performance analytics.</p>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
