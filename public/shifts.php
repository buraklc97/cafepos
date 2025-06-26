<?php
// public/shifts.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/logger.php';
requireRole(['Admin']);

// 1) Mevcut açık shift’i al
$openShift = $pdo->query(
    "SELECT * FROM shifts WHERE closed_at IS NULL ORDER BY opened_at DESC LIMIT 1"
)->fetch();

// 2) Gün Başı (Open) işlemi
if (isset($_POST['open_shift']) && !$openShift) {
    $stmt = $pdo->prepare(
        "INSERT INTO shifts (opened_at, opened_by) VALUES (NOW(), ?)"
    );
    $stmt->execute([$_SESSION['user_id']]);
    header('Location: shifts.php');
    exit;
}

// 3) Gün Sonu (Close) işlemi
if (isset($_POST['close_shift']) && $openShift) {
    // Gün sonu alınmadan önce tüm masaların boş olup olmadığını kontrol et
    $occupied = $pdo->query("SELECT COUNT(*) FROM pos_tables WHERE status = 'occupied'")
                   ->fetchColumn();
    if ($occupied > 0) {
        echo "<script>alert('Gün sonu için tüm masaların boş olması gerekir.');window.location='shifts.php';</script>";
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE shifts
           SET closed_at = NOW(),
               closed_by = ?,
               auto_closed = 0
         WHERE id = ?"
    );
    $stmt->execute([$_SESSION['user_id'], $openShift['id']]);
    header('Location: shifts.php');
    exit;
}

// 4) Raporlama: Geçmiş shift’ler
$shifts = $pdo->query(
    "SELECT 
      s.id, s.opened_at, s.closed_at, s.auto_closed,
      u1.username AS opener,
      u2.username AS closer
    FROM shifts s
    JOIN users u1 ON s.opened_by = u1.id
    LEFT JOIN users u2 ON s.closed_by = u2.id
    ORDER BY s.opened_at DESC
    LIMIT 30"
)->fetchAll(PDO::FETCH_ASSOC);

// 5) Günlük ciro; nakit ve kart
foreach ($shifts as &$sh) {
    if (!empty($sh['closed_at'])) {
        $totals = $pdo->prepare(
            "SELECT 
                SUM(CASE WHEN method='cash' THEN amount ELSE 0 END) AS cash_total,
                SUM(CASE WHEN method='card' THEN amount ELSE 0 END) AS card_total
             FROM payments
             WHERE paid_at BETWEEN :start AND :end"
        );
        $totals->execute([
            'start'=> $sh['opened_at'],
            'end'  => $sh['closed_at']
        ]);
        $row = $totals->fetch(PDO::FETCH_ASSOC);
        $sh['cash_total'] = $row['cash_total'] ?: 0;
        $sh['card_total'] = $row['card_total'] ?: 0;
    } else {
        $sh['cash_total'] = $sh['card_total'] = null;
    }
}
unset($sh);

include __DIR__ . '/../src/header.php';
?>

<div class="container my-5">
  <h1 class="text-center mb-4">Gün Başı / Gün Sonu İşlemleri</h1>

  <!-- Gün Başı ve Gün Sonu İşlemleri -->
  <div class="mb-4 text-center">
    <?php if (!$openShift): ?>
      <form method="post" class="d-inline">
        <button type="submit" name="open_shift" class="btn btn-success btn-lg" onclick="return confirm('Gün Başı almak istediğinize emin misiniz?')">
          <span class="material-icons">event</span> Gün Başı Al
        </button>
      </form>
    <?php else: ?>
      <p><strong>Açık Shift:</strong> <?= htmlspecialchars($openShift['opened_at'] ?: '-', ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($openShift['opened_by'] ?? '-', ENT_QUOTES, 'UTF-8') ?>)</p>
      <form method="post" class="d-inline">
        <button type="submit" name="close_shift" class="btn btn-danger btn-lg" onclick="return confirm('Gün Sonu almak istediğinize emin misiniz?')">
          <span class="material-icons">event_busy</span> Gün Sonu Al
        </button>
      </form>
    <?php endif; ?>
  </div>

  <!-- Shift Raporu -->
  <h2 class="mb-4">Son 30 Günlük Shift Raporu</h2>
  <div class="table-responsive">
    <table class="table table-striped table-bordered table-hover">
      <thead>
        <tr>
          <th>ID</th><th>Başlangıç</th><th>Bitiş</th><th>Otomatik</th>
          <th>Açan</th><th>Kapayan</th><th>Nakit</th><th>Kart</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($shifts as $s): ?>
          <tr onclick="window.location='shift_detail.php?id=<?= $s['id'] ?>'" style="cursor:pointer;">
            <td><?= htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= htmlspecialchars($s['opened_at'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($s['closed_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $s['auto_closed'] ? 'Evet' : 'Hayır' ?></td>
            <td><?= htmlspecialchars($s['opener'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($s['closer'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $s['cash_total'] !== null ? number_format($s['cash_total'], 2) . ' ₺' : '-' ?></td>
            <td><?= $s['card_total'] !== null ? number_format($s['card_total'], 2) . ' ₺' : '-' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
