<?php
// public/users.php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin']);  // Sadece Admin erişebilir

// Kullanıcıları çek
$stmt = $pdo->query("SELECT u.id, u.username, u.role_id, r.name AS role
                      FROM users u
                 JOIN roles r ON u.role_id = r.id
                 ORDER BY u.username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rolleri çek
$roles = $pdo->query("SELECT id, name FROM roles ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Yeni kullanıcı ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['username']) && !empty($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);  // Şifreyi hashle
    $role_id  = (int)$_POST['role_id'];
    
    // Kullanıcıyı eklerken doğru sütun adını kullanıyoruz
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password, $role_id]);

    // Loglama
    require __DIR__ . '/../src/logger.php';
    logAction('add_user', "Yeni kullanıcı eklendi: {$username}");

    header('Location: users.php');
    exit;
}

// Kullanıcıyı silme
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Silme işlemi
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    // Loglama
    require __DIR__ . '/../src/logger.php';
    logAction('delete_user', "Kullanıcı silindi: {$id}");

    header('Location: users.php');
    exit;
}

include __DIR__ . '/../src/header.php';
?>

<h1 class="text-center mb-4">Kullanıcı Yönetimi</h1>

<!-- Yeni Kullanıcı Ekleme Formu -->
<form method="post" class="shadow-lg p-4 rounded-4 mb-4">
  <div class="mb-4">
    <label for="username" class="form-label">Kullanıcı Adı:</label>
    <input type="text" name="username" id="username" class="form-control" required>
  </div>
  <div class="mb-4">
    <label for="password" class="form-label">Şifre:</label>
    <input type="password" name="password" id="password" class="form-control" required>
  </div>
  <div class="mb-4">
    <label for="role_id" class="form-label">Rol Seç:</label>
    <select name="role_id" id="role_id" class="form-select" required>
      <option value="">Seçiniz</option>
      <?php foreach ($roles as $r): ?>
        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" class="btn btn-primary btn-lg w-100">Kullanıcı Ekle</button>
</form>

<!-- Kullanıcıları Listele -->
<h2 class="mb-3">Mevcut Kullanıcılar</h2>
<div class="table-responsive">
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>ID</th><th>Kullanıcı Adı</th><th>Rol</th><th>İşlemler</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= htmlspecialchars($user['id']) ?></td>
          <td><?= htmlspecialchars($user['username']) ?></td>
          <td><?= htmlspecialchars($user['role']) ?></td>
          <td>
            <a href="user_edit.php?id=<?= $user['id'] ?>" class="btn btn-warning btn-sm">Düzenle</a>
            <a href="?delete=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">Sil</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../src/footer.php'; ?>
