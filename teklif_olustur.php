<?php
require 'core/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'config/database.php';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. ANA TEKLİF BİLGİLERİNİ AL
    $customer_id = $_POST['customer_id'];
    $proposal_date = $_POST['proposal_date'];
    $currency = $_POST['currency'];
    
    // Toplamları ve kalemleri al
    $products = $_POST['products'] ?? [];
    $kdv_rate = (float)($_POST['kdv_rate_hidden'] ?? 20);

    // Güvenlik ve tutarlılık için toplamları PHP'de yeniden hesapla
    $sub_total = 0;
    $total_discount = 0;

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

    // Veritabanı işlemlerini transaction ile güvene al
    $pdo->beginTransaction();

    try {
        // 2. ANA TEKLİFİ `proposals` TABLOSUNA KAYDET
        // YENİ NUMARALANDIRMA MANTIĞI
// 1. O gün için mevcut teklif sayısını bul
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE DATE(proposal_date) = ? AND original_proposal_id IS NULL");
$stmt_count->execute([$proposal_date]);
$daily_counter = $stmt_count->fetchColumn() + 1;

// 2. Yeni teklif numarasını formatla (örn: 2025/07/03-01)
$formatted_date = date("Y/m/d", strtotime($proposal_date));
$formatted_counter = str_pad($daily_counter, 2, '0', STR_PAD_LEFT); // 1 ise "01", 10 ise "10" yapar
$proposal_no = $formatted_date . '-' . $formatted_counter;
        
        // Önceki adımlarda veritabanına eklediğimiz yeni sütunları da sorguya dahil ediyoruz
        $sql_proposal = "INSERT INTO proposals 
                            (proposal_no, customer_id, user_id, status_id, proposal_date, currency, sub_total, total_discount, tax_rate, tax_amount, grand_total) 
                         VALUES 
                            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_proposal = $pdo->prepare($sql_proposal);
        // status_id = 1 (Teklif Gönderildi)
        $stmt_proposal->execute([
            $proposal_no, $customer_id, $_SESSION['user_id'], 1, $proposal_date, $currency, 
            $sub_total, $total_discount, $kdv_rate, $tax_amount, $grand_total
        ]);
        
        $last_proposal_id = $pdo->lastInsertId();

        // 3. TEKLİF KALEMLERİNİ `proposal_items` TABLOSUNA KAYDET
        $sql_item = "INSERT INTO proposal_items 
                        (proposal_id, product_id, product_name, quantity, unit_price, discount_percent, total_price) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);

        foreach ($products as $product) {
            $product_name_stmt = $pdo->prepare("SELECT urun_adi FROM products WHERE id = ?");
            $product_name_stmt->execute([$product['id']]);
            $product_name = $product_name_stmt->fetchColumn();
            
            $line_total = (float)$product['quantity'] * (float)$product['unit_price'];
            $line_final_total = $line_total - ($line_total * ((float)$product['discount'] / 100));

            $stmt_item->execute([
                $last_proposal_id, $product['id'], $product_name,
                $product['quantity'], $product['unit_price'], $product['discount'], $line_final_total
            ]);
        }
        
        $pdo->commit();
        header("Location: teklif_listesi.php");
        exit();
		
		add_log($pdo, 'YENİ TEKLİF OLUŞTURULDU', 'Teklif No: ' . $proposal_no);

    } catch (Exception $e) {
        $pdo->rollBack();
        die("HATA: Teklif kaydedilemedi. " . $e->getMessage());
    }
}


// Form ilk yüklendiğinde çalışacak kısım
// Müşterileri çek
$stmt_customers = $pdo->query("SELECT id, unvan as text FROM customers ORDER BY unvan ASC");
$customers = $stmt_customers->fetchAll();

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
                <!-- Teklif Genel Bilgileri -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label">Müşteri Seçin (*)</label>
                        <select class="form-control" id="customer_id" name="customer_id" required></select>
                    </div>
                    <div class="col-md-3">
                        <label for="proposal_date" class="form-label">Teklif Tarihi</label>
                        <input type="date" class="form-control" id="proposal_date" name="proposal_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="currency" class="form-label">Para Birimi</label>
                        <select class="form-control" id="currency" name="currency">
                            <option value="TL">Türk Lirası (TL)</option>
                            <option value="USD">ABD Doları (USD)</option>
                            <option value="EUR">Euro (EUR)</option>
                        </select>
                    </div>
                </div>

                <!-- Teklif Kalemleri -->
                <div class="table-responsive">
                    <table class="table" id="teklifKalemleri">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 5%;">Görsel</th>
                                <th style="width: 30%;">Ürün/Hizmet</th>
                                <th style="width: 10%;">Adet</th>
                                <th style="width: 15%;">Birim Fiyat</th>
                                <th style="width: 15%;">İskonto (%)</th>
                                <th style="width: 20%;">Toplam</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Ürün satırları buraya JavaScript ile eklenecek -->
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-info" id="urunEkleBtn"><i class="fas fa-plus"></i> Ürün Ekle</button>

                <!-- Toplamlar -->
                <div class="row justify-content-end mt-4">
                    <div class="col-md-6">
                        <table class="table totals-table">
                            <tbody>
                                <tr>
                                    <th class="align-middle">ARA TOPLAM</th>
                                    <td class="text-end" id="araToplam">0.00</td>
                                </tr>
                                <tr>
                                    <th class="align-middle">İSKONTO TOPLAMI</th>
                                    <td class="text-end text-danger" id="iskontoToplami">(0.00)</td>
                                </tr>
                                <tr>
                                    <th class="align-middle">
                                        <div class="d-flex justify-content-end align-items-center">
                                            <span>KDV</span>
                                            <div class="input-group ms-2" style="width: 90px;">
                                                <input type="number" class="form-control form-control-sm text-center" id="kdvOrani" value="20" step="1">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </th>
                                    <td class="text-end" id="kdvTutari">0.00</td>
                                </tr>
                                <tr class="grand-total-row">
                                    <th class="align-middle">GENEL TOPLAM</th>
                                    <td class="text-end" id="genelToplam">0.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Gizli input'lar. Form gönderildiğinde PHP'nin bu değerleri alması için gerekli -->
                <input type="hidden" name="kdv_rate_hidden" id="kdv_rate_hidden" value="20">

                <hr class="my-4">
                <button type="submit" class="btn btn-primary btn-lg">Teklifi Kaydet</button>
                <a href="teklif_listesi.php" class="btn btn-secondary btn-lg">Vazgeç</a>
            </form>
        </div>
    </div>
</div>

<?php
// Sayfanın tüm HTML'i bittikten sonra footer'ı çağırıyoruz.
// Bu footer, jQuery'yi ve teklif oluşturma script'ini yükleyecek.
include 'partials/footer.php'; 
?>