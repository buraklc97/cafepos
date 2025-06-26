<?php
// public/merge.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/logger.php';
requireRole(['Admin','Garson']);

// Mevcut dolu masaları çek (sipariş açık)
$tables = $pdo->query(
    "SELECT t.id, t.name
       FROM pos_tables t
       JOIN orders o ON o.table_id = t.id AND o.status = 'open'"
)->fetchAll(PDO::FETCH_ASSOC);

$error = '';
// Kaynak masa GET parametresinden
$sourceTable = (int)($_GET['source_table'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetTable = (int)($_POST['target_table'] ?? 0);
    if ($sourceTable === $targetTable) {
        $error = 'Kaynak ve hedef masa aynı olamaz.';
    } else {
        // Sipariş ID'lerini al
        $stmt = $pdo->prepare(
            "SELECT id FROM orders WHERE table_id = ? AND status = 'open' LIMIT 1"
        );
        $stmt->execute([$sourceTable]);
        $srcOrderId = $stmt->fetchColumn();
        $stmt->execute([$targetTable]);
        $tgtOrderId = $stmt->fetchColumn();

        if (!$srcOrderId || !$tgtOrderId) {
            $error = 'Her iki masanın da açık siparişi olmalı.';
        } else {
            $pdo->beginTransaction();
            try {
                // 1) Kalemleri hedef siparişe aktar
                $items = $pdo->prepare(
                    "SELECT product_id, quantity, unit_price FROM order_items WHERE order_id = ?"
                );
                $items->execute([$srcOrderId]);
                foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $itm) {
                    $chk = $pdo->prepare(
                        "SELECT id FROM order_items WHERE order_id = ? AND product_id = ? LIMIT 1"
                    );
                    $chk->execute([$tgtOrderId, $itm['product_id']]);
                    if ($row = $chk->fetch(PDO::FETCH_ASSOC)) {
                        $pdo->prepare(
                            "UPDATE order_items SET quantity = quantity + ? WHERE id = ?"
                        )->execute([$itm['quantity'], $row['id']]);
                    } else {
                        $pdo->prepare(
                            "INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                             VALUES (?, ?, ?, ?)"
                        )->execute([ 
                            $tgtOrderId, 
                            $itm['product_id'], 
                            $itm['quantity'], 
                            $itm['unit_price'] 
                        ]);
                    }
                }

                // 2) Birleştirme kaydını ekle
                $pdo->prepare(
                    "INSERT INTO table_merges (source_order_id, target_order_id, merged_by)
                     VALUES (?, ?, ?)"
                )->execute([$srcOrderId, $tgtOrderId, $_SESSION['user_id']]);

                // 3) Kaynak siparişi sil
                $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$srcOrderId]);

                // 4) Kaynak masayı boşalt
                $pdo->prepare(
                    "UPDATE pos_tables SET status='empty', opened_at=NULL WHERE id = ?"
                )->execute([$sourceTable]);

                // 5) Logla
                logAction(
                    'merge_tables',
                    "Masa {$sourceTable} siparişleri masa {$targetTable} ile birleştirildi"
                );

                $pdo->commit();
                header('Location: pos.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Birleştirme sırasında hata: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../src/header.php';
?>
<div class="container my-5">
  <h1 class="text-center mb-4">Masa Birleştir</h1>

  <!-- Hata Mesajı -->
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <!-- Masa Birleştirme Formu -->
  <form method="post" class="shadow-lg p-4 rounded-4">
    <input type="hidden" name="source_table" value="<?= $sourceTable ?>">

    <div class="mb-4">
      <label for="target_table" class="form-label">Hedef Masa:</label>
      <select name="target_table" id="target_table" class="form-select" required>
        <option value="">Seçiniz</option>
        <?php foreach ($tables as $t): ?>
          <?php if ($t['id'] != $sourceTable): ?>
            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (ID <?= $t['id'] ?>)</option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="d-grid gap-2">
      <button type="submit" class="btn btn-primary btn-lg">Birleştir</button>
      <a href="pos.php" class="btn btn-secondary btn-lg">İptal</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
