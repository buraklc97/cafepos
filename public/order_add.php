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
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Se\xE7ili kategoriye g\xF6re \xFCr\xFCnleri getir
$query = "SELECT p.id, p.name, p.price, p.image FROM products p";
$params = [];
if ($category_id) {
    $query .= " WHERE p.category_id = ?";
    $params[] = $category_id;
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
/* Ürün Modal Stilleri */
.category-scroll {
    display: flex;
    overflow-x: auto;
    gap: 0.5rem;
    padding-bottom: 0.5rem;
}

.category-btn {
    flex: 0 0 auto;
    white-space: nowrap;
    border: none;
    border-radius: 12px;
    padding: 0.5rem 1rem;
    background: var(--btn-bg);
    color: #fff;
}

.category-btn.active {
    background: var(--success);
}

.product-search {
    background: var(--input-bg);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 0.875rem 1rem;
    font-size: 1rem;
    color: var(--text);
    transition: all 0.2s ease;
    width: 100%;
}

.product-search:focus {
    border-color: var(--btn-bg);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    outline: none;
}

.products-grid {
    display: grid;
    gap: 1.5rem;
    margin-top: 1.5rem;
    grid-template-columns: repeat(4, 1fr);
}

.product-card {
    background: var(--container-bg);
    border: 2px solid var(--border-color);
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border-color: var(--btn-bg);
}

.product-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
    background: linear-gradient(135deg, var(--btn-bg) 0%, rgba(37, 99, 235, 0.8) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info {
    padding: 1.5rem;
    text-align: center;
}

.product-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 0.75rem;
    line-height: 1.3;
}

.product-price {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--btn-bg);
    margin-bottom: 1rem;
}

.add-button {
    background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.quantity-input {
    width: 60px;
    padding: 0.5rem;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    text-align: center;
    font-size: 1rem;
    margin-bottom: 0;
}

.quantity-box {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    margin: 0 auto 0.5rem;
}

.qty-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    color: #fff;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.qty-btn.minus {
    background: var(--danger);
}

.qty-btn.plus {
    background: var(--success);
}

.add-button:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: scale(1.02);
}

.add-button:active {
    transform: scale(0.98);
}

.no-products {
    text-align: center;
    padding: 3rem 1rem;
    color: rgba(var(--text-rgb), 0.6);
}

.no-products .material-icons {
    font-size: 4rem;
    color: var(--border-color);
    margin-bottom: 1rem;
}

/* Responsive Design */
@media (min-width: 992px) {
    .products-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (min-width: 768px) and (max-width: 991.98px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .product-image {
        height: 150px;
        font-size: 2.5rem;
    }
    
    .product-info {
        padding: 1rem;
    }
    
    .product-name {
        font-size: 1rem;
    }
    
    .product-price {
        font-size: 1.1rem;
    }
}

@media (max-width: 576px) {
    .products-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    
    .product-image {
        height: 120px;
        font-size: 2rem;
    }
    
    .product-info {
        padding: 0.75rem;
    }
    
    .product-name {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .product-price {
        font-size: 1rem;
        margin-bottom: 0.75rem;
    }
    
	.add-button {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
	
	.qty-btn {
        width: 24px;
        height: 24px;
        font-size: 0.9rem;
    }

    .quantity-input {
        width: 45px;
        padding: 0.4rem;
        font-size: 0.9rem;
    }

    .add-button .btn-text {
        display: none;
    }
}
</style>

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
                <div class="product-image">
                    <?php if (!empty($p['image'])): ?>
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
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

