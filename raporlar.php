<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require 'config/database.php';

// Değişkenleri başlangıçta tanımla
$rapor_sonuclari = [];
$baslangic_tarihi = '';
$bitis_tarihi = '';
$toplamlar = ['TL' => 0, 'USD' => 0, 'EUR' => 0];
$durum_sayilari = [];

// Form gönderilmişse
if (isset($_GET['action']) && $_GET['action'] == 'tarih_raporu') {
    $baslangic_tarihi = $_GET['baslangic_tarihi'] ?? '';
    $bitis_tarihi = $_GET['bitis_tarihi'] ?? '';

    if (!empty($baslangic_tarihi) && !empty($bitis_tarihi)) {
        $sql = "SELECT p.id, p.proposal_no, p.proposal_date, p.grand_total, p.currency, c.unvan, ps.status_name, ps.id as status_id
                FROM proposals p
                JOIN customers c ON p.customer_id = c.id
                JOIN proposal_statuses ps ON p.status_id = ps.id
                WHERE p.proposal_date BETWEEN ? AND ? ORDER BY p.proposal_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$baslangic_tarihi, $bitis_tarihi]);
        $rapor_sonuclari = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rapor_sonuclari as $sonuc) {
            if (isset($toplamlar[$sonuc['currency']])) { $toplamlar[$sonuc['currency']] += $sonuc['grand_total']; }
            $durum_adi = $sonuc['status_name'];
            if (!isset($durum_sayilari[$durum_adi])) { $durum_sayilari[$durum_adi] = 0; }
            $durum_sayilari[$durum_adi]++;
        }
    }
}

include 'partials/header.php';
?>

<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
             <div class="user-info">Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!</div>
        </div>
        <div class="page-content">
            <h1>Raporlar</h1>
            <p class="lead mb-4">Sistem verilerine dayanarak çeşitli raporlar oluşturun.</p>
            
            <!-- === KAYBOLAN FORM BURAYA GERİ EKLENDİ === -->
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-calendar-alt me-2"></i>Tarih Aralığına Göre Teklif Raporu</h5></div>
                <div class="card-body">
                    <form action="raporlar.php" method="GET" class="row g-3 align-items-end">
                        <input type="hidden" name="action" value="tarih_raporu">
                        <div class="col-md-4"><label for="baslangic_tarihi" class="form-label">Başlangıç Tarihi</label><input type="date" class="form-control" id="baslangic_tarihi" name="baslangic_tarihi" value="<?php echo htmlspecialchars($baslangic_tarihi); ?>" required></div>
                        <div class="col-md-4"><label for="bitis_tarihi" class="form-label">Bitiş Tarihi</label><input type="date" class="form-control" id="bitis_tarihi" name="bitis_tarihi" value="<?php echo htmlspecialchars($bitis_tarihi); ?>" required></div>
                        <div class="col-md-4"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-play me-2"></i>Rapor Oluştur</button></div>
                    </form>
                </div>
            </div>

            <?php if (isset($_GET['action']) && $_GET['action'] == 'tarih_raporu'): ?>
                <hr class="my-5">
                <div id="raporSonucAlani">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3>Rapor Sonuçları</h3>
                         
                        </div>
                        <?php if (count($rapor_sonuclari) > 0): ?>
                        <div class="btn-group">
                            <a href="rapor_pdf.php?baslangic=<?php echo $baslangic_tarihi; ?>&bitis=<?php echo $bitis_tarihi; ?>" target="_blank" class="btn btn-danger"><i class="fas fa-file-pdf me-2"></i>PDF İndir</a>
                            <a href="rapor_excel.php?baslangic=<?php echo $baslangic_tarihi; ?>&bitis=<?php echo $bitis_tarihi; ?>" class="btn btn-success"><i class="fas fa-file-excel me-2"></i>Excel İndir</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($rapor_sonuclari) > 0): ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr><th>Teklif Tarihi</th><th>Teklif No</th><th>Müşteri</th><th>Durum</th><th class="text-end">Tutar</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($rapor_sonuclari as $sonuc): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y', strtotime($sonuc['proposal_date'])); ?></td>
                                        <td><a href="teklif_view.php?id=<?php echo $sonuc['id']; ?>" target="_blank"><?php echo htmlspecialchars($sonuc['proposal_no']); ?></a></td>
                                        <td><?php echo htmlspecialchars($sonuc['unvan']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($sonuc['status_name']); ?></span></td>
                                        <td class="text-end fw-bold"><?php echo number_format($sonuc['grand_total'], 2, ',', '.'); ?> <?php echo $sonuc['currency']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h4>Durum Özeti</h4>
                                <ul class="list-group">
                                    <?php foreach($durum_sayilari as $durum => $sayi): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center"><?php echo htmlspecialchars($durum); ?><span class="badge bg-primary rounded-pill"><?php echo $sayi; ?> adet</span></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h4>Finansal Özet</h4>
                                <ul class="list-group">
                                    <?php foreach($toplamlar as $kur => $tutar): ?>
                                        <?php if ($tutar > 0): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Toplam (<?php echo $kur; ?>):</strong><span class="fw-bolder"><?php echo number_format($tutar, 2, ',', '.'); ?></span></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">Belirtilen tarih aralığında hiç teklif bulunamadı.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include 'partials/footer.php'; 
?>