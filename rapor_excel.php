<?php
session_start();
if (!isset($_SESSION['user_id'])) { exit('Yetkisiz Erişim'); }
require 'config/database.php';

// Linkten gelen tarihleri al
$baslangic_tarihi = $_GET['baslangic'] ?? '';
$bitis_tarihi = $_GET['bitis'] ?? '';

if (empty($baslangic_tarihi) || empty($bitis_tarihi)) {
    exit('Lütfen geçerli bir tarih aralığı seçin.');
}

// Raporlar sayfasındaki sorgunun aynısını çalıştır
$sql = "SELECT p.proposal_date, p.proposal_no, c.unvan, ps.status_name, p.grand_total, p.currency
        FROM proposals p
        JOIN customers c ON p.customer_id = c.id
        JOIN proposal_statuses ps ON p.status_id = ps.id
        WHERE p.proposal_date BETWEEN ? AND ? ORDER BY p.proposal_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$baslangic_tarihi, $bitis_tarihi]);
$sonuclar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Excel Çıktısı için Header'ları Ayarla
$dosya_adi = "Teklif_Raporu_" . $baslangic_tarihi . "_" . $bitis_tarihi . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$dosya_adi\"");
header("Pragma: no-cache");
header("Expires: 0");

// Başlık satırını oluştur
$basliklar = array('Teklif Tarihi', 'Teklif No', 'Müşteri', 'Durum', 'Tutar', 'Para Birimi');
echo implode("\t", $basliklar) . "\n";

// Veri satırlarını oluştur
if (count($sonuclar) > 0) {
    foreach ($sonuclar as $satir) {
        // Türkçe karakter sorununu çözmek için mb_convert_encoding gerekebilir, şimdilik basit tutalım.
        // Tarih formatını düzelt
        $satir['proposal_date'] = date('d.m.Y', strtotime($satir['proposal_date']));
        // Sayı formatını düzelt (Excel'in sayı olarak algılaması için virgülü noktaya çevir)
        $satir['grand_total'] = number_format($satir['grand_total'], 2, '.', '');
        
        echo implode("\t", array_values($satir)) . "\n";
    }
}
exit();
?>