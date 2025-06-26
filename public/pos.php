<?php
// public/pos.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson', 'Garson (Yetkili)']);

// Masaları çek
$stmt = $pdo->query("SELECT * FROM pos_tables ORDER BY id");
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../src/header.php';
?>
<h2 class="my-3 text-center">Masalar</h2>
<div class="row g-3">
  <?php foreach ($tables as $t): ?>
    <div class="col-6 col-sm-6 col-md-4 col-lg-3">
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
        </div>
        <?php if ($t['status'] === 'occupied'): ?>
        <div class="card-footer bg-transparent border-0 d-flex justify-content-center gap-2 py-3"
             style="pointer-events:auto;" onclick="event.stopPropagation();">
          <a href="transfer.php?from=<?= $t['id'] ?>" class="btn btn-warning btn-sm px-3">
            <span class="material-icons align-middle" style="font-size:1.1em; opacity:1; color:inherit;">swap_horiz</span>
            <span class="d-none d-sm-inline"> Taşı</span>
          </a>
          <a href="merge.php?source_table=<?= $t['id'] ?>" class="btn btn-info btn-sm px-3 text-white">
            <span class="material-icons align-middle" style="font-size:1.1em; opacity:1; color:inherit;">call_merge</span>
            <span class="d-none d-sm-inline"> Birleştir</span>
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../src/footer.php'; ?>

<script>
  // ISO formatı ile gelen timestamp'leri JS Date objesine dönüştürmek için
  function parseDateTime(dt) {
    return new Date(dt.replace(' ', 'T'));
  }
  function updateTimers() {
    document.querySelectorAll('.table-timer').forEach(el => {
      const openedAt = parseDateTime(el.dataset.openedAt);
      const now = new Date();
      let diff = Math.floor((now - openedAt) / 1000);
      const hours = Math.floor(diff / 3600);
      diff %= 3600;
      const minutes = Math.floor(diff / 60);
      const seconds = diff % 60;
      if (hours > 0) {
        el.textContent = `${hours} saat ${minutes} dk ${seconds} sn`;
      } else {
        el.textContent = `${minutes} dk ${seconds} sn`;
      }
    });
  }
  document.addEventListener('DOMContentLoaded', () => {
    updateTimers();
    setInterval(updateTimers, 1000);
    initTableWatcher();
  });

  const tableStates = {};
  function initTableWatcher() {
    document.querySelectorAll('.table-card').forEach(card => {
      tableStates[card.dataset.id] = {
        status: card.dataset.status,
        opened_at: card.dataset.openedAt || ''
      };
    });
    setInterval(checkTableUpdates, 5000);
  }

  async function checkTableUpdates() {
    try {
      const resp = await fetch('api_tables_status.php');
      const data = await resp.json();
      let changed = false;
      data.forEach(t => {
        const prev = tableStates[t.id];
        const opened = t.opened_at || '';
        if (!prev || prev.status !== t.status || prev.opened_at !== opened) {
          changed = true;
        }
      });
      if (changed) {
        location.reload();
      }
    } catch (e) {
      console.error(e);
    }
  }
</script>
