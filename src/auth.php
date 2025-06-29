<?php
// src/auth.php
require __DIR__ . '/../config/init.php';

/**
 * Giriş yapmaya çalışır.
 * Başarılıysa session’a kullanıcı ve rolünü kaydeder.
 */
function attemptLogin(string $username, string $password): bool {
    global $pdo;
    $sql = "SELECT u.id, u.password_hash, r.name AS role
              FROM users u
              JOIN roles r ON u.role_id = r.id
             WHERE u.username = :username
             LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Session’a kaydet
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $username;
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    return false;
}

/** Mevcut kullanıcı girişli mi? */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/** Mevcut kullanıcının rolü */
function currentUserRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Sayfaya erişim izni kontrolü:
 * - Rol listesinde yoksa yönlendirir veya hata verir.
 */
function requireRole(array $roles) {
    if (!isLoggedIn() || !in_array(currentUserRole(), $roles, true)) {
        header('Location: login.php?error=yetki');
        exit;
    }
}

/** Çıkış işlemi */
function logout(): void {
    session_unset();
    session_destroy();
}
