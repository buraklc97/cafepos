<?php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson', 'Garson (Yetkili)']);

$item_id = (int)($_POST['item_id'] ?? 0);
$table_id = (int)($_POST['table_id'] ?? 0);

if (!$item_id || !$table_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz parametreler']);
    exit;
}

// Masa yetkisi kontrolü
$role = currentUserRole();
if ($table_id == 1 && !in_array($role, ['Admin','Garson (Yetkili)'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Bu masa için yetkiniz yok']);
    exit;
}

try {
    // Mevcut sipariş kontrolü
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE table_id = ? AND status = 'open' LIMIT 1");
    $stmt->execute([$table_id]);
    $order_id = $stmt->fetchColumn();
    
    if (!$order_id) {
        http_response_code(404);
        echo json_encode(['error' => 'Aktif sipariş bulunamadı']);
        exit;
    }
    
    // Ürün bilgilerini al
    $stmtInfo = $pdo->prepare(
        "SELECT oi.quantity, p.name, oi.order_id FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.id = ? AND oi.order_id = ?"
    );
    $stmtInfo->execute([$item_id, $order_id]);
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
    
    if (!$info) {
        http_response_code(404);
        echo json_encode(['error' => 'Ürün bulunamadı']);
        exit;
    }
    
    // Silme yetkisi kontrolü (sadece azaltma için)
    if ($info['quantity'] == 1 && $role === 'Garson') {
        http_response_code(403);
        echo json_encode(['error' => 'Ürün silme yetkiniz yok']);
        exit;
    }
    
    if ($info['quantity'] > 1) {
        // Adedi azalt
        $pdo->prepare("UPDATE order_items SET quantity = quantity - 1 WHERE id = ?")
            ->execute([$item_id]);
        echo json_encode(['success' => true, 'message' => 'Ürün adedi azaltıldı']);
    } else {
        // Ürünü sil
        $pdo->prepare("DELETE FROM order_items WHERE id = ?")
            ->execute([$item_id]);
        
        // Log kaydı
        require_once __DIR__ . '/../src/logger.php';
        $detail = "Sipariş {$info['order_id']} masa {$table_id} ürünü silindi: {$info['name']}";
        logAction('remove_item', $detail);
        
        // Sipariş boş mu kontrol et
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
        $cnt->execute([$order_id]);
        if ($cnt->fetchColumn() == 0) {
            $pdo->prepare("UPDATE pos_tables SET status = 'empty', opened_at = NULL WHERE id = ?")
                ->execute([$table_id]);
            $pdo->prepare("UPDATE orders SET status = 'closed', closed_at = NOW() WHERE id = ?")
                ->execute([$order_id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Ürün silindi']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Sunucu hatası: ' . $e->getMessage()]);
}
?>

