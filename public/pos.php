<?php
// public/pos.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson', 'Garson (Yetkili)']);
$role = currentUserRole();

// Masaları çek
$query = "SELECT * FROM pos_tables";
if ($role !== 'Admin' && $role !== 'Garson (Yetkili)') {
    $query .= " WHERE id != 1";
}
$query .= " ORDER BY id";
$tables = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
$tables = array_values($tables);
// Masalarin son durumunu kontrol icin hash. Versiyon sadece id, status ve opened_at alanlarina gore hesaplanir
$versionData = array_map(function ($t) {
    return [
        'id' => $t['id'],
        'status' => $t['status'],
        'opened_at' => $t['opened_at'],
    ];
}, $tables);
$tablesVersion = sha1(json_encode($versionData));
// Açık siparişlerdeki toplam tutarları çek
$totStmt = $pdo->query(
    "SELECT o.table_id, SUM(oi.quantity * oi.unit_price) AS total
       FROM orders o
       JOIN order_items oi ON oi.order_id = o.id
      WHERE o.status = 'open'
      GROUP BY o.table_id"
);
$totals = $totStmt->fetchAll(PDO::FETCH_KEY_PAIR);
foreach ($tables as &$tb) {
    $tb['total'] = isset($totals[$tb['id']]) ? (float)$totals[$tb['id']] : 0;
}
unset($tb);
// Kasa kaydini ayir
$kasa = null;
foreach ($tables as $idx => $tb) {
    if ($tb['id'] == 1) {
        $kasa = $tb;
        unset($tables[$idx]);
        $tables = array_values($tables);
        break;
    }
}

// Kaç masa dolu? Birleştirme butonuna izin vermek için hesapla
$occupiedCount = 0;
foreach ($tables as $tb) {
    if ($tb['status'] === 'occupied') {
        $occupiedCount++;
    }
}

$partial = isset($_GET['partial']) && $_GET['partial'] == '1';
if (!$partial) {
    include __DIR__ . '/../src/header.php';
}
?>
<div id="tablesWrapper">
<?php if ($kasa): ?>
  <div class="row justify-content-center mb-4">
    <div class="col-6 col-sm-4 col-md-3 col-lg-2">
      <div class="card h-100 shadow-sm rounded-4 text-center position-relative table-card"
           data-id="1" data-status="<?= $kasa['status'] ?>" style="cursor:pointer;"
           onclick="window.location='order.php?table=1'">
        <div class="card-body py-4 px-2 d-flex flex-column justify-content-center align-items-center">
          <span class="material-icons mb-2" style="font-size:2.7rem; color:#ff9800 !important;">point_of_sale</span>
          <div class="fw-bold" style="font-size:1.1rem;"><?= htmlspecialchars($kasa['name']) ?></div>
    <?php if ($kasa["total"] > 0): ?>
      <div class="mt-2"><span class="badge bg-primary"><?= number_format($kasa["total"], 2) ?> ₺</span></div>
    <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<h2 class="my-3 text-center">Masalar</h2>
<div id="tablesRow" class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 row-cols-xl-8 row-cols-xxl-10 g-3">
  <?php foreach ($tables as $t): ?>
    <div class="col">
        <div class="card h-100 shadow-sm rounded-4 text-center position-relative table-card"
             data-id="<?= $t['id'] ?>"
             data-status="<?= $t['status'] ?>"
             data-opened-at="<?= htmlspecialchars($t['opened_at'] ?? '') ?>"
             style="cursor:pointer;"
             onclick="window.location='order.php?table=<?= $t['id'] ?>'">
          <div class="card-body py-4 px-2 d-flex flex-column justify-content-center align-items-center">
            <span class="material-icons mb-2" style="font-size:2.7rem; color:<?= $t['status']=='empty' ? '#43a047' : '#e65100' ?>;">
              <?= $t['status']=='empty' ? 'event_seat' : 'groups' ?>
            </span>
            <div class="fw-bold" style="font-size:1.1rem;"><?= htmlspecialchars($t['name']) ?></div>
            <div>
              <span class="badge px-3 py-2 mt-2 <?= $t['status']=='empty' ? 'bg-success' : 'bg-danger' ?>">
                <?= $t['status']=='empty' ? 'Boş' : 'Dolu' ?>
              </span>
            </div>
            <?php if ($t['status'] === 'occupied' && !empty($t['opened_at'])): ?>
              <div class="table-timer badge bg-danger text-white small mt-2" data-opened-at="<?= htmlspecialchars($t['opened_at']) ?>"></div>
            <?php endif; ?>
    <?php if ($t["total"] > 0): ?>
      <div class="mt-2"><span class="badge bg-primary"><?= number_format($t["total"], 2) ?> ₺</span></div>
    <?php endif; ?>
          </div>
          <?php if ($t['status'] === 'occupied'): ?>
          <div class="card-footer bg-transparent border-0 d-flex flex-row flex-lg-column justify-content-center align-items-center gap-2 py-3"
               style="pointer-events:auto;" onclick="event.stopPropagation();">
            <a href="transfer.php?from=<?= $t['id'] ?>" class="btn btn-warning btn-sm px-3">
              <span class="material-icons align-middle" style="font-size:1.1em; opacity:1; color:inherit;">swap_horiz</span>
              <span class="d-none d-sm-inline"> Taşı</span>
            </a>
            <?php if ($occupiedCount > 1): ?>
              <a href="merge.php?source_table=<?= $t['id'] ?>" class="btn btn-info btn-sm px-3 text-white">
                <span class="material-icons align-middle" style="font-size:1.1em; opacity:1; color:inherit;">call_merge</span>
                <span class="d-none d-sm-inline"> Birleştir</span>
              </a>
            <?php else: ?>
              <a href="#" class="btn btn-info btn-sm px-3 text-white disabled" tabindex="-1" aria-disabled="true" style="pointer-events:none;">
                <span class="material-icons align-middle" style="font-size:1.1em; opacity:1; color:inherit;">call_merge</span>
                <span class="d-none d-sm-inline"> Birleştir</span>
              </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        </div>
    </div>
  <?php endforeach; ?>
</div>
</div>
<?php if (!$partial) { include __DIR__ . '/../src/footer.php'; ?>

<div id="pos-data" data-tables-version="<?= $tablesVersion ?>" style="display:none;"></div>
<script src="/assets/js/pos.js"></script>
<?php } ?>