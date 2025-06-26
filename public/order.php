<?php
// public/order.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson', 'Garson (Yetkili)']);

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

// Kategorileri çek
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Sipariş kontrol ve oluşturma
$stmt = $pdo->prepare("SELECT * FROM orders WHERE table_id = ? AND status = 'open' LIMIT 1");
$stmt->execute([$table_id]);
$order = $stmt->fetch();
if (!$order) {
    $pdo->prepare("INSERT INTO orders (table_id) VALUES (?)")->execute([$table_id]);
    $order_id = $pdo->lastInsertId();
    $pdo->prepare("UPDATE pos_tables SET status = 'occupied', opened_at = NOW() WHERE id = ?")->execute([$table_id]);
} else {
    $order_id = $order['id'];
}

// Ürün ekleme işlemi
if (isset($_POST['add_product'])) {
    $prod_id = (int)$_POST['product_id'];
    $chk = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? AND product_id = ?");
    $chk->execute([$order_id, $prod_id]);
    if ($item = $chk->fetch()) {
        $pdo->prepare("UPDATE order_items SET quantity = quantity + 1 WHERE id = ?")->execute([$item['id']]);
    } else {
        $price = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $price->execute([$prod_id]);
        $unit = $price->fetchColumn();
        $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, 1, ?)")->execute([$order_id, $prod_id, $unit]);
    }

    // Masa durumu güncelleme
    $pdo->prepare("UPDATE pos_tables SET status = 'occupied' WHERE id = ? AND opened_at IS NULL")->execute([$table_id]);

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
    $stmt = $pdo->prepare("SELECT quantity FROM order_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    if ($item && $item['quantity'] > 1) {
        $pdo->prepare("UPDATE order_items SET quantity = quantity - 1 WHERE id = ?")->execute([$item_id]);
    } else {
        $pdo->prepare("DELETE FROM order_items WHERE id = ?")->execute([$item_id]);
    }

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
    $cnt->execute([$order_id]);
    if ($cnt->fetchColumn() == 0) {
        $pdo->prepare("UPDATE pos_tables SET status = 'empty', opened_at = NULL WHERE id = ?")->execute([$table_id]);
    }

    header("Location: order.php?table={$table_id}");
    exit;
}

// Veri çekme
$items = $pdo->prepare("SELECT oi.id, oi.quantity, oi.unit_price, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items->execute([$order_id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../src/header.php';
?>

<!-- Geri Dön Butonu -->
<div class="d-flex justify-content-center mb-4">
  <a href="pos.php" class="btn btn-secondary btn-lg">Geri Dön</a>
</div>

<h1 class="text-center my-4">Seçili Masa: <?= htmlspecialchars($tableName) ?></h1>

<!-- Kategori Butonları -->
<div class="d-flex justify-content-center mb-4">
    <?php foreach ($categories as $category): ?>
        <button class="btn btn-primary btn-lg mx-2" id="category_<?= $category['id'] ?>" data-category="<?= $category['id'] ?>">
            <?= htmlspecialchars($category['name']) ?>
        </button>
    <?php endforeach; ?>
</div>

<!-- Sipariş Kalemleri -->
<section class="mb-4">
    <h2>Sipariş Kalemleri</h2>
    <?php if (empty($items)): ?>
        <p>Henüz ürün eklenmedi.</p>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Ürün</th><th>Adet</th><th>Birim Fiyat</th><th>Tutar</th><th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i): ?>
                    <tr>
                        <td><?= htmlspecialchars($i['name']) ?></td>
                        <td><?= $i['quantity'] ?></td>
                        <td><?= number_format($i['unit_price'], 2) ?> ₺</td>
                        <td><?= number_format($i['quantity'] * $i['unit_price'], 2) ?> ₺</td>
                        <td>
                            <?php if ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'Garson (Yetkili)'): ?>
                                <a href="?table=<?= $table_id ?>&delete_item=<?= $i['id'] ?>" class="text-danger" onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?')">Sil</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<a href="payment.php?order=<?= $order_id ?>" class="btn btn-danger btn-lg d-block mx-auto" style="width: auto;">Ödeme Al & Masayı Kapat</a>

<!-- Popup Modal -->
<div class="modal" tabindex="-1" id="addProductModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        
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
  // Kategori butonlarına tıklanıldığında popup modal'ını aç ve AJAX ile ürünleri yükle
  const categoryButtons = document.querySelectorAll('[data-category]');
  categoryButtons.forEach(button => {
    button.addEventListener('click', function() {
      var categoryId = this.getAttribute('data-category');

      // AJAX ile order_add.php'yi kategoriye göre yükle
      fetch('order_add.php?table=<?= $table_id ?>&category=' + categoryId)
        .then(response => response.text())
        .then(data => {
          document.getElementById('modal-body-content').innerHTML = data;
          var modal = new bootstrap.Modal(document.getElementById('addProductModal'), {
            keyboard: false
          });
          modal.show();
        })
        .catch(error => {
          console.error('Hata:', error);
        });
    });
  });
</script>
