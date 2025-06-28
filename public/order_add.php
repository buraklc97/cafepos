<?php
// public/order_add.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';

// Masa ID ve kategori
$table_id    = (int)($_GET['table'] ?? 0);
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if (!$table_id) {
    echo "<script>alert('Ge\xE7ersiz i\x15Flem.');window.location='pos.php';</script>";
    exit;
}

// Kategorileri al
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order IS NULL, sort_order, name")->fetchAll(PDO::FETCH_ASSOC);

// Se\xE7ili kategoriye g\xF6re \xFCr\xFCnleri getir
$query = "SELECT p.id, p.name, p.price, p.image, p.sort_order FROM products p";
$params = [];
if ($category_id) {
    $query .= " WHERE p.category_id = ?";
    $params[] = $category_id;
}
$query .= " ORDER BY p.sort_order IS NULL, p.sort_order, p.id";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<link rel="stylesheet" href="/assets/css/order_add.css">

<!-- Kategoriler -->
<div class="category-scroll mb-3">
    <button class="category-btn" data-category="0">Tüm Ürünler</button>
    <?php foreach ($categories as $cat): ?>
        <button class="category-btn<?= $category_id == $cat['id'] ? ' active' : '' ?>" data-category="<?= $cat['id'] ?>">
            <?= htmlspecialchars($cat['name']) ?>
        </button>
    <?php endforeach; ?>
</div>

<!-- Arama Kutusu -->
<div class="row mb-3 justify-content-center">
    <div class="col-12">
        <input type="text" id="productSearch" class="product-search" placeholder="Ürün ara...">
    </div>
</div>

<!-- Ürün Kartları -->
<?php if (empty($products)): ?>
    <div class="no-products">
        <div class="material-icons">inventory_2</div>
        <h4>Bu kategoride ürün bulunamadı</h4>
        <p>Lütfen başka bir kategori seçin</p>
    </div>
<?php else: ?>
    <div class="products-grid" id="productGrid">
        <?php foreach ($products as $p): ?>
            <div class="product-card product-item" data-name="<?= htmlspecialchars($p['name']) ?>">
                <div class="product-image<?= !empty($p['image']) ? ' loading' : '' ?>">
                    <?php if (!empty($p['image'])): ?>
                        <img src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="
                             data-src="<?= htmlspecialchars($p['image']) ?>"
                             alt="<?= htmlspecialchars($p['name']) ?>">
                    <?php else: ?>
                        <span class="material-icons">restaurant</span>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="product-price"><?= number_format($p['price'], 2) ?> ₺</div>
					<form method="post" action="order.php?table=<?= $table_id ?>">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <div class="quantity-box">
                            <button type="button" class="qty-btn minus">-</button>
                            <input type="number" name="quantity" class="quantity-input" min="1" value="1">
                            <button type="button" class="qty-btn plus">+</button>
                        </div>
                        <button type="submit" name="add_product" class="add-button">
                            <span class="material-icons">add_shopping_cart</span>
                            <span class="btn-text">Sepete Ekle</span>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>    

