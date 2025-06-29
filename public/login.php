<?php
// public/login.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';

// Kullanıcı zaten giriş yaptıysa rolüne göre yönlendir
if (isLoggedIn()) {
  if (currentUserRole() === 'Admin') {
    header('Location: dashboard.php');
  } else {
    header('Location: pos.php');
  }
  exit;
}

$error = '';
$rememberChecked = true; // Beni Hatırla varsayılan olarak işaretli
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $rememberChecked = isset($_POST['remember']);
  if (attemptLogin($_POST['username'], $_POST['password'])) {
    // Başarılı girişi logla
    require __DIR__ . '/../src/logger.php';
    logAction('login', 'Kullanıcı giriş yaptı: ' . $_POST['username']);

    if ($rememberChecked) {
      // Oturum çerezinin ömrünü 24 saat yap ve çerezi güvenli hale getir
      setcookie(
        session_name(),
        session_id(),
        [
          'expires'  => time() + 86400,
          'path'     => '/',
          'secure'   => true,
          'httponly' => true,
          'samesite' => 'Strict'
        ]
      );
    }

    header('Location: pos.php');
    exit;
  } else {
    $error = 'Kullanıcı adı veya şifre hatalı.';
  }
}

include __DIR__ . '/../src/header.php';
?>
<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
  <form method="post" class="shadow-lg p-4 rounded-4 w-100" style="max-width:420px;background:var(--container-bg);color:var(--text);border:1px solid var(--border-color);">
  <h1 class="text-center mb-4">Giriş Yap</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
    <div class="mb-4">
      <label for="username" class="form-label">Kullanıcı Adı:</label>
      <input type="text" name="username" id="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    </div>

    <div class="mb-4">
      <label for="password" class="form-label">Şifre:</label>
      <input type="password" name="password" id="password" class="form-control" required>
    </div>

    <div class="form-check mb-4">
      <input type="checkbox" name="remember" id="remember" class="form-check-input"<?= $rememberChecked ? ' checked' : '' ?>>
      <label for="remember" class="form-check-label">Beni Hatırla</label>
    </div>

    <div class="d-grid gap-2">
      <button type="submit" class="btn btn-primary btn-lg">Giriş</button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
