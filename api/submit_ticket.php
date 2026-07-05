<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$employeeId = (int) ($_POST['employee_id'] ?? 0);
$departmentId = (int) ($_POST['department_id'] ?? 0);
$categoryId = (int) ($_POST['category_id'] ?? 0);
$subcategoryId = (int) ($_POST['subcategory_id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$evidence = $_FILES['evidence'] ?? null;

if ($employeeId <= 0 || $departmentId <= 0 || $categoryId <= 0 || $subcategoryId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required ticket fields.']);
    exit;
}

if (!$evidence || empty($evidence['name']) || (int) ($evidence['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => 'Please attach an evidence photo before submitting.']);
    exit;
}

if ((int) ($evidence['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Photo upload failed. Please choose the image again.']);
    exit;
}

$fileSize = (int) ($evidence['size'] ?? 0);
if ($fileSize <= 0 || $fileSize > MAX_UPLOAD_SIZE) {
    echo json_encode(['success' => false, 'message' => 'Photo must be a valid image up to 5MB.']);
    exit;
}

$tmpName = (string) ($evidence['tmp_name'] ?? '');
if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    echo json_encode(['success' => false, 'message' => 'Invalid uploaded photo. Please try again.']);
    exit;
}

$mimeType = mime_content_type($tmpName) ?: '';
$extensionMap = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

if (!isset($extensionMap[$mimeType])) {
    echo json_encode(['success' => false, 'message' => 'Please upload a JPG, PNG, WEBP, or GIF image.']);
    exit;
}

$conn = db();

$empStmt = $conn->prepare('SELECT id, full_name, email FROM users WHERE id = ? AND role = "employee" LIMIT 1');
$empStmt->bind_param('i', $employeeId);
$empStmt->execute();
$employee = $empStmt->get_result()->fetch_assoc();

if (!$employee) {
    echo json_encode(['success' => false, 'message' => 'Employee is invalid.']);
    exit;
}

$trackingCode = randomTrackingCode();
$priority = $_POST['priority'] ?? 'Medium';
$allowedPriorities = ['Low', 'Medium', 'High', 'Critical'];
if (!in_array($priority, $allowedPriorities, true)) {
    $priority = 'Medium';
}
$status = STATUS_SUBMITTED;

$conn->begin_transaction();

try {
    $ticketSql = 'INSERT INTO tickets (tracking_code, employee_id, department_id, category_id, subcategory_id, description, priority, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
    $ticketStmt = $conn->prepare($ticketSql);
    $ticketStmt->bind_param('siiiisss', $trackingCode, $employeeId, $departmentId, $categoryId, $subcategoryId, $description, $priority, $status);
    if (!$ticketStmt->execute()) {
        throw new RuntimeException('Could not create ticket.');
    }
    $ticketId = (int) $conn->insert_id;

    if ($ticketId <= 0) {
        throw new RuntimeException('Could not create ticket.');
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0775, true)) {
        throw new RuntimeException('Upload folder is not available.');
    }

    if (!is_writable(UPLOAD_DIR)) {
        throw new RuntimeException('Upload folder is not writable.');
    }

    $safeExt = $extensionMap[$mimeType];
    $storedName = 'evidence_' . $ticketId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
    $targetPath = UPLOAD_DIR . '/' . $storedName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Unable to save uploaded photo.');
    }

    $attachmentSql = 'INSERT INTO attachments (ticket_id, file_name, file_path, file_type, file_size)
                      VALUES (?, ?, ?, ?, ?)';
    $attachmentStmt = $conn->prepare($attachmentSql);
    $relativePath = 'uploads/' . $storedName;
    $originalName = basename((string) $evidence['name']);
    $attachmentStmt->bind_param('isssi', $ticketId, $originalName, $relativePath, $mimeType, $fileSize);
    if (!$attachmentStmt->execute()) {
        throw new RuntimeException('Could not save photo details.');
    }

    addTicketTimeline($ticketId, null, 'Ticket submitted by employee with evidence photo and awaiting ICT assignment.');
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

$detailsSql = 'SELECT t.tracking_code, t.status, emp.full_name AS employee_name,
                      d.name AS department_name, c.name AS category_name, sc.name AS subcategory_name
               FROM tickets t
               JOIN users emp ON emp.id = t.employee_id
               LEFT JOIN departments d ON d.id = t.department_id
               LEFT JOIN categories c ON c.id = t.category_id
               LEFT JOIN subcategories sc ON sc.id = t.subcategory_id
               WHERE t.id = ? LIMIT 1';
$detailsStmt = $conn->prepare($detailsSql);
$detailsStmt->bind_param('i', $ticketId);
$detailsStmt->execute();
$ticketDetails = $detailsStmt->get_result()->fetch_assoc() ?: [
    'tracking_code' => $trackingCode,
    'status' => $status,
    'employee_name' => $employee['full_name'],
];

$subject = 'ICT Ticket Created - ' . $trackingCode;
$message = "Hello {$employee['full_name']},\n\nYour ICT issue has been submitted successfully.\nTracking code: {$trackingCode}\nCurrent status: Submitted\n\nUse the tracking code on the support portal to view updates.\n";
$htmlMessage = buildTicketCreatedEmail($ticketDetails);
$emailSent = sendNotificationEmail($employee['email'], $subject, $message, $htmlMessage);

$notifSql = 'INSERT INTO notifications (ticket_id, recipient_email, subject, message, is_sent)
             VALUES (?, ?, ?, ?, ?)';
$notifStmt = $conn->prepare($notifSql);
$isSent = $emailSent ? 1 : 0;
$notifStmt->bind_param('isssi', $ticketId, $employee['email'], $subject, $message, $isSent);
$notifStmt->execute();

$adminSql = "SELECT id, full_name, email FROM users WHERE role = 'admin' ORDER BY full_name ASC";
$adminResult = $conn->query($adminSql);
$adminUsers = $adminResult ? $adminResult->fetch_all(MYSQLI_ASSOC) : [];
$adminSubject = 'New ICT Ticket Awaiting Assignment - ' . $trackingCode;
$adminMessage = "Hello Admin,\n\nA new ICT ticket has been submitted and is waiting for assignment.\nTracking code: {$trackingCode}\nEmployee: {$employee['full_name']}\nDepartment: " . ($ticketDetails['department_name'] ?? 'Not specified') . "\nIssue: " . trim((string) ($ticketDetails['category_name'] ?? '') . ' - ' . (string) ($ticketDetails['subcategory_name'] ?? ''), ' -') . "\nPriority: {$priority}\nStatus: {$status}\n\nOpen the admin ticket board to assign it to ICT staff.\n";
$adminHtmlMessage = buildTicketAdminAlertEmail(array_merge($ticketDetails, [
    'priority' => $priority,
]));

if (empty($adminUsers)) {
    error_log('[ict-mail] No admin users found to notify for ticket ' . $trackingCode);
}

foreach ($adminUsers as $adminUser) {
    $adminEmailSent = sendNotificationEmail($adminUser['email'], $adminSubject, $adminMessage, $adminHtmlMessage);
    if (!$adminEmailSent) {
        error_log('[ict-mail] Failed to send admin notification to ' . $adminUser['email'] . ' for ticket ' . $trackingCode);
    }
    $adminNotifStmt = $conn->prepare($notifSql);
    $adminIsSent = $adminEmailSent ? 1 : 0;
    $adminNotifStmt->bind_param('isssi', $ticketId, $adminUser['email'], $adminSubject, $adminMessage, $adminIsSent);
    $adminNotifStmt->execute();
}

echo json_encode([
    'success' => true,
    'tracking_code' => $trackingCode,
    'email' => $employee['email'],
    'email_status' => $emailSent ? 'Sent' : 'Could not send (check mail server).',
]);
