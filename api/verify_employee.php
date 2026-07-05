<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$employeeNumber = trim($_POST['employee_number'] ?? '');
if ($employeeNumber === '') {
    echo json_encode(['success' => false, 'message' => 'Employee number is required.']);
    exit;
}

$conn = db();
$sql = 'SELECT u.id, u.full_name, u.email, u.employee_number, u.job_title, d.id AS department_id, d.name AS department_name
        FROM users u
        LEFT JOIN departments d ON d.id = u.department_id
        WHERE u.employee_number = ? AND u.role = "employee" LIMIT 1';
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $employeeNumber);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    echo json_encode(['success' => false, 'message' => 'Employee record not found.']);
    exit;
}

echo json_encode(['success' => true, 'employee' => $employee]);
