<?php
session_start();
if (!isset($_SESSION['user_id'])) { exit('Bu işlemi yapmak için giriş yapmalısınız.'); }

// Projenin kök dizinini tanımla. Bu, resim yollarını bulmak için en güvenilir yöntemdir.
define('PROJECT_ROOT', dirname(__FILE__));

require_once PROJECT_ROOT . '/lib/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;
require_once PROJECT_ROOT . '/config/database.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) { exit('Geçersiz veya eksik Teklif ID.'); }

// Veritabanı Sorguları
$sql_proposal = "SELECT p.*, c.*, u.full_name as user_name FROM proposals p JOIN customers c ON p.customer_id = c.id JOIN users u ON p.user_id = u.id WHERE p.id = ?";
$stmt = $pdo->prepare($sql_proposal);
$stmt->execute([$id]);
$teklif = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$teklif) { exit('Belirtilen ID ile bir teklif bulunamadı.'); }

$stmt_items = $pdo->prepare("SELECT pi.*, pr.fotograf_yolu FROM proposal_items pi LEFT JOIN products pr ON pi.product_id = pr.id WHERE pi.proposal_id = ? ORDER BY pi.id ASC");
$stmt_items->execute([$id]);
$teklif_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

ob_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Teklif - <?php echo htmlspecialchars($teklif['proposal_no']); ?></title>
    <style>
        @page { margin: 0; }
        body { font-family: 'dejavu sans', sans-serif; font-size: 10px; color: #333; margin: 0; }
        header { width: 100%; text-align: center; }
        header img { max-width: 100%; height: auto; }
        footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; }
        footer img { max-width: 100%; }
        main { padding: 25px; padding-top: 15px; }
        .info-table { margin-top: 20px; margin-bottom: 20px; width: 100%; }
        .info-table td { width: 48%; vertical-align: top; }
        .info-table td.spacer { width: 4%; }
        .info-box { border: 1px solid #dee2e6; padding: 15px; height: 110px; }
        .info-box h3 { font-size: 10px; margin-top: 0; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #dee2e6; font-weight: bold; text-transform: uppercase; }
        .items-table { margin-top: 20px; width: 100%; }
        .items-table th, .items-table td { border: 1px solid #dee2e6; padding: 8px; text-align: left; vertical-align: middle; }
        .items-table th { background-color: #f8f9fa; font-weight: bold; }
        .items-table .product-image { width: 40px; height: 40px; object-fit: cover; }
        .totals-section { width: 100%; margin-top: 20px; }
        .totals-table { width: 45%; float: right; }
        .totals-table td { padding: 6px; }
        .totals-table tr td:first-child { text-align: right; font-weight: bold; }
        .totals-table tr td:last-child { text-align: right; }
        .grand-total { font-size: 14px; font-weight: bold; border-top: 1px solid #333; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    <header>
        <?php if (!empty($settings['proposal_header_path']) && file_exists(PROJECT_ROOT . '/' . $settings['proposal_header_path'])): ?>
            <img src="<?php echo PROJECT_ROOT . '/' . $settings['proposal_header_path']; ?>">
        <?php endif; ?>
    </header>

    <footer>
        <?php if (!empty($settings['proposal_footer_path']) && file_exists(PROJECT_ROOT . '/' . $settings['proposal_footer_path'])): ?>
            <img src="<?php echo PROJECT_ROOT . '/' . $settings['proposal_footer_path']; ?>">
        <?php endif; ?>
    </footer>

    <main>
        <h2 style="text-align:center; font-size:18px; margin-top: 15px;">FİYAT TEKLİFİ</h2>

        <table class="info-table">
           <tr>
               <td>
                   <div class="info-box">
                       <h3>Müşteri Bilgileri</h3>
                       <strong><?php echo htmlspecialchars($teklif['unvan']); ?></strong><br>
                       <?php if (!empty($teklif['yetkili_ismi'])): ?>
                           <strong>İlgili Kişi:</strong> <?php echo htmlspecialchars($teklif['yetkili_ismi']); ?><br>
                       <?php endif; ?>
                       <?php if(!empty($teklif['adres'])): ?><?php echo nl2br(htmlspecialchars($teklif['adres'])); ?><br><?php endif; ?>
                       <?php if(!empty($teklif['telefon'])): ?>Tel: <?php echo htmlspecialchars($teklif['telefon']); ?><br><?php endif; ?>
                       <?php if(!empty($teklif['email'])): ?>E-posta: <?php echo htmlspecialchars($teklif['email']); ?><?php endif; ?>
                   </div>
               </td>
               <td class="spacer"></td>
               <td>
                   <div class="info-box">
                       <h3>Teklif Bilgileri</h3>
                       <strong>Teklif No:</strong> <?php echo htmlspecialchars($teklif['proposal_no']); ?><br>
                       <strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($teklif['proposal_date'])); ?><br>
                       <strong>Hazırlayan:</strong> <?php echo htmlspecialchars($teklif['user_name']); ?>
                   </div>
               </td>
           </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 10%;" class="text-center">Görsel</th>
                    <th style="width: 35%;">Ürün Adı / Açıklama</th>
                    <th class="text-end" style="width: 8%;">Adet</th>
                    <th class="text-end" style="width: 14%;">Birim Fiyat</th>
                    <th class="text-end" style="width: 8%;">İsk. (%)</th>
                    <th class="text-end" style="width: 20%;">Toplam Fiyat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($teklif_items as $index => $item): ?>
                <tr>
                    <td class="text-center"><?php echo $index + 1; ?></td>
                    <td class="text-center">
                        <?php if (!empty($item['fotograf_yolu']) && file_exists(PROJECT_ROOT . '/' . $item['fotograf_yolu'])): ?>
                            <img src="<?php echo PROJECT_ROOT . '/' . $item['fotograf_yolu']; ?>" class="product-image">
                        <?php else: ?>
                            <div style="width: 40px; height: 40px; border:1px solid #ccc; display: flex; align-items: center; justify-content: center; margin:auto;">X</div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td class="text-end"><?php echo $item['quantity']; ?></td>
                    <td class="text-end"><?php echo number_format($item['unit_price'], 2, ',', '.'); ?></td>
                    <td class="text-end"><?php echo number_format($item['discount_percent'], 2, ',', '.'); ?></td>
                    <td class="text-end"><?php echo number_format($item['total_price'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals-section clearfix">
            <table class="totals-table">
                <tr><td>Ara Toplam:</td><td><?php echo number_format($teklif['sub_total'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td></tr>
                <tr><td>İndirim Toplamı:</td><td>- <?php echo number_format($teklif['total_discount'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td></tr>
                <tr><td>KDV (%<?php echo number_format($teklif['tax_rate'], 0); ?>):</td><td><?php echo number_format($teklif['tax_amount'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td></tr>
                <tr class="grand-total"><td>GENEL TOPLAM:</td><td><?php echo number_format($teklif['grand_total'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td></tr>
            </table>
        </div>
    </main>
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

$dosya_adi = 'Teklif-' . str_replace('/', '-', $teklif['proposal_no']) . '.pdf';
$dompdf->stream($dosya_adi, ["Attachment" => false]);
?>