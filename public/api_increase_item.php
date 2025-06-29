<?php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson', 'Garson (Yetkili)']);
$role = currentUserRole();

$table_id = (int)($_POST['table_id'] ?? 0);
$item_id  = (int)($_POST['item_id'] ?? 0);

if (!$table_id || !$item_id) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid']);
    exit;
}

if ($table_id == 1 && !in_array($role, ['Admin','Garson (Yetkili)'])) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

try {
    $pdo->beginTransaction();
    $chk = $pdo->prepare("SELECT oi.order_id FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.id = ? AND o.table_id = ?");
    $chk->execute([$item_id, $table_id]);
    $order_id = $chk->fetchColumn();
    if (!$order_id) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        exit;
    }

    $pdo->prepare("UPDATE order_items SET quantity = quantity + 1 WHERE id = ?")
        ->execute([$item_id]);

    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'db']);
}
