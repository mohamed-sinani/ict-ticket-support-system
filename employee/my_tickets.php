<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(['employee']);

$conn = db();
$user = currentUser();
$userId = (int) $user['id'];

$sql = "SELECT t.tracking_code, t.status, t.created_at, t.updated_at, t.priority,
               d.name AS department_name, c.name AS category_name, sc.name AS subcategory_name,
               t.description
        FROM tickets t
        LEFT JOIN departments d ON d.id = t.department_id
        LEFT JOIN categories c ON c.id = t.category_id
        LEFT JOIN subcategories sc ON sc.id = t.subcategory_id
        WHERE t.employee_id = ?
        ORDER BY t.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Tickets | ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>
<h2 data-i18n="subnav_my_tickets">My Tickets</h2>
<section class="panel-card">
    <h3>All Issues</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tracking Code</th>
                    <th>Issue</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Created</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tickets): ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?= e($ticket['tracking_code']) ?></td>
                            <td>
                                <strong><?= e((string) $ticket['category_name']) ?> - <?= e((string) $ticket['subcategory_name']) ?></strong><br>
                                <span class="small-text"><?= e((string) $ticket['department_name']) ?></span>
                            </td>
                            <td><?= e($ticket['status']) ?></td>
                            <td><?= e($ticket['priority']) ?></td>
                            <td><?= e((string) $ticket['created_at']) ?></td>
                            <td><?= e((string) $ticket['updated_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No issues found yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
