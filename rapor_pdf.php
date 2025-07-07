<?php
session_start();
if (!isset($_SESSION['user_id'])) { exit('Yetkisiz Erişim'); }

// === DEĞİŞİKLİK BURADA: DOĞRU YOL TANIMLAMASI ===
define('PROJECT_ROOT', dirname(__FILE__));

require_once PROJECT_ROOT . '/lib/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;
require_once PROJECT_ROOT . '/config/database.php';

// Linkten gelen tarihleri al
$baslangic_tarihi = $_GET['baslangic_tarihi'] ?? '';
$bitis_tarihi = $_GET['bitis_tarihi'] ?? '';

if (empty($baslangic_tarihi) || empty($bitis_tarihi)) {
    exit('Lütfen geçerli bir tarih aralığı seçin.');
}

// Raporlar sayfasındaki sorgunun aynısı
$sql = "SELECT p.proposal_date, p.proposal_no, c.unvan, ps.status_name, p.grand_total, p.currency
        FROM proposals p
        JOIN customers c ON p.customer_id = c.id
        JOIN proposal_statuses ps ON p.status_id = ps.id
        WHERE p.proposal_date BETWEEN ? AND ? ORDER BY p.proposal_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$baslangic_tarihi, $bitis_tarihi]);
$rapor_sonuclari = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Özetleri hesapla
$toplamlar = ['TL' => 0, 'USD' => 0, 'EUR' => 0];
$durum_sayilari = [];
foreach ($rapor_sonuclari as $sonuc) {
    if (isset($toplamlar[$sonuc['currency']])) { $toplamlar[$sonuc['currency']] += $sonuc['grand_total']; }
    $durum_adi = $sonuc['status_name'];
    if (!isset($durum_sayilari[$durum_adi])) { $durum_sayilari[$durum_adi] = 0; }
    $durum_sayilari[$durum_adi]++;
}

$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$theme_color = $settings['proposal_theme_color'] ?? '#004a99';


ob_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Teklif Raporu</title>
    <style>
        /* ... (CSS stilleri aynı, değişiklik yok) ... */
    </style>
</head>
<body>
    <!-- ... (HTML içeriği aynı, değişiklik yok) ... -->
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('chroot', PROJECT_ROOT); 
$options->set('defaultFont', 'dejavu sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dosya_adi = "Rapor_" . $baslangic_tarihi . "_" . $bitis_tarihi . ".pdf";
$dompdf->stream($dosya_adi, ["Attachment" => false]); 
?>