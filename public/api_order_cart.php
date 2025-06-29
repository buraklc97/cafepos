<?php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson', 'Garson (Yetkili)']);
$role = currentUserRole();

$table_id = (int)($_GET['table'] ?? 0);
if (!$table_id) {
    http_response_code(400);
    exit;
}
if ($table_id == 1 && !in_array($role, ['Admin','Garson (Yetkili)'])) {
    http_response_code(403);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM orders WHERE table_id = ? AND status = 'open' LIMIT 1");
$stmt->execute([$table_id]);
$order_id = $stmt->fetchColumn();
$items = [];
if ($order_id) {
    $stmtItems = $pdo->prepare("SELECT oi.id, oi.quantity, oi.unit_price, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? ORDER BY oi.id");
    $stmtItems->execute([$order_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
}

ob_start();
?>
<div class="cart-header">
    <span class="material-icons">shopping_cart</span>
    Sipariş Sepeti
</div>
<?php if (empty($items)): ?>
    <div class="cart-empty">
        <div class="material-icons">shopping_cart</div>
        <p>Henüz ürün eklenmedi</p>
        <small>Yukarıdaki butondan ürün eklemeye başlayın</small>
    </div>
<?php else: ?>
    <table class="cart-table">
        <thead>
            <tr>
                <th>Ürün</th>
                <th>Adet</th>
                <th>Birim Fiyat</th>
                <th>Tutar</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total = 0;
            foreach ($items as $i):
                $subtotal = $i['quantity'] * $i['unit_price'];
                $total += $subtotal;
            ?>
                <tr>
                    <td><?= htmlspecialchars($i['name']) ?></td>
                    <td class="qty-cell">
                        <span class="badge bg-primary rounded-pill"><?= $i['quantity'] ?></span>
                        <a href="#" class="qty-btn plus" data-item-id="<?= $i['id'] ?>">+</a>
                    </td>
                    <td><?= number_format($i['unit_price'], 2) ?> ₺</td>
                    <td><strong><?= number_format($subtotal, 2) ?> ₺</strong></td>
                    <td>
                        <?php if ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'Garson (Yetkili)'): ?>
                            <a href="?table=<?= $table_id ?>&delete_item=<?= $i['id'] ?>" class="delete-link" onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?')">
                                <span class="material-icons">delete</span>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="border-top: 3px solid var(--border-color);">
                <td colspan="3"><strong>TOPLAM</strong></td>
                <td><strong style="font-size: 1.2rem; color: var(--btn-bg);">
                    <?= number_format($total, 2) ?> ₺
                </strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>
<?php
$html = ob_get_clean();
header('Content-Type: text/html; charset=UTF-8');
echo $html;
