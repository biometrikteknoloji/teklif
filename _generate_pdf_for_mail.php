<?php
session_start();
if (!isset($_SESSION['user_id'])) { exit('Bu işlemi yapmak için giriş yapmalısınız.'); }

define('PROJECT_ROOT', dirname(__FILE__)); 

require_once PROJECT_ROOT . '/lib/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;
require_once PROJECT_ROOT . '/config/database.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) { exit('Geçersiz veya eksik Teklif ID.'); }

$sql_proposal = "SELECT p.*, c.*, u.full_name as user_name, u.email as user_email, u.phone as user_phone 
                 FROM proposals p 
                 JOIN customers c ON p.customer_id = c.id 
                 JOIN users u ON p.user_id = u.id 
                 WHERE p.id = ?";
$stmt = $pdo->prepare($sql_proposal);
$stmt->execute([$id]);
$teklif = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$teklif) { exit('Belirtilen ID ile bir teklif bulunamadı.'); }

$stmt_items = $pdo->prepare("SELECT pi.*, pr.fotograf_yolu, pr.urun_aciklamasi FROM proposal_items pi LEFT JOIN products pr ON pi.product_id = pr.id WHERE pi.proposal_id = ? ORDER BY pi.id ASC");
$stmt_items->execute([$id]);
$teklif_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$theme_color = $settings['proposal_theme_color'] ?? '#004a99';

