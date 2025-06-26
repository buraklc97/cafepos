<?php
// public/payment.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin','Garson', 'Garson (Yetkili)']);

// 1) Parametre olarak order ID
$order_id = (int)($_GET['order'] ?? 0);
if (!$order_id) {
    header('Location: pos.php');
    exit;
}

// 2) Siparişi ve masayı çek
$stmt = $pdo->prepare("
    SELECT o.*, t.id AS table_id 
      FROM orders o
      JOIN pos_tables t ON o.table_id = t.id
     WHERE o.id = ? AND o.status = 'open'
     LIMIT 1
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) {
    header('Location: pos.php');
    exit;
}

// 3) Toplam tutarı hesapla
$tot = $pdo->prepare("
    SELECT SUM(quantity * unit_price) 
      FROM order_items 
     WHERE order_id = ?
");
$tot->execute([$order_id]);
$total = $tot->fetchColumn() ?: 0.00;

// 4) Ödeme formuna POST gelirse işleme al
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = ($_POST['method'] ?? 'cash') === 'card' ? 'card' : 'cash';

    $pdo->beginTransaction();
    // payment kaydı
    $pdo->prepare("
        INSERT INTO payments (order_id, amount, method)
        VALUES (?, ?, ?)
    ")->execute([$order_id, $total, $method]);
    // siparişi kapat
    $pdo->prepare("
        UPDATE orders 
           SET status = 'closed', closed_at = NOW() 
         WHERE id = ?
    ")->execute([$order_id]);
    // masayı boşalt
    $pdo->prepare("
        UPDATE pos_tables 
           SET status = 'empty', opened_at = NULL 
         WHERE id = ?
    ")->execute([$order['table_id']]);
    $pdo->commit();
	
	require __DIR__ . '/../src/logger.php';
	logAction(
	'payment',
	"Sipariş {$order_id} ödendi: tutar={$total}₺, yöntem={$method}"
	);

    header('Location: pos.php');
    exit;
}

// 5) Görünüm
include __DIR__ . '/../src/header.php';
?>

<div class="container my-5">
  <h1 class="text-center mb-4">Ödeme – Masa <?= htmlspecialchars($order['table_id']) ?></h1>
  <p class="text-center"><strong>Toplam Tutar:</strong> <?= number_format($total,2) ?> ₺</p>

  <!-- Ödeme Formu -->
  <form method="post" style="max-width: 400px; margin: 0 auto;" class="shadow-lg p-4 rounded-4">
    <div class="mb-4">
      <label class="form-label">Ödeme Yöntemi:</label>
      <div class="btn-group w-100 gap-3" role="group">
        <input type="radio" class="btn-check" name="method" id="methodCash" value="cash" autocomplete="off" checked>
        <label class="btn btn-outline-primary flex-fill" for="methodCash">
          <span class="material-icons align-middle me-1">attach_money</span>
          Nakit
        </label>

        <input type="radio" class="btn-check" name="method" id="methodCard" value="card" autocomplete="off">
        <label class="btn btn-outline-primary flex-fill" for="methodCard">
          <span class="material-icons align-middle me-1">credit_card</span>
          Kredi Kartı
        </label>
      </div>
    </div>
    <div class="d-grid gap-2">
      <button type="submit" class="btn btn-primary btn-lg">Ödeme Al &amp; Masayı Kapat</button>
      <a href="order.php?table=<?= $order['table_id'] ?>" class="btn btn-secondary btn-lg">Geri Dön</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
