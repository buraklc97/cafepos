<?php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson', 'Garson (Yetkili)']);
$role = currentUserRole();

$table_id = (int)($_GET['table'] ?? 0);
if (!$table_id || ($table_id == 1 && !in_array($role, ['Admin','Garson (Yetkili)']))) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid table']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM orders WHERE table_id = ? AND status = 'open' LIMIT 1");
$stmt->execute([$table_id]);
$order_id = $stmt->fetchColumn();

$items = [];
if ($order_id) {
    $stmtItems = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ? ORDER BY id");
    $stmtItems->execute([$order_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode(['version' => sha1(json_encode($items))]);
