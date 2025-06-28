<?php
// public/dashboard.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/logger.php';
// Admin olmayan kullanıcıları POS ekranına yönlendir
if (!isLoggedIn() || currentUserRole() !== 'Admin') {
    header('Location: pos.php');
    exit;
}

// Gün Başı/Vardiya durumu kontrolü
$openShift = $pdo->query(
    "SELECT * FROM shifts WHERE closed_at IS NULL ORDER BY opened_at DESC LIMIT 1"
)->fetch();

// Son 7 gün için günlük ciro (nakit ve kart)
$dailyStmt = $pdo->query(
    "SELECT DATE(paid_at) AS day,
            SUM(CASE WHEN method='cash' THEN amount ELSE 0 END) AS cash_total,
            SUM(CASE WHEN method='card' THEN amount ELSE 0 END) AS card_total
       FROM payments
      WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      GROUP BY DATE(paid_at)
      ORDER BY day ASC"
);
$dailyRows = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);

$days        = [];
$cashTotals  = [];
$cardTotals  = [];
$totalTotals = [];
foreach ($dailyRows as $r) {
    $days[]       = $r['day'];
    $cashTotals[] = (float)$r['cash_total'];
    $cardTotals[] = (float)$r['card_total'];
    $totalTotals[] = (float)$r['cash_total'] + (float)$r['card_total'];
}

include __DIR__ . '/../src/header.php';
?>
<h2 class="text-center my-4">Admin Paneli</h2>

<!-- Günlük Ciro Grafiği -->
<div class="card shadow-sm rounded-4 mb-4">
  <div class="card-body">
    <canvas id="dailyChart" style="max-height:350px;"></canvas>
  </div>
</div>

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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const days = <?php echo json_encode($days); ?>;
const totals = <?php echo json_encode($totalTotals); ?>;
const cashes = <?php echo json_encode($cashTotals); ?>;
const cards = <?php echo json_encode($cardTotals); ?>;

new Chart(document.getElementById('dailyChart'), {
  type: 'bar',
  data: {
    labels: days,
    datasets: [
      {
        label: 'Toplam',
        data: totals,
        backgroundColor: 'rgba(255,255,255,0.5)',
        borderColor: 'rgba(255,255,255,1)',
        borderWidth: 1
      },
      {
        label: 'Nakit',
        data: cashes,
        backgroundColor: 'rgba(16,185,129,0.5)',
        borderColor: 'rgba(16,185,129,1)',
        borderWidth: 1
      },
      {
        label: 'Kart',
        data: cards,
        backgroundColor: 'rgba(59,130,246,0.5)',
        borderColor: 'rgba(59,130,246,1)',
        borderWidth: 1
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: { y: { beginAtZero: true } },
    plugins: { legend: { position: 'bottom' } }
  }
});
</script>

<?php include __DIR__ . '/../src/footer.php'; ?>
