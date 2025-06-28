<?php
// public/debug-login.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config/init.php';

// Test kullanıcı bilgileri
$username = 'admin';
$password = 'admin123';

// Kullanıcıyı veritabanından çekelim
$stmt = $pdo->prepare("
  SELECT u.id, u.username, u.password_hash, r.name AS role
    FROM users u
    JOIN roles r ON u.role_id = r.id
   WHERE u.username = :username
   LIMIT 1
");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();

echo "<pre>";
if (!$user) {
    echo "Kullanıcı bulunamadı!\n";
    exit;
}

echo "Kullanıcı adı: " . $user['username'] . "\n";
echo "Veritabanındaki hash: " . $user['password_hash'] . "\n\n";

// password_verify sonucu
$ver = password_verify($password, $user['password_hash']);
echo "password_verify('{$password}', hash) => ";
var_export($ver);
echo "\n</pre>";
