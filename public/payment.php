<?php
// public/payment.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson (Yetkili)']);

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

// 4) Sipariş kalemlerini çek
$itemStmt = $pdo->prepare(
    "SELECT oi.quantity, oi.unit_price, p.name
       FROM order_items oi
       JOIN products p ON oi.product_id = p.id
      WHERE oi.order_id = ?"
);
$itemStmt->execute([$order_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// 5) Ödeme formuna POST gelirse işleme al
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

// 6) Görünüm
include __DIR__ . '/../src/header.php';
?>

<style>
  .receipt {
    font-family: "Courier New", Courier, monospace;
    border: 2px dashed var(--border-color);
    padding: 1rem;
    margin-bottom: 1.5rem;
    max-width: 420px;
    margin-left: auto;
    margin-right: auto;
  }
  .payment-form {
    max-width: 420px;
    margin-left: auto;
    margin-right: auto;
  }
  .receipt-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
  }
  .receipt-table th,
  .receipt-table td {
    padding: 0.5rem;
    border-bottom: 1px dashed var(--border-color);
    word-break: break-word;
  }
  .receipt-table tfoot td {
    font-weight: 700;
  }
</style>

<div class="container my-5">
  <h1 class="text-center mb-4">Ödeme – Masa <?= htmlspecialchars($order['table_id']) ?></h1>
  <div class="row justify-content-center g-5">
    <div class="col-md-6">
      <div class="receipt">
        <table class="receipt-table">
          <thead>
            <tr>
              <th>Ürün</th>
              <th>Adet</th>
          <th>Birim Fiyat</th>
          <th>Tutar</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): $sub = $it['quantity'] * $it['unit_price']; ?>
        <tr>
          <td><?= htmlspecialchars($it['name']) ?></td>
          <td><?= $it['quantity'] ?></td>
          <td><?= number_format($it['unit_price'], 2) ?> ₺</td>
          <td><?= number_format($sub, 2) ?> ₺</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3"><strong>TOPLAM</strong></td>
          <td><strong><?= number_format($total,2) ?> ₺</strong></td>
        </tr>
      </tfoot>
    </table>
      </div>

      <p class="text-center"><strong>Toplam Tutar:</strong> <?= number_format($total,2) ?> ₺</p>
    </div>

    <div class="col-md-6">
      <!-- Ödeme Formu -->
      <form method="post" class="payment-form shadow-lg p-4 rounded-4">
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
  </div>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
