<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$departments = getDepartments();
$categories = getCategories();

$pageTitle = 'Report an Issue | ' . APP_NAME;
$forcePanel = isLoggedIn();
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    $user = currentUser();
    if ($user['role'] === 'admin') {
        require_once __DIR__ . '/admin/_nav.php';
    } elseif ($user['role'] === 'ict') {
        require_once __DIR__ . '/staff/_nav.php';
    } else {
        require_once __DIR__ . '/employee/_nav.php';
    }
}

?>
<section class="report-page">
    <?php if (!$forcePanel): ?>
    <aside class="report-side">
        <div class="report-side-card">
            <div class="report-side-head">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 16 12 12 12 8"></polyline><line x1="12" y1="16" x2="12" y2="16"></line></svg>
                <h3 data-i18n="report_side_title">How it works</h3>
            </div>
            <ol class="report-steps">
                <li><span class="report-step-num">1</span><p data-i18n="report_side_step1">Verify your identity with your employee number.</p></li>
                <li><span class="report-step-num">2</span><p data-i18n="report_side_step2">Select your department and describe the issue.</p></li>
                <li><span class="report-step-num">3</span><p data-i18n="report_side_step3">Attach a photo or screenshot as evidence.</p></li>
                <li><span class="report-step-num">4</span><p data-i18n="report_side_step4">Submit and keep your tracking code to check status.</p></li>
            </ol>
        </div>
        <div class="report-side-card report-side-support">
            <div class="report-side-head">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                <h3 data-i18n="report_side_help_title">Need assistance?</h3>
            </div>
            <p class="small-text" data-i18n="report_side_help_text">Our ICT support team is ready to help you.</p>
            <a class="report-side-phone" href="tel:+255763364721">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                0763 364 721
            </a>
        </div>
    </aside>
    <?php endif; ?>

<div class="wizard-wrap report-wizard">
    <h2 data-i18n="report_title">Report ICT Issue</h2>
    <p class="small-text" data-i18n="report_intro">Complete all required steps. Invalid employee numbers are blocked automatically.</p>

    <div class="stepper" aria-label="Report steps">
        <span class="step active" data-step="1" data-num="1" data-i18n="report_stepper_1">Verify</span>
        <span class="step" data-step="2" data-num="2" data-i18n="report_stepper_2">Issue Details</span>
        <span class="step" data-step="3" data-num="3" data-i18n="report_stepper_3">Evidence</span>
        <span class="step" data-step="4" data-num="4" data-i18n="report_stepper_4">Submit</span>
        <span class="step" data-step="5" data-num="5" data-i18n="report_stepper_5">Submitted</span>
    </div>

    <form id="ticketWizard" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" id="employeeId" name="employee_id" value="">
        <section class="wizard-step active" data-step="1">
            <h3 data-i18n="report_s1_title">Step 1: Employee Verification</h3>
            <p class="small-text" data-i18n="report_s1_desc">Category: Identity and access validation.</p>
            <label><span data-i18n="report_employee_label">Employee Number / Badge ID</span>
                <input type="text" id="employeeNumber" name="employee_number" required>
            </label>
            <div class="wizard-actions">
                <button type="button" id="verifyEmployeeBtn" class="btn btn-primary" data-i18n="report_verify_continue">Verify & Continue</button>
            </div>
            <div id="employeeData" class="preview-box hidden"></div>
            <p id="verifyError" class="alert alert-danger hidden"></p>
        </section>

        <section class="wizard-step" data-step="2">
            <div id="employeeWelcome" class="employee-welcome hidden" aria-live="polite"></div>
            <h3 data-i18n="report_s2_title">Step 2: Issue Classification</h3>
            <p class="small-text" data-i18n="report_s2_desc">Category: Department, issue type, and problem context.</p>
            <label><span data-i18n="report_department_label">Department</span>
                <select name="department_id" id="departmentId" required>
                    <option value="" data-i18n="report_department_placeholder">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int) $dept['id'] ?>"><?= e($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span data-i18n="report_category_label">Issue Category</span>
                <select name="category_id" id="categoryId" required>
                    <option value="" data-i18n="report_category_placeholder">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span data-i18n="report_subcategory_label">Subcategory</span>
                <select name="subcategory_id" id="subcategoryId" required>
                    <option value="" data-i18n="report_subcategory_placeholder">Select Subcategory</option>
                </select>
            </label>
            <label><span>Priority</span>
                <select name="priority" required>
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </label>
            <label><span data-i18n="report_description_label">Description (Optional)</span>
                <textarea name="description" rows="4" placeholder="Provide any helpful context..." data-i18n-placeholder="report_description_placeholder"></textarea>
            </label>
            <div class="wizard-actions">
                <button type="button" class="btn btn-secondary" data-prev="1" data-i18n="report_back">Back</button>
                <button type="button" class="btn btn-primary" data-next="3" data-i18n="report_continue">Continue</button>
            </div>
        </section>

        <section class="wizard-step" data-step="3">
            <h3 data-i18n="report_s3_title">Step 3: Supporting Evidence</h3>
            <p class="small-text" data-i18n="report_s3_desc">Category: Required image proof or screenshots.</p>
            <span data-i18n="report_evidence_label">Attach Evidence (Required)</span>
            <label class="file-drop-zone">
                <input type="file" class="file-drop-input" name="evidence" accept="image/*" capture="environment" required>
                <span class="file-drop-content">
                    <svg class="file-drop-icon" xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    <span class="file-drop-text">
                        <strong>Drag &amp; drop or click to browse</strong>
                        <span>Supports: JPG, PNG, WebP, GIF</span>
                    </span>
                </span>
                <span class="file-drop-preview hidden">
                    <img src="" alt="Preview">
                    <span class="file-drop-name"></span>
                    <button type="button" class="file-drop-remove" aria-label="Remove file">&times;</button>
                </span>
            </label>
            <p class="small-text" data-i18n="report_evidence_note">Mobile camera capture is supported on compatible devices.</p>
            <div class="wizard-actions">
                <button type="button" class="btn btn-secondary" data-prev="2" data-i18n="report_back">Back</button>
                <button type="button" class="btn btn-primary" data-next="4" data-i18n="report_continue">Continue</button>
            </div>
        </section>

        <section class="wizard-step" data-step="4">
            <h3 data-i18n="report_s4_title">Step 4: Review and Submit</h3>
            <p class="small-text" data-i18n="report_s4_desc">Category: Final confirmation and ticket creation.</p>
            <p data-i18n="report_submit_note">Submit your issue now. A unique tracking code will be generated and sent to your registered email.</p>
            <div class="wizard-actions">
                <button type="button" class="btn btn-secondary" data-prev="3" data-i18n="report_back">Back</button>
                <button type="button" id="submitTicketBtn" class="btn btn-primary" data-i18n="report_submit_btn">Submit Ticket</button>
            </div>
        </section>

        <section class="wizard-step submit-success-step" data-step="5">
            <div id="submitResult" class="preview-box hidden"></div>
        </section>
    </form>
</div>
</section>
<script src="assets/js/report.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
