<?php
// public/user_edit.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin']);  // Sadece Admin erişebilir

// Kullanıcıyı çek
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: users.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

// Rolleri çek
$roles = $pdo->query("SELECT id, name FROM roles ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Kullanıcıyı güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $role_id  = (int)$_POST['role_id'];

    // Şifreyi değiştirmek isterseniz, şifreyi hashleyip güncelleyebilirsiniz
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, password_hash = ?, role_id = ? WHERE id = ?");
        $stmt->execute([$username, $password, $role_id, $id]);
    } else {
        // Şifreyi değiştirmiyorsak sadece diğer bilgileri güncelle
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role_id = ? WHERE id = ?");
        $stmt->execute([$username, $role_id, $id]);
    }

    // Loglama
    require __DIR__ . '/../src/logger.php';
    logAction('edit_user', "Kullanıcı düzenlendi: {$username}");

    header('Location: users.php');
    exit;
}

include __DIR__ . '/../src/header.php';
?>

<h1 class="text-center mb-4">Kullanıcı Düzenle</h1>

<!-- Kullanıcı Düzenleme Formu -->
<form method="post" class="shadow-lg p-4 rounded-4 mb-4">
  <div class="mb-4">
    <label for="username" class="form-label">Kullanıcı Adı:</label>
    <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
  </div>
  <div class="mb-4">
    <label for="password" class="form-label">Şifre (Boş bırakmak şifreyi değiştirmez):</label>
    <input type="password" name="password" id="password" class="form-control">
  </div>
  <div class="mb-4">
    <label for="role_id" class="form-label">Rol Seç:</label>
    <select name="role_id" id="role_id" class="form-select" required>
      <option value="">Seçiniz</option>
      <?php foreach ($roles as $r): ?>
        <option value="<?= $r['id'] ?>" <?= $r['id'] === $user['role_id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" class="btn btn-primary btn-lg w-100">Kullanıcıyı Güncelle</button>
</form>

<?php include __DIR__ . '/../src/footer.php'; ?>
