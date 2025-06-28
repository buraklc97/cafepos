<?php
// public/logs.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin']);  // Sadece Admin erişebilir

// 1) Son 10 masadan ürün silme logları
$stmtRemove = $pdo->prepare(
    "SELECT l.id, l.created_at, u.username AS user, l.ip_address, l.details
       FROM logs l
  LEFT JOIN users u ON l.user_id = u.id
      WHERE l.action = 'remove_item'
   ORDER BY l.created_at DESC
      LIMIT 10"
);
$stmtRemove->execute();
$removeLogs = $stmtRemove->fetchAll();

// 2) Diğer tüm loglar (son 100)
$stmtAll = $pdo->query(
    "SELECT l.id, l.created_at, u.username AS user, l.ip_address, l.action, l.details
       FROM logs l
  LEFT JOIN users u ON l.user_id = u.id
   ORDER BY l.created_at DESC
      LIMIT 100"
);
$allLogs = $stmtAll->fetchAll();

include __DIR__ . '/../src/header.php';
?>

<div class="container my-5">
  <h1 class="text-center mb-4">Sunucu Logları</h1>

  <!-- Son 10 Masadan Ürün Silme Kayıtları -->
  <div class="card shadow-sm mb-4 rounded-4">
    <div class="card-body">
      <h2 class="card-title">Son 10 Masadan Ürün Silme Kayıtları</h2>
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Zaman</th>
              <th>Kullanıcı</th>
              <th>IP Adresi</th>
              <th>Detay</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($removeLogs as $l): ?>
              <tr>
                <td><?= $l['id'] ?></td>
                <td><?= $l['created_at'] ?></td>
                <td><?= htmlspecialchars($l['user'] ?? '-') ?></td>
                <td><?= htmlspecialchars($l['ip_address']) ?></td>
                <td><?= htmlspecialchars($l['details']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Tüm Loglar (Son 100) -->
  <div class="card shadow-sm mb-4 rounded-4">
    <div class="card-body">
      <h2 class="card-title">Tüm Loglar (Son 100)</h2>
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Zaman</th>
              <th>Kullanıcı</th>
              <th>IP Adresi</th>
              <th>İşlem</th>
              <th>Detay</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allLogs as $l): ?>
              <tr>
                <td><?= $l['id'] ?></td>
                <td><?= $l['created_at'] ?></td>
                <td><?= htmlspecialchars($l['user'] ?? '-') ?></td>
                <td><?= htmlspecialchars($l['ip_address']) ?></td>
                <td><?= htmlspecialchars($l['action']) ?></td>
                <td><?= htmlspecialchars($l['details']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
