<?php
// public/products.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin']);

// Silme işlemi
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")
        ->execute([$id]);
    header('Location: products.php');
    exit;
}

// Yeni ürün ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_sort_id'])) {
    $id         = (int)$_POST['update_sort_id'];
    $sort_order = strlen($_POST['sort_order']) ? (int)$_POST['sort_order'] : null;
    $up = $pdo->prepare('UPDATE products SET sort_order = ? WHERE id = ?');
    $up->execute([$sort_order, $id]);
    header('Location: products.php');
    exit;
}

// Yeni ürün ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['name']) && !isset($_POST['update_sort_id'])) {
    $category_id = (int)$_POST['category_id'];
    $name        = trim($_POST['name']);
    $price       = (float)$_POST['price'];
    $sort_order  = strlen($_POST['sort_order']) ? (int)$_POST['sort_order'] : null;

    // Görsel yükleme
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('prod_', true) . '.' . $ext;
        $dest     = $uploadDir . '/' . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            $imagePath = 'uploads/' . $fileName;
        }
    }

    $pdo->prepare(
        "INSERT INTO products (category_id, name, price, image, sort_order) VALUES (?, ?, ?, ?, ?)"
    )->execute([$category_id, $name, $price, $imagePath, $sort_order]);
    header('Location: products.php');
    exit;
}

// Kategorileri çek (dropdown için)
$cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order IS NULL, sort_order, name")->fetchAll();

// Ürünleri çek
$search = trim($_GET['search'] ?? '');
$sql = "SELECT p.id, p.name, p.price, p.sort_order, c.name AS category
        FROM products p
        JOIN categories c ON p.category_id = c.id";
$params = [];
if ($search !== '') {
    $sql .= " WHERE p.name LIKE ?";
    $params[] = '%' . $search . '%';
}
$sql .= " ORDER BY p.sort_order IS NULL, p.sort_order, p.id";
$stmt  = $pdo->prepare($sql);
$stmt->execute($params);
$prods = $stmt->fetchAll();

include __DIR__ . '/../src/header.php';
?>

<div class="container my-5">
  <h1 class="text-center mb-4">Ürün Yönetimi</h1>

  <!-- Yeni Ürün Ekleme Formu -->
  <form method="post" enctype="multipart/form-data" class="shadow-lg p-4 rounded-4 mb-4 mx-auto" style="max-width:600px;">
    <div class="mb-4">
      <label for="category_id" class="form-label">Kategori:</label>
      <select name="category_id" id="category_id" class="form-select" required>
        <option value="">Seçiniz</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-4">
      <label for="name" class="form-label">Ürün Adı:</label>
      <input type="text" name="name" id="name" class="form-control" required>
    </div>
    <div class="mb-4">
      <label for="price" class="form-label">Fiyat (₺):</label>
      <input type="number" step="0.01" name="price" id="price" class="form-control" required>
    </div>
    <div class="mb-4">
      <label for="sort_order" class="form-label">Sıra:</label>
      <input type="number" name="sort_order" id="sort_order" class="form-control">
    </div>
    <div class="mb-4">
      <label for="image" class="form-label">Ürün Görseli:</label>
      <input type="file" name="image" id="image" class="form-control" accept="image/*">
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-100">Ekle</button>
 </form>

  <!-- Ürün Arama ve Listeleme -->
  <div class="shadow-lg p-4 rounded-4 mb-4 mx-auto">
    <div class="mb-4">
      <input type="text" id="search" class="form-control" placeholder="Ürün adı ara...">
    </div>

    <div class="table-responsive">
      <table id="products-table" class="table table-striped table-hover">
      <thead>
        <tr>
          <th>ID</th>
          <th>Ad</th>
          <th>Kategori</th>
          <th>Sıra</th>
          <th>Fiyat</th>
          <th>İşlemler</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($prods as $p): ?>
          <tr>
            <td><?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['category']) ?></td>
            <td>
              <form method="post" class="d-flex">
                <input type="hidden" name="update_sort_id" value="<?= $p['id'] ?>">
                <input type="number" name="sort_order" value="<?= htmlspecialchars($p['sort_order']) ?>" class="form-control form-control-sm me-2" style="width:80px" onchange="this.form.submit()">
              </form>
            </td>
            <td><?= number_format($p['price'],2) ?> ₺</td>
            <td>
              <a href="products_edit.php?id=<?= $p['id'] ?>" class="me-2 text-warning">Düzenle</a>
              <a href="?delete=<?= $p['id'] ?>"
                 class="text-danger"
                 onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?')">Sil</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="/assets/js/products.js"></script>

<?php include __DIR__ . '/../src/footer.php'; ?>