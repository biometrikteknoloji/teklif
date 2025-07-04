<?php
/**
 * Bu dosya, projenin ana giriş noktasıdır.
 * Kullanıcının giriş yapıp yapmadığını kontrol eder ve onu doğru sayfaya yönlendirir.
 */

// Oturumu başlat
session_start();

// Eğer kullanıcı giriş yapmışsa (session'da user_id varsa) dashboard'a yönlendir.
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
} 
// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir.
else {
    header('Location: login.php');
    exit();
}

?>