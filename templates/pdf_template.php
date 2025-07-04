<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Teklif - <?php echo htmlspecialchars($teklif['proposal_no']); ?></title>
    <style>
        /* === TÜM CSS'LER TEKLIF_PDF.PHP İLE AYNI HALE GETİRİLDİ === */
        @page { margin: 100px 25px 80px 25px; }
        body { font-family: 'dejavu sans', sans-serif; font-size: 10px; color: #333; }
        header { position: fixed; top: -80px; left: 0px; right: 0px; height: 70px; text-align: center; }
        header img { max-width: 100%; height: auto; }
        footer { position: fixed; bottom: -60px; left: 0px; right: 0px; height: 50px; text-align: center; }
        footer img { max-width: 100%; }
        .page-number:after { content: "Sayfa " counter(page); }
        main { }
        .info-table { margin-top: 0; margin-bottom: 20px; width: 100%; border-spacing: 10px 0; border-collapse: separate; }
        .info-table td { width: 50%; vertical-align: top; }
        .info-box { border: 1px solid #dee2e6; padding: 15px; height: 110px; }
        .info-box h3 { font-size: 10px; margin-top: 0; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #dee2e6; font-weight: bold; text-transform: uppercase; }
        .items-table { margin-top: 20px; width: 100%; border-collapse: collapse; }
        .items-table th, .items-table td { border: 1px solid #dee2e6; padding: 8px; text-align: left; vertical-align: middle; }
        
        /* === DİNAMİK RENK KULLANIMI === */
        /* Bu şablonun çağrıldığı pdf_generator.php dosyasında $theme_color değişkeni tanımlanmalıdır */
        .items-table th { background-color: <?php echo $theme_color ?? '#004a99'; ?>; color: #ffffff; }
        
        .items-table .product-image { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        .product-description { font-size: 9px; color: #555; padding-top: 5px; }
        .totals-section { width: 100%; margin-top: 20px; }
        .totals-table { width: 45%; float: right; border-collapse: collapse; }
        .totals-table td { padding: 6px; }
        .totals-table tr td:first-child { text-align: right; font-weight: bold; }
        .totals-table tr td:last-child { text-align: right; }
        .grand-total { font-size: 14px; font-weight: bold; border-top: 1px solid #333; }
        .notes-section { margin-top: 40px; page-break-inside: avoid; }
        
        /* === DİNAMİK RENK KULLANIMI === */
        .notes-section h4 { font-size: 12px; font-weight: bold; color: <?php echo $theme_color ?? '#004a99'; ?>; border-bottom: 1px solid <?php echo $theme_color ?? '#004a99'; ?>; padding-bottom: 5px; margin-bottom: 10px; }
        
        .notes-section div { font-size: 9.5px; line-height: 1.4; }
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
        <h2 style="text-align:center; font-size:18px; margin-bottom: 20px;">FİYAT TEKLİFİ</h2>
        <table class="info-table">
           <tr>
               <td>
                   <div class="info-box">
                       <h3>Müşteri Bilgileri</h3>
                       <strong><?php echo htmlspecialchars($teklif['unvan']); ?></strong><br>
                       <?php if (!empty($teklif['contact_person'])): ?>
                           <strong>İlgili Kişi:</strong> <?php echo htmlspecialchars($teklif['contact_person']); ?><br>
                       <?php endif; ?>
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
                       <?php if (!empty($teklif['subject'])): ?>
                           <strong>Konu:</strong> <?php echo htmlspecialchars($teklif['subject']); ?><br>
                       <?php endif; ?>
                       <strong>Hazırlayan:</strong> <?php echo htmlspecialchars($teklif['user_name']); ?>
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
            <table class="totals-table">
                <tr><td>Ara Toplam:</td><td><?php echo number_format($teklif['sub_total'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td></tr>
                <tr><td>İndirim Toplamı:</td><td>- <?php echo number_format($teklif['total_discount'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td></tr>
                <tr><td>KDV (%<?php echo number_format($teklif['tax_rate'], 0); ?>):</td><td><?php echo number_format($teklif['tax_amount'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td></tr>
                <tr class="grand-total"><td>GENEL TOPLAM:</td><td><?php echo number_format($teklif['grand_total'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td></tr>
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