<?php
require 'core/functions.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require 'config/database.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) { header('Location: teklif_listesi.php'); exit(); }

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $original_proposal_id = $_POST['original_proposal_id'];
    
    $stmt_rev = $pdo->prepare("SELECT MAX(revision_number) as max_rev, proposal_no FROM proposals WHERE id = ? OR original_proposal_id = ? GROUP BY proposal_no ORDER BY max_rev DESC LIMIT 1");
    $stmt_rev->execute([$original_proposal_id, $original_proposal_id]);
    $last_rev_info = $stmt_rev->fetch();
    
    $last_revision_number = $last_rev_info['max_rev'] ?? 0;
    $new_revision_number = $last_revision_number + 1;
    
    // Orijinal teklif numarasını bul (örn: TEK-20231027-123456)
    $original_proposal_no = preg_replace('/-R\d+$/', '', $last_rev_info['proposal_no']);

    $customer_id = $_POST['customer_id'];
    $proposal_date = $_POST['proposal_date'];
    $currency = $_POST['currency'];
    $revision_note = trim($_POST['revision_note']);
    $products = $_POST['products'] ?? [];
    $kdv_rate = (float)($_POST['kdv_rate_hidden'] ?? 20);

    // Toplamları PHP'de yeniden hesapla
    $sub_total = 0; $total_discount = 0;
    foreach ($products as $p) {
        $qt = (float)($p['quantity']??0); $up = (float)($p['unit_price']??0); $dp = (float)($p['discount']??0);
        $lt = $qt * $up; $sub_total += $lt; $total_discount += $lt * ($dp / 100);
    }
    $net_total = $sub_total - $total_discount;
    $tax_amount = $net_total * ($kdv_rate / 100);
    $grand_total = $net_total + $tax_amount;

    $pdo->beginTransaction();
    try {
        // YENİ REVİZYON NUMARALANDIRMA MANTIĞI
$original_proposal_no = $_POST['original_proposal_no']; // Bu gizli input'tan geliyordu, doğru.
$proposal_no = $original_proposal_no . "-R" . $new_revision_number;
        
        $sql = "INSERT INTO proposals (proposal_no, original_proposal_id, revision_number, revision_note, customer_id, user_id, status_id, proposal_date, currency, sub_total, total_discount, tax_rate, tax_amount, grand_total) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $proposal_no, $original_proposal_id, $new_revision_number, $revision_note, $customer_id, $_SESSION['user_id'], 2, $proposal_date, $currency, 
            $sub_total, $total_discount, $kdv_rate, $tax_amount, $grand_total
        ]);
        $new_proposal_id = $pdo->lastInsertId();

        $sql_item = "INSERT INTO proposal_items (proposal_id, product_id, product_name, quantity, unit_price, discount_percent, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);
        foreach ($products as $p) {
            $p_name_stmt = $pdo->prepare("SELECT urun_adi FROM products WHERE id = ?"); $p_name_stmt->execute([$p['id']]);
            $p_name = $p_name_stmt->fetchColumn();
            $lt = (float)$p['quantity'] * (float)$p['unit_price']; $lft = $lt - ($lt * ((float)$p['discount'] / 100));
            $stmt_item->execute([$new_proposal_id, $p['id'], $p_name, $p['quantity'], $p['unit_price'], $p['discount'], $lft]);
        }

        $pdo->commit();
        header("Location: teklif_listesi.php");
        exit();
		
		add_log($pdo, 'TEKLİF REVİZE EDİLDİ', 'Yeni Teklif No: ' . $proposal_no);
		
    } catch (Exception $e) {
        $pdo->rollBack();
        die("HATA: Revizyon oluşturulamadı. " . $e->getMessage());
    }
}

// ---- FORM GÖRÜNÜMÜ İÇİN VERİ ÇEKME ----
$stmt_last_rev = $pdo->prepare("SELECT * FROM proposals WHERE original_proposal_id = ? ORDER BY revision_number DESC LIMIT 1");
$stmt_last_rev->execute([$id]);
$teklif = $stmt_last_rev->fetch();
if (!$teklif) {
    $stmt_original = $pdo->prepare("SELECT * FROM proposals WHERE id = ?");
    $stmt_original->execute([$id]);
    $teklif = $stmt_original->fetch();
}
if (!$teklif) { header('Location: teklif_listesi.php'); exit(); }

$stmt_items = $pdo->prepare("SELECT pi.*, p.urun_adi as text, p.fotograf_yolu FROM proposal_items pi JOIN products p ON pi.product_id = p.id WHERE pi.proposal_id = ?");
$stmt_items->execute([$teklif['id']]);
$teklif_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

$stmt_customers = $pdo->query("SELECT id, unvan as text FROM customers ORDER BY unvan ASC");
$customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);

$original_proposal_id = $teklif['original_proposal_id'] ?? $teklif['id'];
$original_proposal_no = preg_replace('/-R\d+$/', '', $teklif['proposal_no']);


include 'partials/header.php';
?>

