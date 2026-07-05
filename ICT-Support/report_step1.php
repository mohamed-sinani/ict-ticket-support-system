<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/header.php';

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeNumber = trim($_POST['employee_number'] ?? '');
    if ($employeeNumber === '') {
        setFlash('Please enter your employee number.', 'error');
        redirect('report_step1.php');
    }

    $stmt = $conn->prepare("SELECT id, full_name, email, department_id, job_title FROM users WHERE employee_number = ? AND role = 'employee' LIMIT 1");
    $stmt->bind_param('s', $employeeNumber);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();

    if (!$employee) {
        setFlash('Employee number not found. Please check and try again.', 'error');
        redirect('report_step1.php');
    }

    $_SESSION['employee_number'] = $employeeNumber;
    $_SESSION['employee_id'] = (int) $employee['id'];
    $_SESSION['employee_name'] = $employee['full_name'];
    $_SESSION['employee_email'] = $employee['email'];
    $_SESSION['employee_job_title'] = $employee['job_title'];
    $_SESSION['employee_department_id'] = (int) $employee['department_id'];

    redirect('report_step2.php');
}

if (!empty($_SESSION['employee_number'])) {
    redirect('report_step2.php');
}
?>
<section class="wizard-wrap">
    <h2>Report ICT Issue - Step 1: Verify Employee</h2>
    <form method="POST" action="report_step1.php" class="form-grid">
        <label>
            Employee Number / Badge ID
            <input type="text" name="employee_number" required />
        </label>
        <button type="submit" class="btn btn-primary">Continue</button>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
