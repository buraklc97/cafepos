<?php
// public/settings.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin','Garson']);

$success = '';
$userId  = $_SESSION['user_id'];

// Tema değeri POST veya GET ile gelebilir
if (
    ($_SERVER['REQUEST_METHOD']==='POST'  && in_array($_POST['theme'] ?? '', ['light','dark'], true))
 || ($_SERVER['REQUEST_METHOD']==='GET'   && in_array($_GET['theme']  ?? '', ['light','dark'], true))
) {
    $theme = $_REQUEST['theme'];

    // Veritabanına kaydet
    $up = $pdo->prepare("
      INSERT INTO user_settings (user_id, theme) 
      VALUES (?,?)
      ON DUPLICATE KEY UPDATE theme = VALUES(theme)
    ");
    $up->execute([ $userId, $theme ]);

    $success = 'Tema seçiminiz kaydedildi.';
    // Eğer GET ile geldiyse referer’a geri gönder
    if ($_SERVER['REQUEST_METHOD']==='GET') {
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
        exit;
    }
}

// Mevcut tema tercihini tekrar çekelim
$stmt    = $pdo->prepare("SELECT theme FROM user_settings WHERE user_id = ?");
$stmt->execute([ $userId ]);
$current = $stmt->fetchColumn() ?: 'light';

include __DIR__ . '/../src/header.php';
?>
  <h1>Ayarlar</h1>
  <?php if ($success): ?>
    <p style="color:green;"><?= htmlspecialchars($success) ?></p>
  <?php endif; ?>
  <form method="post">
    <fieldset>
      <legend>Tema Seçimi</legend>
      <label>
        <input type="radio" name="theme" value="light"
          <?= $current==='light' ? 'checked' : '' ?>> 
        Açık (Light)
      </label><br>
      <label>
        <input type="radio" name="theme" value="dark"
          <?= $current==='dark' ? 'checked' : '' ?>> 
        Koyu (Dark)
      </label>
    </fieldset>
    <button type="submit">Kaydet</button>
  </form>
<?php include __DIR__ . '/../src/footer.php'; ?>
