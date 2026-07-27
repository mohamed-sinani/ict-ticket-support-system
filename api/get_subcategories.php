<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

if (!verify_csrf()) {
    echo json_encode(['success' => false, 'subcategories' => []]);
    exit;
}

$categoryId = (int) ($_POST['category_id'] ?? 0);
if ($categoryId <= 0) {
    echo json_encode(['success' => false, 'subcategories' => []]);
    exit;
}

$conn = db();
$sql = 'SELECT id, name FROM subcategories WHERE category_id = ? ORDER BY name ASC';
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $categoryId);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode([
    'success' => true,
    'subcategories' => $result->fetch_all(MYSQLI_ASSOC),
]);
