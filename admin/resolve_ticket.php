<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['admin', 'ict']);

$conn = db();
$user = currentUser();
$userId = (int) $user['id'];
$ticketId = (int) ($_GET['id'] ?? 0);

if ($ticketId < 1) {
    setFlash('Invalid ticket.', 'error');
    redirect('tickets.php');
}

function resolvePhotoUpload(array $file): array
{
    if (empty($file['name']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [false, 'No file uploaded.'];
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Photo upload failed.'];
    }

    $tmpPath = $file['tmp_name'] ?? '';
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return [false, 'Invalid upload source.'];
    }

    $mime = mime_content_type($tmpPath) ?: '';
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
        return [false, 'Please upload a valid image file.'];
    }

    $uploadDir = __DIR__ . '/../uploads/resolutions';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $extension = $extensionMap[$mime] ?? 'jpg';
    $safeName = 'resolution_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $uploadDir . '/' . $safeName;

    if (!move_uploaded_file($tmpPath, $destination)) {
        return [false, 'Unable to save uploaded photo.'];
    }

    return [true, [
        'file_name' => $file['name'],
        'file_path' => 'uploads/resolutions/' . $safeName,
        'file_type' => $mime,
        'file_size' => (int) ($file['size'] ?? 0),
    ]];
}

// Fetch ticket
$ticketSql = "SELECT t.*, d.name AS department_name, c.name AS category_name, sc.name AS subcategory_name,
                     emp.full_name AS employee_name, emp.email AS employee_email,
                     ict.full_name AS ict_name
              FROM tickets t
              LEFT JOIN departments d ON d.id = t.department_id
              LEFT JOIN categories c ON c.id = t.category_id
              LEFT JOIN subcategories sc ON sc.id = t.subcategory_id
              LEFT JOIN users emp ON emp.id = t.employee_id
              LEFT JOIN users ict ON ict.id = t.assigned_to
              WHERE t.id = ?";
$ticketStmt = $conn->prepare($ticketSql);
$ticketStmt->bind_param('i', $ticketId);
$ticketStmt->execute();
$ticket = $ticketStmt->get_result()->fetch_assoc();

