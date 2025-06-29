<?php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson', 'Garson (Yetkili)']);

$item_id = (int)($_POST['item_id'] ?? 0);

if (!$item_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz parametreler']);
    exit;
}

try {
    // Ürün kontrolü ve masa bilgisini al
    $stmt = $pdo->prepare("
        SELECT oi.order_id, o.table_id 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE oi.id = ? AND o.status = 'open'
    ");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'Ürün bulunamadı']);
        exit;
    }
    
    // Masa yetkisi kontrolü
    $role = currentUserRole();
    if ($item['table_id'] == 1 && !in_array($role, ['Admin','Garson (Yetkili)'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Bu masa için yetkiniz yok']);
        exit;
    }
    
    // Ürün adedini artır
    $pdo->prepare("UPDATE order_items SET quantity = quantity + 1 WHERE id = ?")
        ->execute([$item_id]);
    
    echo json_encode(['success' => true, 'message' => 'Ürün adedi artırıldı']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Sunucu hatası: ' . $e->getMessage()]);
}
?>
