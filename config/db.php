<?php
// config/db.php
$dbHost = 'localhost';
$dbName = 'codebbsoftware_cafev2';
$dbUser = 'codebbsoftware_cafev2';
$dbPass = 'Q].&!UHC[i8[frdi';
$dsn    = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log('DB bağlantı hatası: ' . $e->getMessage());
    die('Veritabanı bağlantısında sorun var.');
}
