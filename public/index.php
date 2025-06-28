<?php
// public/index.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';

// Eğer kullanıcı giriş yapmışsa rolüne göre ilgili sayfaya yönlendir
if (isLoggedIn()) {
    if (currentUserRole() === 'Admin') {
        header('Location: dashboard.php');
    } else {
        header('Location: pos.php');
    }
    exit;
}

include __DIR__ . '/../src/header.php';
?>

<div class="d-flex align-items-center justify-content-center" style="min-height:70vh;">
  <div class="text-center">
    <h1>Cafe POS Sistemi</h1>
    <p class="lead">Lütfen giriş yapın.</p>
    <a href="login.php" class="btn btn-primary">Yetkili Girişi Yap</a>
  </div>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
