<?php
// public/shift_detail.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin']);

// Shift ID
$shiftId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$shiftId) {
    header('Location: shifts.php');
    exit;
}

// Shift bilgisi
$stmt = $pdo->prepare(
    "SELECT s.*, u1.username AS opener, u2.username AS closer
       FROM shifts s
       JOIN users u1 ON s.opened_by = u1.id
       LEFT JOIN users u2 ON s.closed_by = u2.id
      WHERE s.id = ?
      LIMIT 1"
);
$stmt->execute([$shiftId]);
$shift = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$shift) {
    header('Location: shifts.php');
    exit;
}

$start = $shift['opened_at'];
$end   = $shift['closed_at'] ?: date('Y-m-d H:i:s');

// Ödemeler
$payStmt = $pdo->prepare(
    "SELECT p.order_id, p.amount, p.method, p.paid_at, o.table_id
       FROM payments p
       JOIN orders o ON p.order_id = o.id
      WHERE p.paid_at BETWEEN ? AND ?
      ORDER BY p.paid_at"
);
$payStmt->execute([$start, $end]);
$payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

// Masa taşıma kayıtları
$transferStmt = $pdo->prepare(
    "SELECT tt.*, u.username AS user
       FROM table_transfers tt
       LEFT JOIN users u ON tt.transferred_by = u.id
      WHERE tt.transferred_at BETWEEN ? AND ?
      ORDER BY tt.transferred_at"
);
$transferStmt->execute([$start, $end]);
$transfers = $transferStmt->fetchAll(PDO::FETCH_ASSOC);

// Masa birleştirme kayıtları
$mergeStmt = $pdo->prepare(
    "SELECT tm.*, u.username AS user
       FROM table_merges tm
       LEFT JOIN users u ON tm.merged_by = u.id
      WHERE tm.merged_at BETWEEN ? AND ?
      ORDER BY tm.merged_at"
);
$mergeStmt->execute([$start, $end]);
$merges = $mergeStmt->fetchAll(PDO::FETCH_ASSOC);

// Log kayıtları
$logStmt = $pdo->prepare(
    "SELECT l.created_at, l.action, l.details, u.username AS user
       FROM logs l
       LEFT JOIN users u ON l.user_id = u.id
      WHERE l.created_at BETWEEN ? AND ?
      ORDER BY l.created_at"
);
$logStmt->execute([$start, $end]);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../src/header.php';
?>
<div class="container my-5">
  <h1 class="text-center mb-4">Shift Detayı #<?= htmlspecialchars($shift['id']) ?></h1>
  <div class="mb-4">
    <strong>Başlangıç:</strong> <?= htmlspecialchars($shift['opened_at']) ?><br>
    <strong>Bitiş:</strong> <?= htmlspecialchars($shift['closed_at'] ?: '-') ?><br>
    <strong>Açan:</strong> <?= htmlspecialchars($shift['opener']) ?><br>
    <strong>Kapayan:</strong> <?= htmlspecialchars($shift['closer'] ?: '-') ?>
  </div>

  <h3>Ödemeler</h3>
  <div class="table-responsive mb-4">
    <table class="table table-striped table-bordered table-hover">
      <thead>
        <tr>
          <th>Zaman</th><th>Masa</th><th>Tutar</th><th>Yöntem</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['paid_at']) ?></td>
          <td><?= htmlspecialchars($p['table_id']) ?></td>
          <td><?= number_format($p['amount'], 2) ?> ₺</td>
          <td><?= $p['method'] === 'card' ? 'Kart' : 'Nakit' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($payments)): ?>
        <tr><td colspan="4" class="text-center">Kayıt yok</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <h3>Masa Taşıma Kayıtları</h3>
  <div class="table-responsive mb-4">
    <table class="table table-striped table-bordered table-hover">
      <thead>
        <tr>
          <th>Zaman</th><th>Kaynak Masa</th><th>Hedef Masa</th><th>Kullanıcı</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transfers as $t): ?>
        <tr>
          <td><?= htmlspecialchars($t['transferred_at']) ?></td>
          <td><?= htmlspecialchars($t['from_table_id']) ?></td>
          <td><?= htmlspecialchars($t['to_table_id']) ?></td>
          <td><?= htmlspecialchars($t['user'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($transfers)): ?>
        <tr><td colspan="4" class="text-center">Kayıt yok</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <h3>Masa Birleştirme Kayıtları</h3>
  <div class="table-responsive mb-4">
    <table class="table table-striped table-bordered table-hover">
      <thead>
        <tr>
          <th>Zaman</th><th>Kaynak Sipariş</th><th>Hedef Sipariş</th><th>Kullanıcı</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($merges as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['merged_at']) ?></td>
          <td><?= htmlspecialchars($m['source_order_id']) ?></td>
          <td><?= htmlspecialchars($m['target_order_id']) ?></td>
          <td><?= htmlspecialchars($m['user'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($merges)): ?>
        <tr><td colspan="4" class="text-center">Kayıt yok</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <h3>Log Kayıtları</h3>
  <div class="table-responsive">
    <table class="table table-striped table-bordered table-hover">
      <thead>
        <tr>
          <th>Zaman</th><th>Kullanıcı</th><th>İşlem</th><th>Detay</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td><?= htmlspecialchars($l['created_at']) ?></td>
          <td><?= htmlspecialchars($l['user'] ?? '-') ?></td>
          <td><?= htmlspecialchars($l['action']) ?></td>
          <td><?= htmlspecialchars($l['details']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?>
        <tr><td colspan="4" class="text-center">Kayıt yok</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../src/footer.php'; ?>