<?php
// public/order.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson', 'Garson (Yetkili)']);
require_once __DIR__ . '/../src/logger.php';

// Masa ID
$table_id = (int)($_GET['table'] ?? 0);
if (!$table_id) {
    header('Location: pos.php');
    exit;
}

// Masa adını al
$stmtTableName = $pdo->prepare("SELECT name FROM pos_tables WHERE id = ?");
$stmtTableName->execute([$table_id]);
$tableName = $stmtTableName->fetchColumn();

// Vardiya kontrolü
$shift = $pdo->query("SELECT * FROM shifts WHERE closed_at IS NULL ORDER BY opened_at DESC LIMIT 1")->fetch();
if (!$shift) {
    echo "<script>alert('Gün Başı alınmamış. Lütfen önce Gün Başı yapın.');window.location='dashboard.php';</script>";
    exit;
}


// Sipariş kontrol ve oluşturma
$stmt = $pdo->prepare("SELECT * FROM orders WHERE table_id = ? AND status = 'open' LIMIT 1");
$stmt->execute([$table_id]);
$order = $stmt->fetch();
if (!$order) {
    $pdo->prepare("INSERT INTO orders (table_id) VALUES (?)")->execute([$table_id]);
	 // Table status will be set to occupied when the first product is added
    $order_id = $pdo->lastInsertId();
} else {
    $order_id = $order['id'];
}

// Ürün ekleme işlemi
if (isset($_POST['add_product'])) {
    $prod_id = (int)$_POST['product_id'];
    $qty = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    $chk = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? AND product_id = ?");
    $chk->execute([$order_id, $prod_id]);
    if ($item = $chk->fetch()) {
        $pdo->prepare("UPDATE order_items SET quantity = quantity + ? WHERE id = ?")->execute([$qty, $item['id']]);
    } else {
        $price = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $price->execute([$prod_id]);
        $unit = $price->fetchColumn();
        $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)")->execute([$order_id, $prod_id, $qty, $unit]);
    }

    // Masa durumu güncelleme
    $pdo->prepare("UPDATE pos_tables SET status = 'occupied', opened_at = NOW() WHERE id = ? AND opened_at IS NULL")->execute([$table_id]);

    header("Location: order.php?table={$table_id}");
    exit;
}

// Ürün silme
if (isset($_GET['delete_item'])) {
    $item_id = (int)$_GET['delete_item'];

    // Silme işlemi için yetki kontrolü
    if ($_SESSION['user_role'] === 'Garson' && $_SESSION['user_role'] !== 'Garson (Yetkili)') {
        echo "<script>alert('Silme yetkiniz yok.');window.location='order.php?table={$table_id}';</script>";
        exit;
    }

    // Admin ve Garson (Yetkili) için işlem yapılacak
    $stmtInfo = $pdo->prepare(
        "SELECT oi.quantity, p.name, oi.order_id FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.id = ?"
    );
    $stmtInfo->execute([$item_id]);
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if ($info && $info['quantity'] > 1) {
        $pdo->prepare("UPDATE order_items SET quantity = quantity - 1 WHERE id = ?")->execute([$item_id]);
    } else {
        $pdo->prepare("DELETE FROM order_items WHERE id = ?")->execute([$item_id]);
    }

    if ($info) {
        $detail = "Sipariş {$info['order_id']} masa {$table_id} ürünü silindi: {$info['name']}";
        logAction('remove_item', $detail);
    }

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
    $cnt->execute([$order_id]);
    if ($cnt->fetchColumn() == 0) {
        $pdo->prepare("UPDATE pos_tables SET status = 'empty', opened_at = NULL WHERE id = ?")->execute([$table_id]);
        $pdo->prepare("UPDATE orders SET status = 'closed', closed_at = NOW() WHERE id = ?")->execute([$order_id]);
    }

    header("Location: order.php?table={$table_id}");
    exit;
}

// Veri çekme
$items = $pdo->prepare("SELECT oi.id, oi.quantity, oi.unit_price, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items->execute([$order_id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Silinen ürün loglarını çek (sadece admin ve aktif ürün varsa)
$itemLogs = [];
if ($_SESSION['user_role'] === 'Admin' && !empty($items)) {
    $logStmt = $pdo->prepare(
        "SELECT l.created_at, l.details, u.username
           FROM logs l
      LEFT JOIN users u ON l.user_id = u.id
          WHERE l.action='remove_item' AND l.details LIKE ?
       ORDER BY l.created_at DESC"
    );
    $logStmt->execute(['%Sipariş ' . $order_id . '%']);
    $itemLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/../src/header.php';
?>

<style>
/* Garson Sipariş Sayfası Özel Stilleri */
.order-header {
    background: linear-gradient(135deg, var(--header-bg) 0%, rgba(37, 99, 235, 0.8) 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 20px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.order-header h1 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.back-button {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.back-button:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
}

.category-section {
    background: var(--container-bg);
    border: 2px solid var(--border-color);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}


.cart-section {
    background: var(--container-bg);
    border: 2px solid var(--border-color);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.cart-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
}

.cart-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: rgba(var(--text-rgb), 0.6);
}

.cart-empty .material-icons {
    font-size: 4rem;
    color: var(--border-color);
    margin-bottom: 1rem;
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
}

.cart-table th {
    background: rgba(var(--text-rgb), 0.05);
    color: var(--text);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid var(--border-color);
}

.cart-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text);
}

.cart-table tr:hover {
    background: rgba(var(--text-rgb), 0.02);
}

.delete-link {
    color: var(--danger);
    text-decoration: none;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.delete-link:hover {
    background: rgba(220, 38, 38, 0.1);
    color: var(--danger);
}

.payment-button {
    background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
    color: white;
    border: none;
    padding: 1.25rem 2.5rem;
    border-radius: 16px;
    font-size: 1.2rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    margin: 0 auto;
    display: block;
    text-align: center;
    width: fit-content;
}

.payment-button:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.2);
}