<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="user-info">Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!</div>
        </div>
        <div class="page-content">
            <h1>Teklifi Revize Et</h1>
            <p class="lead">Teklif No: <strong><?php echo htmlspecialchars($original_proposal_no); ?></strong></p>

            <form action="teklif_revize_et.php?id=<?php echo $id; ?>" method="POST" id="teklifFormu">
                <input type="hidden" name="original_proposal_id" value="<?php echo $original_proposal_id; ?>">
                <input type="hidden" name="original_proposal_no" value="<?php echo $original_proposal_no; ?>">
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label">Müşteri</label>
                        <select class="form-control" id="customer_id" name="customer_id" required>
                            <option value="<?php echo $teklif['customer_id']; ?>" selected>
                                <?php $cust_stmt = $pdo->prepare("SELECT unvan FROM customers WHERE id=?"); $cust_stmt->execute([$teklif['customer_id']]); echo $cust_stmt->fetchColumn(); ?>
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="proposal_date" class="form-label">Teklif Tarihi</label>
                        <input type="date" class="form-control" id="proposal_date" name="proposal_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="currency" class="form-label">Para Birimi</label>
                        <select class="form-control" id="currency" name="currency">
                            <option value="TL" <?php if($teklif['currency']=='TL') echo 'selected'; ?>>Türk Lirası (TL)</option>
                            <option value="USD" <?php if($teklif['currency']=='USD') echo 'selected'; ?>>ABD Doları (USD)</option>
                            <option value="EUR" <?php if($teklif['currency']=='EUR') echo 'selected'; ?>>Euro (EUR)</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table" id="teklifKalemleri">
                        <thead class="table-dark">
                             <tr>
                                <th style="width: 5%;">Görsel</th><th style="width: 30%;">Ürün/Hizmet</th><th style="width: 10%;">Adet</th>
                                <th style="width: 15%;">Birim Fiyat</th><th style="width: 15%;">İskonto (%)</th><th style="width: 20%;">Toplam</th><th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($teklif_items as $i => $item): ?>
                            <tr>
                                <td><img src="<?php echo htmlspecialchars($item['fotograf_yolu'] ?? 'assets/images/placeholder.png'); ?>" class="product-image" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"></td>
                                <td>
                                    <select class="form-control product-select" name="products[<?php echo $i; ?>][id]">
                                        <option value="<?php echo $item['product_id']; ?>" selected><?php echo htmlspecialchars($item['text']); ?></option>
                                    </select>
                                </td>
                                <td><input type="number" class="form-control quantity" name="products[<?php echo $i; ?>][quantity]" value="<?php echo $item['quantity']; ?>" min="1"></td>
                                <td><input type="number" class="form-control unit-price" name="products[<?php echo $i; ?>][unit_price]" value="<?php echo $item['unit_price']; ?>" step="0.01"></td>
                                <td><div class="input-group"><input type="number" class="form-control discount" name="products[<?php echo $i; ?>][discount]" value="<?php echo $item['discount_percent']; ?>" min="0" max="100" step="1"><span class="input-group-text">%</span></div></td>
                                <td><input type="text" class="form-control text-end fw-bold total-price" name="products[<?php echo $i; ?>][total]" value="<?php echo $item['total_price']; ?>" readonly></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-info" id="urunEkleBtn"><i class="fas fa-plus"></i> Ürün Ekle</button>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <label for="revision_note" class="form-label"><strong>Revizyon Notu (*)</strong></label>
                        <textarea name="revision_note" id="revision_note" class="form-control" rows="4" placeholder="Bu revizyonun neden yapıldığını açıklayın. Örn: Müşteri talebi üzerine 2 adet ürün eklendi, fiyatta %5 indirim yapıldı." required></textarea>
                    </div>
                    <div class="col-md-6">
                        <table class="table totals-table">
                            <!-- Toplamlar buraya gelecek -->
                        </table>
                    </div>
                </div>

                <hr class="my-4">
                <button type="submit" class="btn btn-primary btn-lg">Revizyonu Kaydet</button>
                <a href="teklif_listesi.php" class="btn btn-secondary btn-lg">Vazgeç</a>
            </form>
        </div>
    </div>
</div>

<?php 
// Footer'ı çağırdığımızda, içindeki JavaScript teklif oluşturma mantığı bu sayfada da çalışacak.
include 'partials/footer.php'; 
?>
<script>
// Sayfa yüklendiğinde mevcut kalemler için Select2'yi ve toplamları başlat
$(document).ready(function(){
    $('#teklifKalemleri tbody .product-select').each(function(){
        $(this).select2({
            placeholder: 'Ürün arayın...',
            ajax: { url: 'api_get_products.php', dataType: 'json', delay: 250, processResults: data => ({ results: data.results }), cache: true }
        });
    });
    // Mevcut toplamları hesaplamak için fonksiyonu çağır
    // 'footer.php' içindeki updateAllCalculations() fonksiyonu bu işi görecek.
    // Ancak ondan önce totals-table'ın içini doldurmamız lazım.
    var totalsHTML = `
        <tbody>
            <tr><th class="align-middle">ARA TOPLAM</th><td class="text-end" id="araToplam">0.00</td></tr>
            <tr><th class="align-middle">İSKONTO TOPLAMI</th><td class="text-end text-danger" id="iskontoToplami">(0.00)</td></tr>
            <tr><th class="align-middle"><div class="d-flex justify-content-end align-items-center"><span>KDV</span><div class="input-group ms-2" style="width: 90px;"><input type="number" class="form-control form-control-sm text-center" id="kdvOrani" value="<?php echo $teklif['tax_rate']; ?>" step="1"><span class="input-group-text">%</span></div></div></th><td class="text-end" id="kdvTutari">0.00</td></tr>
            <tr class="grand-total-row"><th class="align-middle">GENEL TOPLAM</th><td class="text-end" id="genelToplam">0.00</td></tr>
        </tbody>
    `;
    $('.totals-table').html(totalsHTML);
    // Şimdi hesaplamayı tetikle
    updateAllCalculations();
});
</script>