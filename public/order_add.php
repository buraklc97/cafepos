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

<!-- Ürün Kartları (Görsellerle) -->
<html lang="tr" data-theme="dark">

<head>
  <meta charset="UTF-8">
  <!-- Bootstrap ve diğer stiller -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/styleson.css?v=20250625">
</head>

<section class="mb-4">
    <div class="row g-3">
        <?php foreach ($products as $p): ?>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card shadow-sm rounded-4" style="cursor: pointer;">
                    <img src="<?= htmlspecialchars($p['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>" style="height: 200px; object-fit: cover;">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
                        <p class="card-text"><?= number_format($p['price'], 2) ?> ₺</p>
                        <form method="post" action="order.php?table=<?= $table_id ?>">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button type="submit" name="add_product" class="btn btn-success w-100">Ekle</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
