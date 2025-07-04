<?php
session_start();
// Yetki kontrolü
if ($_SESSION['user_role_id'] != 1) { // 1 = Admin rolü ID'si
    // Admin değilse, panele yönlendir ve işlemi durdur.
    header('Location: dashboard.php');
    exit('Bu sayfaya erişim yetkiniz yok.');
}

// Güvenlik 1: Giriş yapılmamışsa işlemi durdur ve login'e yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Güvenlik 2: Yönetici rolü kontrolü (Örnek: Sadece Admin silebilir)
// Gelecekte yetkilendirme için bu bloğu aktif edebilirsiniz.
/*
if ($_SESSION['user_role_id'] != 1) { // 1 = Admin rolü ID'si olsun
    // Yetkisi yoksa, bir hata mesajı gösterip durdurabilir veya ana sayfaya yönlendirebiliriz.
    die('Bu işlemi yapmaya yetkiniz yok.'); 
}
*/

// Veritabanı bağlantısı
require 'config/database.php';

// Güvenlik 3: Silinecek müşterinin ID'si gönderildi mi ve sayısal mı diye kontrol et
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    $customer_id = $_GET['id'];

    // Veritabanından silme sorgusu
    // SQL Injection'a karşı korumalı, güvenli yöntem
    $sql = "DELETE FROM customers WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    try {
        // Sorguyu çalıştır
        $stmt->execute([$customer_id]);
        
        // İşlem başarılı. Kullanıcıyı başarı mesajıyla müşteri listesine geri yönlendir.
        // Gelecekte bu şekilde mesajlar gösterebiliriz:
        // $_SESSION['success_message'] = "Müşteri başarıyla silindi.";

    } catch (PDOException $e) {
        // Bir hata oluşursa (örn: bu müşteriye ait bir teklif varsa ve silinemiyorsa)
        // Kullanıcıyı bir hata mesajıyla geri yönlendir.
        // die("HATA: Müşteri silinemedi. Bu müşteriye ait teklifler olabilir. " . $e->getMessage());
    }

}

// Her durumda (ID gelmese de, işlem bitse de) müşteri listesine geri dön
header("Location: musteri_listesi.php");
exit();

?>