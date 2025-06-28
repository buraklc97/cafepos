<?php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin', 'Garson', 'Garson (Yetkili)']);
$role = currentUserRole();

if ($role === 'Admin' || $role === 'Garson (Yetkili)') {
    $tables = $pdo->query("SELECT id, status, opened_at FROM pos_tables ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $tables = $pdo->query("SELECT id, status, opened_at FROM pos_tables WHERE id != 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode([
    'version' => sha1(json_encode($tables)),
    'tables' => $tables
]);