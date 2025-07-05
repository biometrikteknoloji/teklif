<?php
require 'core/functions.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... (POST kaydetme kodu senin çalışan kodunla aynı, hata burada değil) ...
}

$stmt_customers = $pdo->query("SELECT id, unvan as text, yetkili_ismi FROM customers ORDER BY unvan ASC");
$customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);

include 'partials/header.php';
?>
<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="user-info">Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!</div>
        </div>
        <div class="page-content">
            <h1>Yeni Teklif Oluştur</h1>
            <form action="teklif_olustur.php" method="POST" id="teklifFormu">
                
                <!-- Üst Bilgi Alanları -->
                <div class="row mb-3">
                    <div class="col-md-5"><label for="customer_id" class="form-label">Müşteri Seçin (*)</label><select class="form-control" id="customer_id" name="customer_id" required></select></div>
                    <div class="col-md-4"><label for="subject" class="form-label">Teklif Konusu</label><input type="text" class="form-control" id="subject" name="subject" placeholder="Örn: Ofis Malzemeleri Alımı"></div>
                    <div class="col-md-3"><label for="proposal_date" class="form-label">Teklif Tarihi</label><input type="date" class="form-control" id="proposal_date" name="proposal_date" value="<?php echo date('Y-m-d'); ?>"></div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-5"><label for="contact_person" class="form-label">Kime (Yetkili)</label><input type="text" class="form-control" id="contact_person" name="contact_person" placeholder="Müşteri seçince otomatik gelir"></div>
                    <div class="col-md-4"></div>
                    <div class="col-md-3"><label for="currency" class="form-label">Para Birimi</label><select class="form-control" id="currency" name="currency"><option value="TL">Türk Lirası (TL)</option><option value="USD">ABD Doları (USD)</option><option value="EUR">Euro (EUR)</option></select></div>
                </div>

                <!-- Teklif Kalemleri Tablosu (Sadeleştirilmiş) -->
                <div class="table-responsive">
                    <table class="table" id="teklifKalemleri">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 5%;">Görsel</th>
                                <th style="width: 45%;">Ürün/Hizmet</th>
                                <th style="width: 15%;">Adet</th>
                                <th style="width: 15%;">Birim Fiyat</th>
                                <th style="width: 20%;">Toplam</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-start gap-2">
                    <button type="button" class="btn btn-info" id="urunEkleBtn"><i class="fas fa-plus"></i> Ürün Ekle</button>
                    <button type="button" class="btn btn-outline-secondary" id="toggleDiscountBtn"><i class="fas fa-percent"></i> İskonto Uygula</button>
                </div>

                <!-- Toplamlar Bölümü (İstediğin Son Yapı) -->
                <div class="row mt-4">
                    <div class="col-md-6 col-lg-5 ms-auto">
                        <div class="totals-summary p-3 border rounded-3 bg-light">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">TOPLAM</span>
                                <span id="toplamBrut" class="fw-bold">0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 discount-row d-none">
                                <span class="text-danger">İSKONTO</span>
                                <span class="text-danger" id="genelIskontoGosterim">(0.00)</span>
                            </div>
                            <hr class="my-2 discount-row d-none">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted fw-bold">ARA TOPLAM</span>
                                <span id="araToplamNet" class="fw-bold">0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center text-muted">
                                    <span>K.D.V.</span>
                                    <div class="input-group ms-2" style="width: 100px;"><input type="number" class="form-control form-control-sm text-center" id="kdvOrani" value="20" step="1"><span class="input-group-text p-1">%</span></div>
                                </div>
                                <span id="kdvTutari" class="fw-bold">0.00</span>
                            </div>
                            <hr class="my-2 bg-primary" style="height: 2px; opacity: 0.75;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">G.TOPLAM</h5>
                                <h5 class="mb-0 text-primary" id="genelToplam">0.00</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Gizli İskonto Inputları -->
                <div class="discount-row d-none">
                     <input type="number" id="genelIskontoYuzde" step="any">
                     <input type="number" id="genelIskontoTutar" name="genelIskontoTutar" step="any">
                </div>
                
                <input type="hidden" name="kdv_rate_hidden" id="kdv_rate_hidden" value="20">
                <hr class="my-4">
                <button type="submit" class="btn btn-primary btn-lg">Teklifi Kaydet</button>
                <a href="teklif_listesi.php" class="btn btn-secondary btn-lg">Vazgeç</a>
            </form>
        </div>
    </div>
</div>
<?php include 'partials/footer.php'; ?>