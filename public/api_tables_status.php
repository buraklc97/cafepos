<?php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson', 'Garson (Yetkili)']);

$tables = $pdo->query("SELECT id, status, opened_at FROM pos_tables ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($tables);
