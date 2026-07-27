<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/header.php';

if (empty($_SESSION['employee_id']) || empty($_SESSION['department_id']) || empty($_SESSION['category_id'])) {
    setFlash('Please complete the wizard from the beginning.', 'error');
    redirect('report_step1.php');
}

$conn = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('report_step1.php');
}

$employeeId = (int) $_SESSION['employee_id'];
$departmentId = (int) $_SESSION['department_id'];
$categoryId = (int) $_SESSION['category_id'];
$subcategoryName = trim($_SESSION['subcategory_name'] ?? '');
$description = trim($_SESSION['description'] ?? '');
$evidence = $_FILES['evidence'] ?? null;

if (!$evidence || empty($evidence['name']) || (int) ($evidence['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    setFlash('Please attach an evidence photo before submitting.', 'error');
    redirect('report_step3.php');
}

if ((int) ($evidence['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    setFlash('Photo upload failed. Please choose the image again.', 'error');
    redirect('report_step3.php');
}

$fileSize = (int) ($evidence['size'] ?? 0);
if ($fileSize <= 0 || $fileSize > MAX_UPLOAD_SIZE) {
    setFlash('Photo must be a valid image up to 5MB.', 'error');
    redirect('report_step3.php');
}

$tmpName = (string) ($evidence['tmp_name'] ?? '');
if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    setFlash('Invalid uploaded photo. Please try again.', 'error');
    redirect('report_step3.php');
}

$mimeType = mime_content_type($tmpName) ?: '';
$extensionMap = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

if (!isset($extensionMap[$mimeType])) {
    setFlash('Please upload a JPG, PNG, WEBP, or GIF image.', 'error');
    redirect('report_step3.php');
}

$subcategoryId = 0;
if ($subcategoryName !== '') {
    $subStmt = $conn->prepare('SELECT id FROM subcategories WHERE name = ? AND category_id = ? LIMIT 1');
    $subStmt->bind_param('si', $subcategoryName, $categoryId);
    $subStmt->execute();
    $existing = $subStmt->get_result()->fetch_assoc();
    if ($existing) {
        $subcategoryId = (int) $existing['id'];
    }
}

$trackingCode = randomTrackingCode();
$priority = $_SESSION['priority'] ?? 'Medium';
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
    setFlash($e->getMessage(), 'error');
    redirect('report_step3.php');
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
    'employee_name' => $_SESSION['employee_name'] ?? 'Employee',
];

$subject = 'ICT Ticket Created - ' . $trackingCode;
$message = "Hello {$ticketDetails['employee_name']},\n\nYour ICT issue has been submitted successfully.\nTracking code: {$trackingCode}\nCurrent status: Submitted\n\nUse the tracking code on the support portal to view updates.\n";
$htmlMessage = buildTicketCreatedEmail($ticketDetails);
$emailSent = sendNotificationEmail($_SESSION['employee_email'], $subject, $message, $htmlMessage);

$notifSql = 'INSERT INTO notifications (ticket_id, recipient_email, subject, message, is_sent) VALUES (?, ?, ?, ?, ?)';
$notifStmt = $conn->prepare($notifSql);
$isSent = $emailSent ? 1 : 0;
$notifStmt->bind_param('isssi', $ticketId, $_SESSION['employee_email'], $subject, $message, $isSent);
$notifStmt->execute();

$adminSql = "SELECT id, full_name, email FROM users WHERE role = 'admin' ORDER BY full_name ASC";
$adminResult = $conn->query($adminSql);
$adminUsers = $adminResult ? $adminResult->fetch_all(MYSQLI_ASSOC) : [];
$adminSubject = 'New ICT Ticket Awaiting Assignment - ' . $trackingCode;
$adminMessage = "Hello Admin,\n\nA new ICT ticket has been submitted and is waiting for assignment.\nTracking code: {$trackingCode}\nEmployee: {$ticketDetails['employee_name']}\nDepartment: " . ($ticketDetails['department_name'] ?? 'Not specified') . "\nIssue: " . trim((string) ($ticketDetails['category_name'] ?? '') . ' - ' . (string) ($ticketDetails['subcategory_name'] ?? ''), ' -') . "\nPriority: {$priority}\nStatus: {$status}\n\nOpen the admin ticket board to assign it to ICT staff.\n";
$adminHtmlMessage = buildTicketAdminAlertEmail(array_merge($ticketDetails, [
    'priority' => $priority,
]));

foreach ($adminUsers as $adminUser) {
    $adminEmailSent = sendNotificationEmail($adminUser['email'], $adminSubject, $adminMessage, $adminHtmlMessage);
    $adminNotifStmt = $conn->prepare($notifSql);
    $adminIsSent = $adminEmailSent ? 1 : 0;
    $adminNotifStmt->bind_param('isssi', $ticketId, $adminUser['email'], $adminSubject, $adminMessage, $adminIsSent);
    $adminNotifStmt->execute();
}

unset($_SESSION['employee_number'], $_SESSION['employee_id'], $_SESSION['employee_name'], $_SESSION['employee_email'], $_SESSION['employee_job_title'], $_SESSION['employee_department_id'], $_SESSION['department_id'], $_SESSION['category_id'], $_SESSION['subcategory_name'], $_SESSION['description'], $_SESSION['priority']);

$pageTitle = 'Ticket Submitted | ' . APP_NAME;
?>
<section class="wizard-wrap" style="text-align:center;">
    <h2>Ticket Submitted Successfully</h2>
    <div style="background:#dcfce7;border:1px solid #86efac;border-radius:10px;padding:24px;margin:24px 0;text-align:center;">
        <div style="font-size:14px;font-weight:700;color:#166534;text-transform:uppercase;letter-spacing:.04em;">Your Tracking Code</div>
        <div style="font-size:32px;font-weight:800;color:#0f2f61;letter-spacing:.04em;margin-top:8px;"><?= e($trackingCode) ?></div>
    </div>
    <p>Keep this tracking code to check your ticket status anytime.</p>
    <p>A confirmation email has been sent to <strong><?= e($_SESSION['employee_email'] ?? '') ?></strong>.</p>
    <br>
    <a href="<?= e(app_base_path() . '/track.php') ?>" class="btn btn-primary">Track Your Ticket</a>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
