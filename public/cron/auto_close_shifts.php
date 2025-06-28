<?php
// public/cron/auto_close_shifts.php

// PHP CLI’den çalışacağı için yolunuzu tam verin:
require __DIR__ . '/../../config/init.php';

// Otomatik kapanacaklar:
// açık kalan shift’ler (closed_at NULL), önceden açılmış (örneğin 1 saatten eski)
$sql = "
    UPDATE shifts
       SET closed_at = NOW(),
           closed_by = NULL,
           auto_closed = 1
     WHERE closed_at IS NULL
       AND opened_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
";
$stmt = $pdo->prepare($sql);
$stmt->execute();

// İsterseniz burada log’a da yazabilirsiniz:
file_put_contents(__DIR__ . '/auto_close.log',
    date('[Y-m-d H:i:s] ') 
    . $stmt->rowCount() 
    . " shift(ler) otomatik kapatıldı.\n",
    FILE_APPEND
);
