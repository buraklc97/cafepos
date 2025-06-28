<?php
// config/init.php

// Hata raporlamayı aç (geliştirme aşamasında)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Oturumu başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone ayarı
date_default_timezone_set('Europe/Istanbul');

// PDO ile veritabanı bağlantısı
require __DIR__ . '/db.php';
