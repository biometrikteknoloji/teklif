<?php
session_start();

// Veritabanından token'ı temizle (opsiyonel ama daha güvenli)
if (isset($_SESSION['user_id'])) {
    require 'config/database.php';
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Session'ı yok et
$_SESSION = array();
session_destroy();

// Çerezi sil
if (isset($_COOKIE['remember_user_token'])) {
    unset($_COOKIE['remember_user_token']); 
    setcookie('remember_user_token', '', time() - 3600, '/');
}

// Giriş sayfasına yönlendir
header("location: login.php");
exit;
?>