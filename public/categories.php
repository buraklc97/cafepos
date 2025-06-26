<?php
// public/categories.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin']);

// Silme işlemi
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")
            ->execute([$id]);
        header('Location: categories.php');
        exit;
    } catch (PDOException $e) {
        // 1451 = FOREIGN KEY constraint violation
        if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1451) {
            echo "<script>
                    alert('Bu kategoride ekli ürün varken kategoriyi silemezsiniz.');
                    window.location='categories.php';
                  </script>";
            exit;
        } else {
            echo "<script>
                    alert('Silme hatası: " . addslashes($e->getMessage()) . "');
                    window.location='categories.php';
                  </script>";
            exit;
        }
    }
}

// Yeni kategori ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['name'])) {
    $name = trim($_POST['name']);
    $pdo->prepare("INSERT INTO categories (name) VALUES (?)")
        ->execute([$name]);
    header('Location: categories.php');
    exit;
}

// Kategorileri çek
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

include __DIR__ . '/../src/header.php';
?>
<div class="container my-5">
  <h1 class="text-center mb-4">Kategori Yönetimi</h1>

  <!-- Kategori Ekleme Formu -->
  <form method="post" class="mb-4 shadow-lg p-4 rounded-4">
    <div class="mb-4">
      <label for="categoryName" class="form-label">Yeni Kategori Adı:</label>
      <input type="text" name="name" id="categoryName" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-100">Kategori Ekle</button>
  </form>

  <!-- Kategori Tablosu -->
  <div class="table-responsive">
    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th style="width: 10%;">ID</th>
          <th>Ad</th>
          <th style="width: 20%;">İşlemler</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cats as $c): ?>
          <tr>
            <td><?= $c['id'] ?></td>
            <td><?= htmlspecialchars($c['name']) ?></td>
            <td>
              <a href="?delete=<?= $c['id'] ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?')">
                 Sil
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
