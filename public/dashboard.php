<?php
// public/dashboard.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/logger.php';
requireRole(['Admin']);

// Gün Başı/Vardiya durumu kontrolü
$openShift = $pdo->query(
    "SELECT * FROM shifts WHERE closed_at IS NULL ORDER BY opened_at DESC LIMIT 1"
)->fetch();

include __DIR__ . '/../src/header.php';
?>
<h2 class="text-center my-4">Admin Paneli</h2>

<div class="row g-3">
  <div class="col-12 col-md-6">
    <!-- Gün Başı / Gün Sonu -->
    <div class="card shadow-sm rounded-4">
      <div class="card-body text-center">
        <h4 class="card-title">Vardiya İşlemleri</h4>
        <div class="mt-3">
          <?php if (!$openShift): ?>
            <!-- Gün Başı Butonu -->
            <form method="post" action="shifts.php" class="d-inline">
              <button type="submit" name="open_shift" class="btn btn-success btn-lg">
                <span class="material-icons">event</span> Gün Başı Al
              </button>
            </form>
          <?php else: ?>
            <!-- Gün Sonu Butonu -->
            <form method="post" action="shifts.php" class="d-inline">
              <button type="submit" name="close_shift" class="btn btn-danger btn-lg">
                <span class="material-icons">event_busy</span> Gün Sonu Al
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Menü Linkleri (Kartlar) -->
  <div class="col-12 col-md-6">
    <div class="card shadow-sm rounded-4">
      <div class="card-body text-center">
        <h4 class="card-title">Yönetim Ekranları</h4>
        <div class="d-grid gap-2 mt-3">
          <a href="pos.php" class="btn btn-outline-primary btn-lg">Masalar</a>
          <a href="tables.php" class="btn btn-outline-info btn-lg">Masa Yönetimi</a>
          <a href="products.php" class="btn btn-outline-warning btn-lg">Ürün/Kategori Yönetimi</a>
          <a href="logs.php" class="btn btn-outline-secondary btn-lg">Sunucu Logları</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
