<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/header.php';

if (empty($_SESSION['employee_number']) || empty($_SESSION['department_id']) || empty($_SESSION['category_id'])) {
    setFlash('Please complete all previous steps first.', 'error');
    redirect('report_step1.php');
}
?>
<section class="wizard-wrap">
    <h2>Report ICT Issue - Step 3: Attach Evidence & Submit</h2>
    <form method="POST" action="report_step4.php" enctype="multipart/form-data" class="form-grid">
        <?= csrf_field() ?>
        <label>
            Attach Evidence Photo (Required)
            <input type="file" name="evidence" accept="image/*" required />
        </label>
        <div class="wizard-actions">
            <a href="report_step2.php" class="btn btn-secondary">Back</a>
            <button type="submit" class="btn btn-primary">Submit Ticket</button>
        </div>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
