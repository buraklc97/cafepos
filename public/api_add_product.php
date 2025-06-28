<?php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson', 'Garson (Yetkili)']);
$role = currentUserRole();

$table_id   = (int)($_POST['table_id'] ?? 0);
$product_id = (int)($_POST['product_id'] ?? 0);
$qty        = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

if (!$table_id || !$product_id) {
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

    $stmt = $pdo->prepare("SELECT id FROM orders WHERE table_id = ? AND status = 'open' LIMIT 1");
    $stmt->execute([$table_id]);
    $order_id = $stmt->fetchColumn();
    if (!$order_id) {
        $pdo->prepare("INSERT INTO orders (table_id) VALUES (?)")->execute([$table_id]);
        $order_id = $pdo->lastInsertId();
    }

    $chk = $pdo->prepare("SELECT id FROM order_items WHERE order_id = ? AND product_id = ?");
    $chk->execute([$order_id, $product_id]);
    if ($item = $chk->fetch()) {
        $pdo->prepare("UPDATE order_items SET quantity = quantity + ? WHERE id = ?")
            ->execute([$qty, $item['id']]);
    } else {
        $price = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $price->execute([$product_id]);
        $unit = $price->fetchColumn();
        $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)")
            ->execute([$order_id, $product_id, $qty, $unit]);
    }

    $pdo->prepare("UPDATE pos_tables SET status = 'occupied', opened_at = NOW() WHERE id = ? AND opened_at IS NULL")
        ->execute([$table_id]);

    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'db']);
}
