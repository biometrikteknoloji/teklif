<?php
session_start();
// Yetki kontrolü
if ($_SESSION['user_role_id'] != 1) { // 1 = Admin rolü ID'si
    // Admin değilse, panele yönlendir ve işlemi durdur.
    header('Location: dashboard.php');
    exit('Bu sayfaya erişim yetkiniz yok.');
}

// Güvenlik: Giriş yapılmamışsa işlemi durdur
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'config/database.php';

// Silinecek ürünün ID'si gönderildi mi ve sayısal mı diye kontrol et
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    $product_id = $_GET['id'];

    $pdo->beginTransaction();
    try {
        // 1. Silmeden önce ürünün fotoğraf yolunu alalım
        $stmt_select = $pdo->prepare("SELECT fotograf_yolu FROM products WHERE id = ?");
        $stmt_select->execute([$product_id]);
        $urun = $stmt_select->fetch();
        
        $fotograf_yolu = $urun ? $urun['fotograf_yolu'] : null;

        // 2. Bu ürüne ait fiyatları `product_prices` tablosundan sil
        // Bu adım, foreign key kısıtlaması (ON DELETE CASCADE) varsa otomatik de yapılabilir,
        // ama manuel yapmak daha güvenlidir.
        $stmt_delete_prices = $pdo->prepare("DELETE FROM product_prices WHERE product_id = ?");
        $stmt_delete_prices->execute([$product_id]);

        // 3. Ana ürünü `products` tablosundan sil
        $stmt_delete_product = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt_delete_product->execute([$product_id]);

        // 4. Sunucudaki fotoğraf dosyasını sil (eğer varsa ve yolu boş değilse)
        if ($fotograf_yolu && file_exists($fotograf_yolu)) {
            unlink($fotograf_yolu);
        }

        // Tüm işlemler başarılıysa, transaction'ı onayla
        $pdo->commit();

    } catch (Exception $e) {
        // Bir hata olursa, tüm işlemleri geri al
        $pdo->rollBack();
        // Geliştirme aşamasında hatayı görmek için
        // die("HATA: Ürün silinemedi. " . $e->getMessage());
    }
}

// Her durumda ürün listesine geri dön
header("Location: urun_listesi.php");
exit();
?>