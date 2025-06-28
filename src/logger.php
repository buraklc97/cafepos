<?php
// src/logger.php
require __DIR__ . '/../config/init.php';

/**
 * Sunucu tarafı log kaydı
 *
 * @param string $action  Kısa kod veya işlem adı (ör. 'login', 'add_table')
 * @param string $details Daha ayrıntılı açıklama
 */
function logAction(string $action, string $details = ''): void {
    global $pdo;
    $userId = $_SESSION['user_id'] ?? null;
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Eğer oturum yoksa user_id NULL geçilsin
    $stmt = $pdo->prepare("
        INSERT INTO logs (user_id, ip_address, action, details)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $ip, $action, $details]);
}
