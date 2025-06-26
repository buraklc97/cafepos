<?php
// public/login.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (attemptLogin($_POST['username'], $_POST['password'])) {
    // Başarılı girişi logla
    require __DIR__ . '/../src/logger.php';
    logAction('login', 'Kullanıcı giriş yaptı: ' . $_POST['username']);
    header('Location: pos.php');
    exit;
  } else {
    $error = 'Kullanıcı adı veya şifre hatalı.';
  }
}

include __DIR__ . '/../src/header.php';
?>
<div class="container my-5">
  <h1 class="text-center mb-4">Giriş Yap</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="shadow-lg p-4 rounded-4">
    <div class="mb-4">
      <label for="username" class="form-label">Kullanıcı Adı:</label>
      <input type="text" name="username" id="username" class="form-control" required>
    </div>

    <div class="mb-4">
      <label for="password" class="form-label">Şifre:</label>
      <input type="password" name="password" id="password" class="form-control" required>
    </div>

    <div class="d-grid gap-2">
      <button type="submit" class="btn btn-primary btn-lg">Giriş</button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
