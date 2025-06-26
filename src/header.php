<?php
require __DIR__ . '/../config/init.php';

$currentUrl = strtok($_SERVER['REQUEST_URI'], '?');

if (!empty($_SESSION['user_id']) && isset($_GET['theme']) && in_array($_GET['theme'], ['light','dark'], true)) {
    $newTheme = $_GET['theme'];
    $up = $pdo->prepare(
        "INSERT INTO user_settings (user_id, theme) VALUES (?,?) ON DUPLICATE KEY UPDATE theme = VALUES(theme)"
    );
    $up->execute([ $_SESSION['user_id'], $newTheme ]);
    header('Location: ' . $currentUrl);
    exit;
}
$username = $_SESSION['username'] ?? '';
$role     = $_SESSION['user_role'] ?? '';

$currentTheme = 'light';
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT theme FROM user_settings WHERE user_id = ?');
    $stmt->execute([ $_SESSION['user_id'] ]);
    $currentTheme = $stmt->fetchColumn() ?: 'light';
}
$toggleTheme = $currentTheme === 'light' ? 'dark' : 'light';
?>
<!DOCTYPE html>
<!-- <html lang="tr" data-theme="<?= htmlspecialchars($currentTheme) ?>"> -->
<html lang="tr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <title>Cafe POS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <!-- Bootstrap & Material Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/styleson.css?v=20252320625">
  <script>
    // Disable pinch zoom on mobile devices
    document.addEventListener('touchstart', function (e) {
      if (e.touches.length > 1) {
        e.preventDefault();
      }
    }, { passive: false });
    document.addEventListener('touchmove', function (e) {
      if (e.touches.length > 1) {
        e.preventDefault();
      }
    }, { passive: false });

    // Prevent double-tap zoom on iOS
    var lastTouchEnd = 0;
    document.addEventListener('touchend', function (e) {
      var now = Date.now();
      if (now - lastTouchEnd <= 300) {
        e.preventDefault();
      }
      lastTouchEnd = now;
    }, false);
  </script>
</head>
<body>
<?php if ($username): ?>
<!-- Mobil ve Tablet için offcanvas menü -->
<nav class="navbar navbar-dark bg-primary sticky-top shadow-sm py-2">
  <div class="container-fluid">
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sideMenu">
      <span class="material-icons">menu</span>
    </button>
    <span class="navbar-brand mx-auto"><?= htmlspecialchars($role === 'Admin' ? 'Cafe POS (Admin)' : 'Cafe POS') ?></span>
    <!-- Tema Switch + Kullanıcı -->
    <form method="get" action="<?= htmlspecialchars($currentUrl) ?>" class="d-flex align-items-center m-0">
      <input type="hidden" name="theme" value="<?= $toggleTheme ?>">
      <span class="material-icons me-1">account_circle</span>
      <span class="text-white d-none d-md-inline"><?= htmlspecialchars($username) ?></span>
    </form>
  </div>
</nav>

<!-- Offcanvas Menü -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sideMenu">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Menü</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <a href="pos.php" class="nav-link"><span class="material-icons me-1">table_restaurant</span> Masalar</a>
    <?php if ($role === 'Admin'): ?>
      <a href="dashboard.php" class="nav-link"><span class="material-icons me-1">dashboard</span> Ana Sayfa</a>
      <a href="tables.php" class="nav-link"><span class="material-icons me-1">view_module</span> Masa Yönetimi</a>
      <a href="categories.php" class="nav-link"><span class="material-icons me-1">category</span> Kategoriler</a>
      <a href="products.php" class="nav-link"><span class="material-icons me-1">restaurant_menu</span> Ürünler</a>
      <a href="shifts.php" class="nav-link"><span class="material-icons me-1">schedule</span> Gün Başı/Sonu</a>
      <a href="logs.php" class="nav-link"><span class="material-icons me-1">event_note</span> Loglar</a>
      <a href="users.php" class="nav-link"><span class="material-icons me-1">group</span> Kullanıcılar</a>
    <?php endif; ?>
    <a href="logout.php" class="nav-link text-danger mt-2"><span class="material-icons me-1">logout</span> Çıkış</a>
  </div>
</div>
<?php endif; ?>
<main class="container my-3">