ob_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Teklif - <?php echo htmlspecialchars($teklif['proposal_no']); ?></title>
    <style>
        /* === CSS YERLEŞİMİ GÜNCELLENDİ === */
        @page { margin: 90px 25px 80px 25px; } /* Üst boşluk azaltıldı */
        body { font-family: 'dejavu sans', sans-serif; font-size: 9.5px; color: #333; }
        header { position: fixed; top: -75px; left: 0px; right: 0px; height: 65px; text-align: center; }
        header img { max-width: 100%; max-height: 65px; }
        footer { position: fixed; bottom: -60px; left: 0px; right: 0px; height: 50px; text-align: center; }
        footer img { max-width: 100%; }
        .page-number:after { content: "Sayfa " counter(page); }
        main { }
        .info-table { margin-top: 5px; margin-bottom: 15px; width: 100%; border-spacing: 5px 0; border-collapse: separate; }
        .info-table td { width: 50%; vertical-align: top; }
        .info-box { border: 1px solid #dee2e6; padding: 10px; height: 95px; }
        .info-box h3 { font-size: 9.5px; margin-top: 0; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid #dee2e6; font-weight: bold; text-transform: uppercase; }
        .items-table { margin-top: 15px; width: 100%; border-collapse: collapse; }
        .items-table th, .items-table td { border: 1px solid #dee2e6; padding: 6px; text-align: left; vertical-align: middle; }
        .items-table th { background-color: <?php echo $theme_color; ?>; color: #ffffff; }
        .items-table .product-image { width: 45px; height: 45px; object-fit: cover; border-radius: 4px; }
        .product-description { font-size: 8.5px; color: #555; padding-top: 4px; }
        .totals-section { width: 100%; margin-top: 15px; }
        .totals-table { width: 40%; float: right; border-collapse: collapse; }
        .totals-table td { padding: 5px; font-size: 10px; }
        .totals-table tr td:first-child { text-align: right; font-weight: bold; }
        .totals-table tr td:last-child { text-align: right; }
        .grand-total { font-size: 13px; font-weight: bold; border-top: 1px solid #333; }
        .notes-section { margin-top: 30px; page-break-inside: avoid; }
        .notes-section h4 { font-size: 11px; font-weight: bold; color: <?php echo $theme_color; ?>; border-bottom: 1px solid <?php echo $theme_color; ?>; padding-bottom: 4px; margin-bottom: 8px; }
        .notes-section div { font-size: 9px; line-height: 1.3; }
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
        <div class="page-number" style="text-align: right; padding-right: 25px; font-size:9px;"></div>
        <?php if (!empty($settings['proposal_footer_path']) && file_exists(PROJECT_ROOT . '/' . $settings['proposal_footer_path'])): ?>
            <img src="<?php echo PROJECT_ROOT . '/' . $settings['proposal_footer_path']; ?>">
        <?php endif; ?>
    </footer>

    <main>
        <!-- === BAŞLIK YUKARI TAŞINDI === -->
        <h2 style="text-align:center; font-size:16px; margin-bottom: 10px; margin-top: -10px;">FİYAT TEKLİFİ</h2>
        
        <table class="info-table">
           <tr>
               <td>
                   <div class="info-box">
                       <h3>Müşteri Bilgileri</h3>
                       <strong><?php echo htmlspecialchars($teklif['unvan']); ?></strong><br>
                       <?php if (!empty($teklif['contact_person'])): ?><strong>İlgili Kişi:</strong> <?php echo htmlspecialchars($teklif['contact_person']); ?><br><?php endif; ?>
                       <?php if(!empty($teklif['adres'])): ?><?php echo nl2br(htmlspecialchars($teklif['adres'])); ?><br><?php endif; ?>
                       <?php if(!empty($teklif['telefon'])): ?>Tel: <?php echo htmlspecialchars($teklif['telefon']); ?><br><?php endif; ?>
                       <?php if(!empty($teklif['email'])): ?>E-posta: <?php echo htmlspecialchars($teklif['email']); ?><?php endif; ?>
                   </div>
               </td>
               <td>
                   <div class="info-box">
                       <h3>Teklif Bilgileri</h3>
                       <strong>Teklif No:</strong> <?php echo htmlspecialchars($teklif['proposal_no']); ?><br>
                       <strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($teklif['proposal_date'])); ?><br>
                       <?php if (!empty($teklif['subject'])): ?><strong>Konu:</strong> <?php echo htmlspecialchars($teklif['subject']); ?><br><?php endif; ?>
                       <strong>Hazırlayan:</strong> <?php echo htmlspecialchars($teklif['user_name']); ?><br>
                       <?php if (!empty($teklif['user_phone'])): ?><strong>Tel:</strong> <?php echo htmlspecialchars($teklif['user_phone']); ?><br><?php endif; ?>
                       <?php if (!empty($teklif['user_email'])): ?><strong>E-posta:</strong> <?php echo htmlspecialchars($teklif['user_email']); ?><?php endif; ?>
                   </div>
               </td>
           </tr>
        </table>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">S.No</th>
                    <th style="width: 10%;" class="text-center">Görsel</th>
                    <th style="width: 45%;">Ürün Adı / Açıklama</th>
                    <th class="text-end" style="width: 8%;">Adet</th>
                    <th class="text-end" style="width: 14%;">Birim Fiyat</th>
                    <th class="text-end" style="width: 8%;">İsk. (%)</th>
                    <th class="text-end" style="width: 10%;">Toplam Fiyat</th>
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
                            <div style="width: 50px; height: 50px; border:1px solid #ccc; display: flex; align-items: center; justify-content: center; margin:auto;">-</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                        <?php if(!empty($item['urun_aciklamasi'])): ?>
                            <div class="product-description"><?php echo nl2br(htmlspecialchars($item['urun_aciklamasi'])); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?php echo $item['quantity']; ?></td>
                    <td class="text-end"><?php echo number_format($item['unit_price'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td>
                    <td class="text-end"><?php echo number_format($item['discount_percent'], 0); ?>%</td>
                    <td class="text-end"><?php echo number_format($item['total_price'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
<div class="totals-section clearfix">
    <table class="totals-table" style="border: 1px solid #dee2e6;">
        <?php
        $sub_total_calc = (float)($teklif['sub_total'] ?? 0);
        $total_discount_calc = (float)($teklif['total_discount'] ?? 0);
        
        if ($total_discount_calc > 0):
            $net_total_calc = $sub_total_calc - $total_discount_calc;
            $discount_percentage_calc = (float)($teklif['discount_percentage'] ?? (($sub_total_calc > 0) ? ($total_discount_calc / $sub_total_calc) * 100 : 0));
        ?>
            <!-- TOPLAM (Sadece İskonto Varsa Görünür) -->
            <tr style="background-color: #f8f9fa;">
                <td style="text-align: right; font-weight: bold; width: 65%; border-bottom: 1px solid #dee2e6; padding: 6px;">TOPLAM:</td>
                <td style="text-align: right; width: 35%; border-bottom: 1px solid #dee2e6; padding: 6px;"><?php echo number_format($sub_total_calc, 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td>
            </tr>
            <!-- İSKONTO (Sadece İskonto Varsa Görünür) - GÜNCELLENDİ -->
            <tr style="background-color: #f8f9fa; color: #dc3545;">
                <td style="text-align: right; font-weight: bold; border-bottom: 1px solid #dee2e6; padding: 6px;">İSKONTO (%<?php echo number_format($discount_percentage_calc, 2, ',', '.'); ?>):</td>
                <td style="text-align: right; border-bottom: 1px solid #dee2e6; padding: 6px;">
                    (<?php echo number_format($total_discount_calc, 2, ',', '.'); ?> <?php echo $teklif['currency']; ?>)
                </td>
            </tr>
            <!-- ARA TOPLAM (Sadece İskonto Varsa Görünür) -->
            <tr style="background-color: #f8f9fa;">
                <td style="text-align: right; font-weight: bold; border-bottom: 1px solid #dee2e6; padding: 6px; padding-top: 10px;">ARA TOPLAM:</td>
                <td style="text-align: right; border-bottom: 1px solid #dee2e6; padding: 6px; padding-top: 10px;">
                    <?php echo number_format($net_total_calc, 2, ',', '.'); ?> <?php echo $teklif['currency']; ?>
                </td>
            </tr>
        <?php else: ?>
            <!-- İSKONTO YOKSA GÖSTERİLECEK YAPI -->
            <tr style="background-color: #f8f9fa;">
                <td style="text-align: right; font-weight: bold; width: 65%; border-bottom: 1px solid #dee2e6; padding: 6px;">ARA TOPLAM:</td>
                <td style="text-align: right; width: 35%; border-bottom: 1px solid #dee2e6; padding: 6px;"><?php echo number_format($sub_total_calc, 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td>
            </tr>
        <?php endif; ?>

        <!-- KDV (Her Zaman Görünür) -->
        <tr style="background-color: #f8f9fa;">
            <td style="text-align: right; font-weight: bold; border-bottom: 1px solid #dee2e6; padding: 6px;">K.D.V. (%<?php echo number_format($teklif['tax_rate'], 0); ?>):</td>
            <td style="text-align: right; border-bottom: 1px solid #dee2e6; padding: 6px;"><?php echo number_format($teklif['tax_amount'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td>
        </tr>
        <!-- GENEL TOPLAM (Her Zaman Görünür) -->
        <tr style="background-color: #e9ecef;">
            <td class="grand-total" style="text-align: right; padding: 8px;">G.TOPLAM:</td>
            <td class="grand-total" style="text-align: right; padding: 8px;"><?php echo number_format($teklif['grand_total'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td>
        </tr>
    </table>
</div>

        <div class="clearfix"></div>
        <?php if (!empty($settings['proposal_default_notes'])): ?>
            <div class="notes-section">
                <h4>: DİĞER BİLGİLER :</h4>
                <div>
                    <?php echo $settings['proposal_default_notes']; ?>
                </div>
            </div>
        <?php endif; ?>

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
// generate_pdf_for_mail.php dosyasının sonu

// ...
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// ESKİ SATIR (SİLİNDİ): $dompdf->stream($dosya_adi, ["Attachment" => false]); 

// YENİ SATIR:
return $dompdf->output(); 
?> 
?>