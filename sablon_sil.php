<?php
session_start();
// Sadece adminlerin silme işlemi yapabildiğinden emin olalım
if (!isset($_SESSION['user_id']) || $_SESSION['user_role_id'] != 1) {
    header('Location: dashboard.php');
    exit('Yetkisiz Erişim.');
}
require 'config/database.php';

$id = $_GET['id'] ?? null;

// ID geçerli mi kontrol et
if (!$id || !is_numeric($id)) {
    header('Location: sablon_listesi.php?status=error');
    exit();
}

// Silme işlemini Transaction ile güvene al
$pdo->beginTransaction();
try {
    // Önce şablona ait tüm kalemleri (items) sil.
    // Bu, FOREIGN KEY kısıtlaması hatası almamızı engeller.
    $stmt_items = $pdo->prepare("DELETE FROM proposal_template_items WHERE template_id = ?");
    $stmt_items->execute([$id]);

    // Sonra ana şablonu sil.
    $stmt_template = $pdo->prepare("DELETE FROM proposal_templates WHERE id = ?");
    $stmt_template->execute([$id]);

    // İşlemler başarılıysa onayla
    $pdo->commit();

    // Kullanıcıyı başarı mesajıyla listeleme sayfasına geri yönlendir
    header('Location: sablon_listesi.php?status=deleted');
    exit();

} catch (Exception $e) {
    // Bir hata olursa işlemleri geri al
    $pdo->rollBack();
    // Hata detayını loglayabilir veya bir hata sayfasına yönlendirebiliriz.
    // Şimdilik basit bir şekilde sonlandıralım.
    die("HATA: Şablon silinemedi. " . $e->getMessage());
}
?>