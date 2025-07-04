<?php
session_start();

// 1. GÜVENLİK KONTROLLERİ
// Sadece giriş yapmış ve rolü Admin olanlar bu işlemi yapabilir
if (!isset($_SESSION['user_id']) || $_SESSION['user_role_id'] != 1) {
    header('Location: dashboard.php');
    exit('Yetkisiz Erişim.');
}

// URL'den silinecek kullanıcının ID'sini al
$id_to_delete = $_GET['id'] ?? null;

// ID geçerli mi ve sayısal mı diye kontrol et
if (!$id_to_delete || !is_numeric($id_to_delete)) {
    // Geçersiz ID ise, hata mesajı ile listeye yönlendir
    header('Location: kullanici_listesi.php?status=invalid_id');
    exit();
}

// Bir kullanıcının kendi hesabını silmesini engelle
if ($id_to_delete == $_SESSION['user_id']) {
    // Kendi kendini silemezse, hata mesajı ile listeye yönlendir
    header('Location: kullanici_listesi.php?status=self_delete_error');
    exit();
}


// 2. VERİTABANI İŞLEMİ
require 'config/database.php';
require 'core/functions.php'; // add_log fonksiyonu için

try {
    // Önce silinecek kullanıcının adını loglamak için alalım
    $stmt_user = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_user->execute([$id_to_delete]);
    $username_to_delete = $stmt_user->fetchColumn();

    // Silme sorgusunu hazırla ve çalıştır
    $stmt_delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete->execute([$id_to_delete]);

    // Silme işlemi başarılı olursa log kaydı oluştur
    if ($stmt_delete->rowCount() > 0) {
        add_log($pdo, 'KULLANICI SİLİNDİ', 'Kullanıcı Adı: ' . $username_to_delete . ' (ID: ' . $id_to_delete . ')');
        // Başarı mesajı ile listeye yönlendir
        header('Location: kullanici_listesi.php?status=deleted');
        exit();
    } else {
        // Silinecek kullanıcı bulunamadıysa (zaten silinmiş olabilir)
        header('Location: kullanici_listesi.php?status=not_found');
        exit();
    }

} catch (PDOException $e) {
    // Veritabanı hatası olursa (örneğin, bu kullanıcıya ait teklifler olduğu için silinemiyorsa)
    // Gerçek hata mesajını loglayabilir veya daha genel bir hata gösterebiliriz.
    // die("Veritabanı Hatası: " . $e->getMessage());
    header('Location: kullanici_listesi.php?status=db_error');
    exit();
}