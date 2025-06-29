<?php
// public/products_edit.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/image_utils.php';
requireRole(['Admin']);

// Ürün ID'si
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: products.php');
    exit;
}

// Ürünü çek
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    header('Location: products.php');
    exit;
}

// Kategorileri çek
$cats = $pdo->query('SELECT * FROM categories ORDER BY sort_order IS NULL, sort_order, name')->fetchAll();

// Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id  = (int)$_POST['category_id'];
    $name         = trim($_POST['name']);
    $price        = (float)$_POST['price'];
    $sort_order   = strlen($_POST['sort_order']) ? (int)$_POST['sort_order'] : null;
    $imagePath    = $product['image'];
    $removeImage  = isset($_POST['delete_image']);

    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/uploads';
        $thumbDir  = $uploadDir . '/thumbs';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0777, true);
        }
        $fileName = uniqid('prod_', true) . '.webp';
        $dest     = $uploadDir . '/' . $fileName;
        if (imageToWebp($_FILES['image']['tmp_name'], $dest)) {
            $imagePath = 'uploads/' . $fileName;
            createWebpThumbnail($dest, $thumbDir . '/' . $fileName);
            // Yeni görsel yüklendiğinde eski dosyaları kaldır
            if ($product['image']) {
                $oldFile = __DIR__ . '/' . $product['image'];
                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
                $oldThumb = __DIR__ . '/uploads/thumbs/' . basename($product['image']);
                if (is_file($oldThumb)) {
                    unlink($oldThumb);
                }
            }
        }
    } elseif ($removeImage) {
        // Sadece mevcut görseli kaldır
        if ($product['image']) {
            $oldFile = __DIR__ . '/' . $product['image'];
            if (is_file($oldFile)) {
                unlink($oldFile);
            }
            $oldThumb = __DIR__ . '/uploads/thumbs/' . basename($product['image']);
            if (is_file($oldThumb)) {
                unlink($oldThumb);
            }
        }
        $imagePath = null;
    }

    $upd = $pdo->prepare('UPDATE products SET category_id = ?, name = ?, price = ?, image = ?, sort_order = ? WHERE id = ?');
    $upd->execute([$category_id, $name, $price, $imagePath, $sort_order, $id]);

    header('Location: products.php');
    exit;
}

include __DIR__ . '/../src/header.php';
?>

<h1 class="text-center mb-4">Ürün Düzenle</h1>
<form method="post" enctype="multipart/form-data" class="shadow-lg p-4 rounded-4 mb-4">
  <div class="mb-4">
    <label for="category_id" class="form-label">Kategori:</label>
    <select name="category_id" id="category_id" class="form-select" required>
      <option value="">Seçiniz</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $c['id'] == $product['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-4">
    <label for="name" class="form-label">Ürün Adı:</label>
    <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
  </div>
  <div class="mb-4">
    <label for="price" class="form-label">Fiyat (₺):</label>
    <input type="number" step="0.01" name="price" id="price" class="form-control" value="<?= number_format($product['price'], 2, '.', '') ?>" required>
  </div>
  <div class="mb-4">
    <label for="sort_order" class="form-label">Sıra:</label>
    <input type="number" name="sort_order" id="sort_order" class="form-control" value="<?= htmlspecialchars($product['sort_order']) ?>">
  </div>
  <div class="mb-4">
    <label for="image" class="form-label">Ürün Görseli:</label>
    <?php if ($product['image']): ?>
      <div class="mb-2">
        <img src="<?= htmlspecialchars($product['image']) ?>" alt="Ürün Görseli" style="max-height: 150px;" class="img-thumbnail">
      </div>
      <div class="form-check d-flex align-items-center gap-2 mb-2">
        <input class="form-check-input" type="checkbox" name="delete_image" id="delete_image">
        <label class="form-check-label" for="delete_image">Mevcut görseli sil</label>
      </div>  
    <?php endif; ?>
    <input type="file" name="image" id="image" class="form-control" accept="image/*">
  </div>
  <button type="submit" class="btn btn-primary btn-lg w-100">Güncelle</button>
</form>

<?php include __DIR__ . '/../src/footer.php'; ?>