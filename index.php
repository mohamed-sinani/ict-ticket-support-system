<?php
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';
$pageTitle = 'Home | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
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

<section class="how-it-works container">
    <h2>How It Works</h2>
    <div class="steps-grid">
        <div class="step-item">
            <div class="step-number">1</div>
            <h4>Verify Your Identity</h4>
            <p>Enter your employee badge number to authenticate. No account needed for first-time reporting.</p>
        </div>
        <div class="step-item">
            <div class="step-number">2</div>
            <h4>Describe Your Issue</h4>
            <p>Select your department, choose a category, and provide details about the ICT problem you're facing.</p>
        </div>
        <div class="step-item">
            <div class="step-number">3</div>
            <h4>Upload Evidence</h4>
            <p>Attach screenshots or photos to help our ICT team understand and resolve your issue faster.</p>
        </div>
        <div class="step-item">
            <div class="step-number">4</div>
            <h4>Track & Get Resolved</h4>
            <p>Receive a unique tracking code. Use it to check real-time updates until your issue is resolved.</p>
        </div>
    </div>
</section>

<section class="features-section container">
    <h2>Why Use This System?</h2>
    <div class="features-grid">
        <div class="feature-card">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <h4>Fast Response</h4>
            <p>Issues are routed directly to the right ICT staff for quicker resolution times.</p>
        </div>
        <div class="feature-card">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <h4>Secure & Private</h4>
            <p>Your data is protected with enterprise-grade security and access controls.</p>
        </div>
        <div class="feature-card">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            <h4>Real-Time Updates</h4>
            <p>Track your ticket status and receive updates from ICT staff at every step.</p>
        </div>
        <div class="feature-card">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
            <h4>Centralized Management</h4>
            <p>All ICT issues in one place. Admins can monitor performance and manage departments.</p>
        </div>
    </div>
</section>

<section class="cta-section container">
    <div class="cta-card">
        <h2>Ready to Report an Issue?</h2>
        <p>Don't let ICT problems slow you down. Submit a ticket now and our team will get to work.</p>
        <div class="cta-actions">
            <a href="report.php" class="btn btn-primary">Report an Issue</a>
            <a href="track.php" class="btn btn-secondary">Track Existing Ticket</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
