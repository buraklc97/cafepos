<?php
// public/transfer.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/logger.php';
requireRole(['Admin','Garson']);

// Kaynak masa ID’si
$fromTable = (int)($_GET['from'] ?? 0);
if (!$fromTable) {
    header('Location: pos.php');
    exit;
}

// Kaynak masada açık sipariş var mı?
$stmtOrder = $pdo->prepare(
  "SELECT id, table_id, status
     FROM orders
    WHERE table_id = ? AND status = 'open'
    LIMIT 1"
);
$stmtOrder->execute([$fromTable]);
$order = $stmtOrder->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo "<script>alert('Kaynak masada açık sipariş yok.');window.location='pos.php';</script>";
    exit;
}

// Orijinal açılış zamanını al
$stmtOpen = $pdo->prepare("SELECT opened_at FROM pos_tables WHERE id = ?");
$stmtOpen->execute([$fromTable]);
$originalOpenedAt = $stmtOpen->fetchColumn();

// Hedef masalar listesi (kendi dışındaki)
$tables = $pdo->query(
  "SELECT id, name, status
     FROM pos_tables
    WHERE id != {$fromTable}
    ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

// Transfer işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['to_table'])) {
    $toTable = (int)$_POST['to_table'];

    // Siparişi güncelle
    $pdo->prepare("UPDATE orders SET table_id = ? WHERE id = ?")
        ->execute([$toTable, $order['id']]);

    // Kaynak masayı boşalt
    $pdo->prepare(
      "UPDATE pos_tables
         SET status = 'empty', opened_at = NULL
       WHERE id = ?"
    )->execute([$fromTable]);

    // Hedef masayı doldur, orijinal açılış zamanını koru
    $pdo->prepare(
      "UPDATE pos_tables
         SET status = 'occupied', opened_at = ?
       WHERE id = ?"
    )->execute([$originalOpenedAt, $toTable]);

    // table_transfers kaydı
    $pdo->prepare(
      "INSERT INTO table_transfers
         (order_id, from_table_id, to_table_id, transferred_by)
       VALUES (?, ?, ?, ?)"
    )->execute([
      $order['id'], $fromTable, $toTable, $_SESSION['user_id']
    ]);

    // Loglama
    logAction(
      'transfer_table',
      "Sipariş {$order['id']} masa {$fromTable} → {$toTable} taşındı"
    );

    header('Location: pos.php');
    exit;
}

include __DIR__ . '/../src/header.php';
?>
<div class="container my-5">
  <h1 class="text-center mb-4">Masa Değiştir – Masa <?= $fromTable ?></h1>
  
  <form method="post" class="shadow-lg p-4 rounded-4">
    <div class="mb-4">
      <label for="to_table" class="form-label">Hedef Masa:</label>
      <select name="to_table" id="to_table" class="form-select" required>
        <option value="">Seçiniz</option>
        <?php foreach($tables as $t): ?>
          <option value="<?= $t['id'] ?>">
            <?= htmlspecialchars($t['name']) ?> (<?= $t['status'] === 'empty' ? 'Boş' : 'Dolu' ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="d-grid gap-2">
      <button type="submit" class="btn btn-primary btn-lg">Taşı</button>
      <a href="pos.php" class="btn btn-secondary btn-lg">İptal</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
