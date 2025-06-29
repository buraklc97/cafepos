<?php
// public/logout.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';

require __DIR__ . '/../src/logger.php';
logAction('logout', 'Kullanıcı çıkış yaptı: ' . ($_SESSION['username'] ?? ''));

logout();
header('Location: login.php');
exit;