/* Modal Özelleştirmeleri */
.modal-content {
    border: none;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modal-header {
    border-bottom: 2px solid var(--border-color);
    border-radius: 20px 20px 0 0;
    background: var(--container-bg);
    padding: 1.5rem;
}

.modal-body {
    background: var(--container-bg);
    padding: 2rem;
    border-radius: 0 0 20px 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .order-header {
        padding: 1rem;
    }
    
    .order-header h1 {
        font-size: 1.5rem;
    }
    
    
    .cart-section {
        padding: 1.5rem;
    }
    
    .cart-table {
        font-size: 0.875rem;
    }
    
    .cart-table th,
    .cart-table td {
        padding: 0.75rem 0.5rem;
    }
    
    .payment-button {
        padding: 1rem 2rem;
        font-size: 1.1rem;
    }
}

@media (max-width: 576px) {
    .cart-table {
        font-size: 0.8rem;
    }
    
    .cart-table th,
    .cart-table td {
        padding: 0.5rem 0.25rem;
    }
}
</style>

<div class="order-header">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="pos.php" class="back-button">
            <span class="material-icons">arrow_back</span>Geri Dön
        </a>
        <div></div>
    </div>
    <h1>
        <span class="material-icons">restaurant_menu</span>
        <?= htmlspecialchars($tableName) ?>
    </h1>
</div>

<!-- Ürün Ekle -->
<div class="category-section text-center">
    <button id="openAddProduct" class="btn btn-primary btn-lg">
        <span class="material-icons me-2">add</span>Ürün Ekle
    </button>
</div>

<!-- Sipariş Sepeti -->
<div class="cart-section">
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
                        <td>
                            <span class="badge bg-primary rounded-pill"><?= $i['quantity'] ?></span>
                        </td>
                        <td><?= number_format($i['unit_price'], 2) ?> ₺</td>
                        <td><strong><?= number_format($subtotal, 2) ?> ₺</strong></td>
                        <td>
                            <?php if ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'Garson (Yetkili)'): ?>
                                <a href="?table=<?= $table_id ?>&delete_item=<?= $i['id'] ?>" 
                                   class="delete-link" 
                                   onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?')">
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
                    <td><strong style="font-size: 1.2rem; color: var(--btn-bg);"><?= number_format($total, 2) ?> ₺</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>

<?php if (!empty($items) && (($_SESSION['user_role'] === 'Admin') || ($_SESSION['user_role'] === 'Garson (Yetkili)'))): ?>
<a href="payment.php?order=<?= $order_id ?>" class="payment-button">
    <span class="material-icons">payment</span>
    Ödeme Al & Masayı Kapat
</a>
<?php endif; ?>

<?php if ($_SESSION['user_role'] === 'Admin' && !empty($itemLogs)): ?>
<div class="cart-section mt-4">
    <h3 class="mb-3">Silinen Ürünler</h3>
    <table class="cart-table">
        <thead>
            <tr>
                <th>Zaman</th>
                <th>Kullanıcı</th>
                <th>Detay</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itemLogs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                    <td><?= htmlspecialchars($log['username'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['details']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Popup Modal -->
<div class="modal" tabindex="-1" id="addProductModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="material-icons me-2">restaurant_menu</span>
                    Ürün Seçin
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modal-body-content">
                <!-- order_add.php içeriği burada dinamik olarak yüklenecek -->
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>

<script>
let productModal;

function initQuantityButtons(container) {
    container.querySelectorAll('.quantity-box').forEach(box => {
        const input = box.querySelector('.quantity-input');
        box.querySelector('.minus').addEventListener('click', () => {
            const val = parseInt(input.value) || 1;
            input.value = Math.max(1, val - 1);
        });
        box.querySelector('.plus').addEventListener('click', () => {
            const val = parseInt(input.value) || 1;
            input.value = val + 1;
        });
    });
}

function attachModalEvents(container) {
    const searchInput = container.querySelector('#productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            container.querySelectorAll('#productGrid .product-item').forEach(item => {
                const name = item.dataset.name.toLowerCase();
                item.style.display = name.includes(term) ? '' : 'none';
            });
        });
    }

    container.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            openAddProductModal(this.dataset.category);
        });
    });

    initQuantityButtons(container);
}

function openAddProductModal(categoryId = 0) {
    fetch('order_add.php?table=<?= $table_id ?>&category=' + categoryId)
        .then(res => res.text())
        .then(html => {
            const modalBody = document.getElementById('modal-body-content');
            modalBody.innerHTML = html;

            const modalTitle = document.querySelector('.modal-title');
            modalTitle.innerHTML = '<span class="material-icons me-2">restaurant_menu</span>\u00dcr\u00fcn Seçin';

            if (!productModal) {
                productModal = new bootstrap.Modal(document.getElementById('addProductModal'), {keyboard:false});
            }
            productModal.show();

            attachModalEvents(modalBody);
        })
        .catch(err => {
            console.error('Hata:', err);
            alert('\u00dcr\u00fcnler y\u00fcklenirken bir hata olu\u015ftu.');
        });
}

document.getElementById('openAddProduct').addEventListener('click', () => openAddProductModal());
</script>
