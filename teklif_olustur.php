<?php
require 'core/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'config/database.php';

// Form gönderildi mi? (PHP Kısmında Değişiklik Yok)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. ANA TEKLİF BİLGİLERİNİ AL
    $customer_id = $_POST['customer_id'];
    $proposal_date = $_POST['proposal_date'];
    $currency = $_POST['currency'];
    $subject = $_POST['subject'] ?? 'Teklif';
    $contact_person = $_POST['contact_person'] ?? '';
    
    // Toplamları ve kalemleri al
    $products = $_POST['products'] ?? [];
    $kdv_rate = (float)($_POST['kdv_rate_hidden'] ?? 20);

    // Güvenlik ve tutarlılık için toplamları PHP'de yeniden hesapla
    $sub_total = 0; $total_discount = 0;
    foreach ($products as $product) {
        $quantity = (float)($product['quantity'] ?? 0);
        $unit_price = (float)($product['unit_price'] ?? 0);
        $discount_percent = (float)($product['discount'] ?? 0);
        $line_total = $quantity * $unit_price;
        $sub_total += $line_total;
        $total_discount += $line_total * ($discount_percent / 100);
    }
    $net_total = $sub_total - $total_discount;
    $tax_amount = $net_total * ($kdv_rate / 100);
    $grand_total = $net_total + $tax_amount;

    $pdo->beginTransaction();

    try {
        // Numara oluşturma
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE DATE(proposal_date) = ? AND original_proposal_id IS NULL");
        $stmt_count->execute([$proposal_date]);
        $daily_counter = $stmt_count->fetchColumn() + 1;
        $formatted_date = date("Y/m/d", strtotime($proposal_date));
        $formatted_counter = str_pad($daily_counter, 2, '0', STR_PAD_LEFT);
        $proposal_no = $formatted_date . '-' . $formatted_counter;
        
        // Veritabanına kaydetme
        $sql_proposal = "INSERT INTO proposals (proposal_no, customer_id, user_id, status_id, proposal_date, currency, sub_total, total_discount, tax_rate, tax_amount, grand_total, subject, contact_person) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_proposal = $pdo->prepare($sql_proposal);
        $stmt_proposal->execute([$proposal_no, $customer_id, $_SESSION['user_id'], 1, $proposal_date, $currency, $sub_total, $total_discount, $kdv_rate, $tax_amount, $grand_total, $subject, $contact_person]);
        $last_proposal_id = $pdo->lastInsertId();

        // Kalemleri kaydetme
        $sql_item = "INSERT INTO proposal_items (proposal_id, product_id, product_name, quantity, unit_price, discount_percent, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);
        foreach ($products as $product) {
            $product_name_stmt = $pdo->prepare("SELECT urun_adi FROM products WHERE id = ?");
            $product_name_stmt->execute([$product['id']]);
            $product_name = $product_name_stmt->fetchColumn();
            $line_total = (float)$product['quantity'] * (float)$product['unit_price'];
            $line_final_total = $line_total - ($line_total * ((float)$product['discount'] / 100));
            $stmt_item->execute([$last_proposal_id, $product['id'], $product_name, $product['quantity'], $product['unit_price'], $product['discount'], $line_final_total]);
        }
        
        add_log($pdo, 'YENİ TEKLİF OLUŞTURULDU', 'Teklif No: ' . $proposal_no);
        $pdo->commit();
        header("Location: teklif_listesi.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("HATA: Teklif kaydedilemedi. " . $e->getMessage());
    }
}

// Müşterileri çekerken yetkili ismini de alıyoruz
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
                <!-- Teklif Genel Bilgileri (Değişiklik Yok) -->
                <div class="row mb-3">
                    <div class="col-md-5">
                        <label for="customer_id" class="form-label">Müşteri Seçin (*)</label>
                        <select class="form-control" id="customer_id" name="customer_id" required></select>
                    </div>
                    <div class="col-md-4">
                        <label for="subject" class="form-label">Teklif Konusu</label>
                        <input type="text" class="form-control" id="subject" name="subject" placeholder="Örn: Ofis Malzemeleri Alımı">
                    </div>
                    <div class="col-md-3">
                        <label for="proposal_date" class="form-label">Teklif Tarihi</label>
                        <input type="date" class="form-control" id="proposal_date" name="proposal_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-5">
                        <label for="contact_person" class="form-label">Kime (Yetkili)</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person" placeholder="Müşteri seçince otomatik gelir">
                    </div>
                    <div class="col-md-4"></div>
                    <div class="col-md-3">
                        <label for="currency" class="form-label">Para Birimi</label>
                        <select class="form-control" id="currency" name="currency">
                            <option value="TL">Türk Lirası (TL)</option>
                            <option value="USD">ABD Doları (USD)</option>
                            <option value="EUR">Euro (EUR)</option>
                        </select>
                    </div>
                </div>

                <!-- Teklif Kalemleri (Değişiklik Yok) -->
                <div class="table-responsive">
                    <table class="table" id="teklifKalemleri">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 5%;">Görsel</th><th style="width: 30%;">Ürün/Hizmet</th><th style="width: 10%;">Adet</th><th style="width: 15%;">Birim Fiyat</th><th style="width: 15%;">İskonto (%)</th><th style="width: 20%;">Toplam</th><th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-info" id="urunEkleBtn"><i class="fas fa-plus"></i> Ürün Ekle</button>

                <!-- === TOPLAMLAR BÖLÜMÜ GÜNCELLENDİ === -->
                <div class="row mt-4">
                    <div class="col-md-6 col-lg-5 ms-auto"> <!-- Sağ tarafta daha az yer kaplaması için col-lg-5 ekledik -->
                        <div class="totals-summary p-3 border rounded-3 bg-light">
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">ARA TOPLAM</span>
                                <span id="araToplam" class="fw-bold">0.00</span>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">İSKONTO TOPLAMI</span>
                                <span class="text-danger fw-bold" id="iskontoToplami">(0.00)</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center text-muted">
                                    <span>KDV</span>
                                    <div class="input-group ms-2" style="width: 100px;">
                                        <input type="number" class="form-control form-control-sm text-center" id="kdvOrani" value="20" step="1">
                                        <span class="input-group-text p-1">%</span>
                                    </div>
                                </div>
                                <span id="kdvTutari" class="fw-bold">0.00</span>
                            </div>

                            <hr class="my-2">

                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">GENEL TOPLAM</h5>
                                <h5 class="mb-0 text-primary" id="genelToplam">0.00</h5>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- === GÜNCELLENMİŞ TOPLAMLAR BÖLÜMÜ SONU === -->
                
                <input type="hidden" name="kdv_rate_hidden" id="kdv_rate_hidden" value="20">
                <hr class="my-4">
                <button type="submit" class="btn btn-primary btn-lg">Teklifi Kaydet</button>
                <a href="teklif_listesi.php" class="btn btn-secondary btn-lg">Vazgeç</a>
            </form>
        </div>
    </div>
</div>

<?php
include 'partials/footer.php'; 
?>