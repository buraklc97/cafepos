<?php
// public/test-db.php
require __DIR__ . '/../config/init.php';

try {
    $stmt  = $pdo->query("SELECT COUNT(*) AS cnt FROM pos_tables");
    $count = $stmt->fetch()['cnt'];
    echo "Veritabanı bağlantısı OK. Masa sayısı: {$count}";
} catch (Exception $e) {
    echo "DB Testinde hata: " . htmlspecialchars($e->getMessage());
}
