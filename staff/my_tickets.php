<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['ict']);

$conn = db();
$user = currentUser();
$userId = (int) $user['id'];

function resolveTicketPhotoUpload(array $file): array
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { setFlash('Invalid security token. Please try again.', 'error'); redirect('my_tickets.php'); }
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $resolutionNote = trim($_POST['resolution_note'] ?? '');
    $resolutionPhoto = $_FILES['resolution_photo'] ?? null;

    if ($ticketId > 0 && in_array($status, TICKET_STATUSES, true)) {
        $requiresPhoto = in_array($status, [STATUS_RESOLVED, STATUS_CLOSED], true);
        $photoRecord = null;

        if ($requiresPhoto) {
            [$photoOk, $photoResult] = resolveTicketPhotoUpload($resolutionPhoto ?? []);
            if (!$photoOk) {
                setFlash((string) $photoResult, 'error');
                redirect('my_tickets.php');
            }

            $photoRecord = $photoResult;
        }

        $conn->begin_transaction();

        try {
            $resolvedAt = null;
            if ($requiresPhoto) {
                $resolvedAt = date('Y-m-d H:i:s');
                $stmt = $conn->prepare('UPDATE tickets SET status = ?, resolution_note = ?, resolved_at = ? WHERE id = ? AND assigned_to = ?');
                $stmt->bind_param('sssii', $status, $resolutionNote, $resolvedAt, $ticketId, $userId);
            } else {
                $stmt = $conn->prepare('UPDATE tickets SET status = ?, resolution_note = ? WHERE id = ? AND assigned_to = ?');
                $stmt->bind_param('ssii', $status, $resolutionNote, $ticketId, $userId);
            }
            $stmt->execute();

            if ($photoRecord) {
                $attachStmt = $conn->prepare('INSERT INTO attachments (ticket_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)');
                $attachStmt->bind_param('isssi', $ticketId, $photoRecord['file_name'], $photoRecord['file_path'], $photoRecord['file_type'], $photoRecord['file_size']);
                $attachStmt->execute();
            }

            $timelineMessage = 'Status updated to ' . $status . ' by ICT staff.';
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
                        'department_name' => $notifyRow['department_name'] ?? null,
                        'category_name' => $notifyRow['category_name'] ?? null,
                        'subcategory_name' => $notifyRow['subcategory_name'] ?? null,
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

            setFlash($requiresPhoto ? 'Ticket marked as resolved and photo uploaded successfully.' : 'Ticket updated successfully.');
        } catch (Throwable $exception) {
            $conn->rollback();
            setFlash('Unable to update ticket.', 'error');
        }
    } else {
        setFlash('Unable to update ticket.', 'error');
    }

    redirect('my_tickets.php');
}

$sql = "SELECT t.id, t.tracking_code, t.status, t.priority, t.description, t.resolution_note, t.created_at,
               d.name AS department_name, c.name AS category_name, sc.name AS subcategory_name,
               emp.full_name AS employee_name, emp.email AS employee_email,
               att.file_path AS evidence_path,
               att.file_name AS evidence_name
        FROM tickets t
        LEFT JOIN departments d ON d.id = t.department_id
        LEFT JOIN categories c ON c.id = t.category_id
        LEFT JOIN subcategories sc ON sc.id = t.subcategory_id
        LEFT JOIN users emp ON emp.id = t.employee_id
        LEFT JOIN attachments att ON att.id = (
            SELECT a2.id
            FROM attachments a2
            WHERE a2.ticket_id = t.id
            ORDER BY a2.created_at DESC, a2.id DESC
            LIMIT 1
        )
        WHERE t.assigned_to = ?
        ORDER BY t.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['debug']) && $_GET['debug']) {
    echo '<section class="panel-card"><pre>' . e(print_r(['userId' => $userId, 'tickets_fetched' => $tickets], true)) . '</pre></section>';
}

$pageTitle = 'Assigned Tickets | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<h2 data-i18n="staff_assigned_tickets">Assigned Tickets</h2>

<?php if (count($tickets) === 0): ?>
    <section class="panel-card">
        <p><em>No assigned tickets found.</em></p>
    </section>
<?php endif; ?>

