<?php
// public/order_add.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';

// Masa ID
$table_id = (int)($_GET['table'] ?? 0);
$category_id = (int)($_GET['category'] ?? 0);

if (!$table_id || !$category_id) {
    echo "<script>alert('Geçersiz işlem.');window.location='pos.php';</script>";
    exit;
}

// Ürünleri kategoriye göre çek
$query = "SELECT p.id, p.name, p.price, p.image FROM products p WHERE p.category_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$category_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
/* Ürün Modal Stilleri */
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
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
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

<script>
// Arama filtrasyonu
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#productGrid .product-item').forEach(function (item) {
                const name = item.dataset.name.toLowerCase();
                item.style.display = name.includes(term) ? '' : 'none';
            });
        });
    }
    
    // Ürün kartlarına hover efekti
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Form submit sonrası modal kapatma
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            // Modal'ı kapat
            const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
            if (modal) {
                modal.hide();
            }
        });
    });

    // Adet arttırma/azaltma butonları
    document.querySelectorAll('.quantity-box').forEach(box => {
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
});
</script>
