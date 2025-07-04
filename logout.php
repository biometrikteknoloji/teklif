<?php
// Oturumu başlatmalıyız ki sonlandırabilelim.
session_start();

// Hata ayıklama için ekrana bilgi basalım.
echo "Çıkış işlemi başlatıldı...<br>";
echo "Önceki Oturum Bilgileri: <pre>";
print_r($_SESSION);
echo "</pre>";

// Tüm oturum değişkenlerini temizle.
session_unset();
echo "Oturum değişkenleri (session_unset) temizlendi.<br>";

// Oturumu tamamen yok et.
session_destroy();
echo "Oturum (session_destroy) yok edildi.<br>";

echo "<hr>";
echo "Şimdi giriş sayfasına yönlendiriliyorsunuz...";

// Kullanıcıyı tekrar giriş sayfasına yönlendir.
header('Location: login.php');

// Yönlendirmeden sonra başka bir kodun çalışmasını engelle.
exit();
?>