if (!$ticket) {
    setFlash('Ticket not found.', 'error');
    redirect('tickets.php');
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { setFlash('Invalid security token. Please try again.', 'error'); redirect('tickets.php'); }
    $status = trim($_POST['status'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $resolutionNote = trim($_POST['resolution_note'] ?? '');
    $resolutionPhoto = $_FILES['resolution_photo'] ?? null;

    if ($status !== '' && in_array($status, TICKET_STATUSES, true)) {
        $requiresPhoto = in_array($status, [STATUS_RESOLVED, STATUS_CLOSED], true);
        $photoRecord = null;

        if ($requiresPhoto) {
            [$photoOk, $photoResult] = resolvePhotoUpload($resolutionPhoto ?? []);
            if (!$photoOk) {
                setFlash((string) $photoResult, 'error');
                redirect('resolve_ticket.php?id=' . $ticketId);
            }
            $photoRecord = $photoResult;
        }

        $conn->begin_transaction();

        try {
            $resolvedAt = null;
            if ($requiresPhoto) {
                $resolvedAt = date('Y-m-d H:i:s');
                $stmt = $conn->prepare('UPDATE tickets SET status = ?, resolution_note = ?, resolved_at = ? WHERE id = ?');
                $stmt->bind_param('sssi', $status, $resolutionNote, $resolvedAt, $ticketId);
            } else {
                $stmt = $conn->prepare('UPDATE tickets SET status = ?, resolution_note = ? WHERE id = ?');
                $stmt->bind_param('ssi', $status, $resolutionNote, $ticketId);
            }
            $stmt->execute();

            if ($photoRecord) {
                $attachStmt = $conn->prepare('INSERT INTO attachments (ticket_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)');
                $attachStmt->bind_param('isssi', $ticketId, $photoRecord['file_name'], $photoRecord['file_path'], $photoRecord['file_type'], $photoRecord['file_size']);
                $attachStmt->execute();
            }

            $timelineMessage = 'Status updated to ' . $status . ' by admin.';
            if ($photoRecord) {
                $timelineMessage .= ' Resolution photo uploaded.';
            }
            addTicketTimeline($ticketId, $userId, $timelineMessage);

            if ($comment !== '') {
                $commentStmt = $conn->prepare('INSERT INTO comments (ticket_id, user_id, comment_text, is_timeline) VALUES (?, ?, ?, 0)');
                $commentStmt->bind_param('iis', $ticketId, $userId, $comment);
                $commentStmt->execute();
            }

            $notifySql = 'SELECT t.tracking_code, t.status, t.resolution_note,
                                 d.name AS department_name, c.name AS category_name, sc.name AS subcategory_name,
                                 u.full_name, u.email
                          FROM tickets t
                          JOIN users u ON u.id = t.employee_id
                          LEFT JOIN departments d ON d.id = t.department_id
                          LEFT JOIN categories c ON c.id = t.category_id
                          LEFT JOIN subcategories sc ON sc.id = t.subcategory_id
                          WHERE t.id = ? LIMIT 1';
            $notifyStmt = $conn->prepare($notifySql);
            $notifyStmt->bind_param('i', $ticketId);
            $notifyStmt->execute();
            $notifyRow = $notifyStmt->get_result()->fetch_assoc();

            $conn->commit();

            if ($notifyRow) {
                if (in_array($status, [STATUS_RESOLVED, STATUS_CLOSED], true)) {
                    $subject = 'ICT Ticket Resolved - ' . $notifyRow['tracking_code'];
                    $message = "Hello {$notifyRow['full_name']},\n\n";
                    $message .= "Your ICT ticket {$notifyRow['tracking_code']} has been marked as {$status}.\n";
                    if ($comment !== '') {
                        $message .= "Update note: {$comment}\n";
                    }
                    if ($resolutionNote !== '') {
                        $message .= "Resolution note: {$resolutionNote}\n";
                    }
                    if ($photoRecord) {
                        $message .= "A resolution photo has been attached.\n";
                    }
                    $message .= "\nYou can review the full timeline on the tracking page using your tracking code.";
                    $htmlMessage = buildTicketResolvedEmail(array_merge($notifyRow, [
                        'status' => $status,
                        'resolution_note' => $resolutionNote,
                    ]));
                } else {
                    $subject = 'ICT Ticket Update - ' . $notifyRow['tracking_code'];
                    $message = "Hello {$notifyRow['full_name']},\n\n";
                    $message .= "Your ticket {$notifyRow['tracking_code']} is now: {$status}.\n";
                    if ($comment !== '') {
                        $message .= "Update note: {$comment}\n";
                    }
                    if ($photoRecord) {
                        $message .= "A resolution photo has been attached.\n";
                    }
                    $message .= "\nPlease use your tracking code on the portal for full timeline details.";
                    $htmlMessage = null;
                }

                $sent = sendNotificationEmail($notifyRow['email'], $subject, $message, $htmlMessage);
                $notifInsert = $conn->prepare('INSERT INTO notifications (ticket_id, recipient_email, subject, message, is_sent) VALUES (?, ?, ?, ?, ?)');
                $isSent = $sent ? 1 : 0;
                $notifInsert->bind_param('isssi', $ticketId, $notifyRow['email'], $subject, $message, $isSent);
                $notifInsert->execute();
            }

            setFlash($requiresPhoto ? 'Ticket resolved successfully with photo.' : 'Ticket updated successfully.');
        } catch (Throwable $exception) {
            $conn->rollback();
            setFlash('Unable to update ticket.', 'error');
        }
    } else {
        setFlash('Unable to update ticket.', 'error');
    }

    redirect('tickets.php');
}

// Fetch evidence attachments
$evidenceSql = "SELECT file_name, file_path, file_type, created_at FROM attachments WHERE ticket_id = ? ORDER BY created_at ASC";
$evidenceStmt = $conn->prepare($evidenceSql);
$evidenceStmt->bind_param('i', $ticketId);
$evidenceStmt->execute();
$attachments = $evidenceStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch timeline
$timelineSql = "SELECT c.comment_text AS action, c.created_at, u.full_name
                FROM comments c
                LEFT JOIN users u ON u.id = c.user_id
                WHERE c.ticket_id = ? AND c.is_timeline = 1
                ORDER BY c.created_at ASC";
$timelineStmt = $conn->prepare($timelineSql);
$timelineStmt->bind_param('i', $ticketId);
$timelineStmt->execute();
$timeline = $timelineStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch comments
$commentSql = "SELECT c.comment_text, c.created_at, u.full_name
               FROM comments c
               LEFT JOIN users u ON u.id = c.user_id
               WHERE c.ticket_id = ? AND c.is_timeline = 0
               ORDER BY c.created_at ASC";
$commentStmt = $conn->prepare($commentSql);
$commentStmt->bind_param('i', $ticketId);
$commentStmt->execute();
$comments = $commentStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Resolve Ticket - ' . $ticket['tracking_code'] . ' | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<h2>Resolve Ticket: <?= e($ticket['tracking_code']) ?></h2>

<a href="<?= e(app_base_path() . '/admin/tickets.php') ?>" class="btn btn-secondary" style="margin-bottom:1rem;">&larr; Back to Tickets</a>

<section class="panel-card">
    <div class="track-table-block">
        <table class="track-table track-summary-table">
            <caption>Ticket Details</caption>
            <tbody>
                <tr><th>Tracking Code</th><td><?= e($ticket['tracking_code']) ?></td></tr>
                <tr><th>Employee</th><td><?= e($ticket['employee_name'] ?? '') ?> (<?= e($ticket['employee_email'] ?? '') ?>)</td></tr>
                <tr><th>Department</th><td><?= e($ticket['department_name'] ?? 'Not specified') ?></td></tr>
                <tr><th>Issue</th><td><?= e((string) $ticket['category_name']) ?> - <?= e((string) $ticket['subcategory_name']) ?></td></tr>
                <tr><th>Description</th><td><?= e((string) $ticket['description']) ?></td></tr>
                <tr><th>Status</th><td><span class="status-badge status-<?= e(strtolower(str_replace(' ', '-', $ticket['status']))) ?>"><?= e($ticket['status']) ?></span></td></tr>
                <tr><th>Assigned ICT</th><td><?= e($ticket['ict_name'] ?? 'Unassigned') ?></td></tr>
                <tr><th>Created</th><td><?= e(date('M j, Y g:i A', strtotime($ticket['created_at']))) ?></td></tr>
            </tbody>
        </table>
    </div>
</section>

<?php if (!empty($attachments)): ?>
<section class="panel-card">
    <h3>Evidence Photos</h3>
    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-top:0.5rem;">
        <?php foreach ($attachments as $att): ?>
            <a href="<?= e(absoluteUrl($att['file_path'])) ?>" target="_blank" rel="noopener">
                <img src="<?= e(absoluteUrl($att['file_path'])) ?>" alt="<?= e($att['file_name']) ?>" style="width:120px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--border);">
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="panel-card">
    <h3>Update Status</h3>
    <form method="POST" enctype="multipart/form-data" class="staff-ticket-update-form" style="max-width:520px;">
        <?= csrf_field() ?>
        <label><span data-i18n="common_status">Status</span>
            <select name="status" required>
                <?php foreach (TICKET_STATUSES as $status): ?>
                    <option value="<?= e($status) ?>" <?= $ticket['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span data-i18n="staff_comment_update">Comment / Update</span>
            <textarea name="comment" rows="3" placeholder="Visible in employee tracking timeline" data-i18n-placeholder="staff_comment_placeholder"></textarea>
        </label>
        <label><span data-i18n="staff_resolution_notes">Resolution Notes</span>
            <textarea name="resolution_note" rows="3" placeholder="Internal/closure notes" data-i18n-placeholder="staff_resolution_placeholder"><?= e((string) $ticket['resolution_note']) ?></textarea>
        </label>
        <div><span>Resolution Photo</span>
            <label class="file-drop-zone">
                <input type="file" class="file-drop-input" name="resolution_photo" accept="image/*" capture="environment">
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
        </div>
        <button class="btn btn-primary" type="submit" data-i18n="staff_save_update">Save Update</button>
    </form>
</section>

<?php if (!empty($comments)): ?>
<section class="panel-card">
    <h3>Comments</h3>
    <?php foreach ($comments as $c): ?>
        <div style="padding:0.65rem 0;border-bottom:1px solid var(--border);">
            <strong><?= e($c['full_name'] ?? 'System') ?></strong>
            <span style="color:var(--text-muted);font-size:0.85rem;margin-left:0.5rem;"><?= e(date('M j, Y g:i A', strtotime($c['created_at']))) ?></span>
            <p style="margin-top:0.25rem;"><?= e($c['comment_text']) ?></p>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<section class="panel-card">
    <h3>Timeline</h3>
    <?php if (empty($timeline)): ?>
        <p class="small-text">No timeline entries yet.</p>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:0.5rem;">
            <?php foreach ($timeline as $entry): ?>
                <div style="display:flex;gap:0.75rem;align-items:flex-start;padding:0.45rem 0;border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted);font-size:0.85rem;white-space:nowrap;min-width:140px;"><?= e(date('M j, Y g:i A', strtotime($entry['created_at']))) ?></span>
                    <span><strong><?= e($entry['full_name'] ?? 'System') ?></strong>: <?= e($entry['action']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
