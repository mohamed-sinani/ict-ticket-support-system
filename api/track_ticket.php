<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$trackingCode = trim($_POST['tracking_code'] ?? '');
if ($trackingCode === '') {
    echo json_encode(['success' => false, 'message' => 'Tracking code is required.']);
    exit;
}

$conn = db();

$ticketSql = 'SELECT t.id, t.tracking_code, t.status, t.created_at,
                     d.name AS department,
                     c.name AS category,
                     sc.name AS subcategory
              FROM tickets t
              LEFT JOIN departments d ON d.id = t.department_id
              LEFT JOIN categories c ON c.id = t.category_id
              LEFT JOIN subcategories sc ON sc.id = t.subcategory_id
              WHERE t.tracking_code = ? LIMIT 1';
$ticketStmt = $conn->prepare($ticketSql);
$ticketStmt->bind_param('s', $trackingCode);
$ticketStmt->execute();
$ticket = $ticketStmt->get_result()->fetch_assoc();

if (!$ticket) {
    echo json_encode(['success' => false, 'message' => 'No ticket found with this code.']);
    exit;
}

$attachmentSql = 'SELECT file_name, file_path, file_type, file_size,
                         DATE_FORMAT(created_at, "%Y-%m-%d %H:%i") AS created_at
                  FROM attachments
                  WHERE ticket_id = ?
                  ORDER BY created_at DESC, id DESC
                  LIMIT 1';
$attachmentStmt = $conn->prepare($attachmentSql);
$ticketId = (int) $ticket['id'];
$attachmentStmt->bind_param('i', $ticketId);
$attachmentStmt->execute();
$attachment = $attachmentStmt->get_result()->fetch_assoc() ?: null;

$timelineSql = 'SELECT comment_text, DATE_FORMAT(created_at, "%Y-%m-%d %H:%i") AS created_at
                FROM comments
                WHERE ticket_id = ? AND is_timeline = 1
                ORDER BY created_at ASC';
$timelineStmt = $conn->prepare($timelineSql);
$timelineStmt->bind_param('i', $ticketId);
$timelineStmt->execute();
$timeline = $timelineStmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'ticket' => $ticket,
    'attachment' => $attachment,
    'timeline' => $timeline,
]);
