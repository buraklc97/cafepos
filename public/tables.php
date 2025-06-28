<?php
// public/tables.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin']);  // Sadece Admin

// Silme işlemi
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Masa adını al (log veya mesaj için)
    $stmtName = $pdo->prepare("SELECT name FROM pos_tables WHERE id = ?");
    $stmtName->execute([$id]);
    $name = $stmtName->fetchColumn() ?: '';

    try {
        // Silme denemesi
        $pdo->prepare("DELETE FROM pos_tables WHERE id = ?")
            ->execute([$id]);

        // Loglama
        require __DIR__ . '/../src/logger.php';
        logAction('delete_table', "Masa silindi: {$name} (ID={$id})");

        // Başarılı ise yönlendir
        header('Location: tables.php');
        exit;

    } catch (PDOException $e) {
        // FK ihlali kodu 1451 ise özel uyarı
        if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1451) {
            echo "<script>
                    alert('Bu masa üzerinde hâlâ açık sipariş(ler) var. Lütfen önce siparişleri kapatın.');
                    window.location='tables.php';
                  </script>";
            exit;
        }
        // Diğer hatalarda dilerseniz genel bir uyarı:
        echo "<script>
                alert('Silme sırasında bir hata oluştu.');
                window.location='tables.php';
              </script>";
        exit;
    }
}

// Yeni masa ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['name'])) {
    $name = trim($_POST['name']);
    $pdo->prepare("INSERT INTO pos_tables (name) VALUES (?)")
        ->execute([$name]);
    require __DIR__ . '/../src/logger.php';
    logAction('add_table', "Masa eklendi: {$name}");
    header('Location: tables.php');
    exit;
}

// Masaları çek
$tables = $pdo->query("SELECT * FROM pos_tables ORDER BY id")->fetchAll();

include __DIR__ . '/../src/header.php';
?>
<div class="container my-5">
  <h1 class="text-center mb-4">Masa Yönetimi</h1>

  <!-- Yeni Masa Ekleme Formu -->
  <form method="post" class="shadow-lg p-4 rounded-4 mb-4">
    <div class="mb-3">
      <label for="name" class="form-label">Yeni Masa Adı:</label>
      <input type="text" name="name" id="name" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success btn-lg w-100">Ekle</button>
  </form>

  <!-- Masa Listesi -->
  <div class="table-responsive">
    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th>ID</th><th>Ad</th><th>Durum</th><th>İşlemler</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tables as $t): ?>
          <tr>
            <td><?= $t['id'] ?></td>
            <td><?= htmlspecialchars($t['name']) ?></td>
            <td>
              <?php if ($t['status'] === 'empty'): ?>
                <span class="badge bg-success">Boş</span>
              <?php else: ?>
                <span class="badge bg-danger">Dolu</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="?delete=<?= $t['id'] ?>" class="text-danger" onclick="return confirm('Bu masayı silmek istediğinize emin misiniz?')">Sil</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
