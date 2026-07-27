<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/header.php';

// Ensure employee is verified
if (empty($_SESSION['employee_number'])) {
    setFlash('Please verify your employee number first.', 'error');
    redirect('report_step1.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $subcategory = trim($_POST['subcategory'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'Medium';
    $allowedPriorities = ['Low', 'Medium', 'High', 'Critical'];
    if (!in_array($priority, $allowedPriorities, true)) {
        $priority = 'Medium';
    }

    if ($departmentId <= 0 || $categoryId <= 0) {
        setFlash('Please select a department and category.', 'error');
        redirect('report_step2.php');
    }

    $_SESSION['department_id'] = $departmentId;
    $_SESSION['category_id'] = $categoryId;
    $_SESSION['subcategory_name'] = $subcategory;
    $_SESSION['description'] = $description;
    $_SESSION['priority'] = $priority;

    redirect('report_step3.php');
}

$departments = getDepartments();
$categories = getCategories();
?>
<section class="wizard-wrap">
    <h2>Report ICT Issue - Step 2: Issue Details</h2>
    <form method="POST" action="report_step2.php" class="form-grid">
        <?= csrf_field() ?>
        <label>
            Department
            <select name="department_id" required>
                <option value="">Select Department</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= (int)$dept['id'] ?>" <?= (isset($_SESSION['department_id']) && $_SESSION['department_id'] === (int)$dept['id']) ? 'selected' : '' ?>><?= e($dept['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Issue Category
            <select name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= (isset($_SESSION['category_id']) && $_SESSION['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Subcategory
            <input type="text" name="subcategory" placeholder="Optional" value="<?= e($_SESSION['subcategory_name'] ?? '') ?>" />
        </label>
        <label>
            Priority
            <select name="priority" required>
                <option value="Low" <?= ($_SESSION['priority'] ?? 'Medium') === 'Low' ? 'selected' : '' ?>>Low</option>
                <option value="Medium" <?= ($_SESSION['priority'] ?? 'Medium') === 'Medium' ? 'selected' : '' ?>>Medium</option>
                <option value="High" <?= ($_SESSION['priority'] ?? 'Medium') === 'High' ? 'selected' : '' ?>>High</option>
                <option value="Critical" <?= ($_SESSION['priority'] ?? 'Medium') === 'Critical' ? 'selected' : '' ?>>Critical</option>
            </select>
        </label>
        <label>
            Description (Optional)
            <textarea name="description" rows="4" placeholder="Provide any helpful context..."><?= e($_SESSION['description'] ?? '') ?></textarea>
        </label>
        <div class="wizard-actions">
            <a href="report_step1.php" class="btn btn-secondary">Back</a>
            <button type="submit" class="btn btn-primary">Continue</button>
        </div>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