<section class="panel-card">
    <div class="table-wrap">
        <table class="admin-tickets-table">
            <thead>
                <tr>
                    <th data-i18n="common_tracking_code">Tracking Code</th>
                    <th data-i18n="common_employee">Employee</th>
                    <th data-i18n="common_department">Department</th>
                    <th data-i18n="common_issue">Issue</th>
                    <th data-i18n="common_status">Status</th>
                    <th>Evidence</th>
                    <th data-i18n="common_description">Description</th>
                    <th data-i18n="staff_save_update">Update</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                    <?php $issueLabel = trim((string) ($t['category_name'] ?? '') . ' - ' . (string) ($t['subcategory_name'] ?? ''), ' -'); ?>
                    <tr>
                        <td><?= e((string) $t['tracking_code']) ?></td>
                        <td>
                            <div class="staff-ticket-meta">
                                <strong><?= e((string) $t['employee_name']) ?></strong>
                                <span><?= e((string) $t['employee_email']) ?></span>
                            </div>
                        </td>
                        <td><?= e((string) $t['department_name']) ?></td>
                        <td><?= e($issueLabel) ?></td>
                        <td><?= e((string) $t['status']) ?></td>
                        <td>
                            <?php if (!empty($t['evidence_path'])): ?>
                                <button
                                    type="button"
                                    class="btn btn-secondary evidence-view-btn"
                                    data-evidence-url="<?= e(absoluteUrl((string) $t['evidence_path'])) ?>"
                                    data-evidence-name="<?= e((string) $t['evidence_name']) ?>"
                                    data-ticket-code="<?= e((string) $t['tracking_code']) ?>"
                                    data-ticket-employee="<?= e((string) $t['employee_name']) ?>"
                                    data-ticket-issue="<?= e($issueLabel) ?>"
                                    data-ticket-status="<?= e((string) $t['status']) ?>"
                                >View Photo</button>
                            <?php else: ?>
                                <span class="evidence-empty">No photo</span>
                            <?php endif; ?>
                        </td>
                        <td class="staff-ticket-description"><?= e((string) $t['description']) ?></td>
                        <td>
                            <details class="staff-ticket-details">
                                <summary class="btn btn-secondary">Update</summary>
                                <div class="staff-ticket-details-body">
                                    <form method="POST" class="staff-ticket-update-form" enctype="multipart/form-data">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="ticket_id" value="<?= (int) $t['id'] ?>">
                                        <label><span data-i18n="common_status">Status</span>
                                            <select name="status" required>
                                                <?php foreach (TICKET_STATUSES as $status): ?>
                                                    <option value="<?= e($status) ?>" <?= $t['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label><span data-i18n="staff_comment_update">Comment / Update</span>
                                            <textarea name="comment" rows="3" placeholder="Visible in employee tracking timeline" data-i18n-placeholder="staff_comment_placeholder"></textarea>
                                        </label>
                                        <label><span data-i18n="staff_resolution_notes">Resolution Notes</span>
                                            <textarea name="resolution_note" rows="3" placeholder="Internal/closure notes" data-i18n-placeholder="staff_resolution_placeholder"><?= e((string) $t['resolution_note']) ?></textarea>
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
                                </div>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="evidenceDrawer" class="evidence-drawer hidden" aria-hidden="true">
    <div class="evidence-drawer-backdrop" data-evidence-close></div>
    <aside class="evidence-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="evidenceDrawerTitle">
        <div class="evidence-drawer-header">
            <div>
                <div class="evidence-drawer-kicker">Ticket Evidence</div>
                <h3 id="evidenceDrawerTitle">Preview Photo</h3>
            </div>
            <button type="button" class="evidence-drawer-close" data-evidence-close aria-label="Close preview">&times;</button>
        </div>
        <div class="evidence-drawer-meta">
            <p><strong>Tracking Code:</strong> <span id="evidenceTicketCode">-</span></p>
            <p><strong>Employee:</strong> <span id="evidenceTicketEmployee">-</span></p>
            <p><strong>Issue:</strong> <span id="evidenceTicketIssue">-</span></p>
            <p><strong>Status:</strong> <span id="evidenceTicketStatus">-</span></p>
        </div>
        <div class="evidence-drawer-preview">
            <img id="evidenceTicketImage" alt="Ticket evidence preview" src="">
        </div>
        <p class="evidence-drawer-name" id="evidenceTicketName"></p>
    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const drawer = document.getElementById('evidenceDrawer');
    const image = document.getElementById('evidenceTicketImage');
    const nameBox = document.getElementById('evidenceTicketName');
    const codeBox = document.getElementById('evidenceTicketCode');
    const employeeBox = document.getElementById('evidenceTicketEmployee');
    const issueBox = document.getElementById('evidenceTicketIssue');
    const statusBox = document.getElementById('evidenceTicketStatus');

    function closeDrawer() {
        if (!drawer) return;
        drawer.classList.add('hidden');
        drawer.setAttribute('aria-hidden', 'true');
        image.src = '';
    }

    document.querySelectorAll('.evidence-view-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            image.src = button.dataset.evidenceUrl || '';
            nameBox.textContent = button.dataset.evidenceName || '';
            codeBox.textContent = button.dataset.ticketCode || '-';
            employeeBox.textContent = button.dataset.ticketEmployee || '-';
            issueBox.textContent = button.dataset.ticketIssue || '-';
            statusBox.textContent = button.dataset.ticketStatus || '-';
            drawer.classList.remove('hidden');
            drawer.setAttribute('aria-hidden', 'false');
        });
    });

    drawer.querySelectorAll('[data-evidence-close]').forEach(function (el) {
        el.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });
});
</script>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
