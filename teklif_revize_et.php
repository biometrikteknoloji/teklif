<?php
require 'core/functions.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require 'config/database.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) { header('Location: teklif_listesi.php'); exit(); }

// FORM GÖNDERİLDİĞİNDE...
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $original_proposal_id = $_POST['original_proposal_id'];
    $last_proposal_id_to_revise = $_POST['last_revised_id'];

    $stmt_rev = $pdo->prepare("SELECT MAX(revision_number) as max_rev FROM proposals WHERE id = ? OR original_proposal_id = ?");
    $stmt_rev->execute([$original_proposal_id, $original_proposal_id]);
    $last_revision_number = $stmt_rev->fetchColumn() ?? 0;
    $new_revision_number = $last_revision_number + 1;
    
    $original_proposal_no_base = preg_replace('/-R\d+$/', '', $_POST['original_proposal_no']);
    $proposal_no = $original_proposal_no_base . "-R" . $new_revision_number;

    $customer_id = $_POST['customer_id'];
    $proposal_date = $_POST['proposal_date'];
    $currency = $_POST['currency'];
    $revision_note = trim($_POST['revision_note']);
    $subject = $_POST['subject'] ?? 'Teklif';
    $contact_person = $_POST['contact_person'] ?? '';
    $products = $_POST['products'] ?? [];
    $kdv_rate = (float)($_POST['kdv_rate_hidden'] ?? 20);
    $genel_iskonto_tutar = (float)($_POST['genelIskontoTutar'] ?? 0);

    $sub_total = 0;
    foreach ($products as $p) {
        $sub_total += (float)($p['quantity']??0) * (float)($p['unit_price']??0);
    }
    $total_discount = $genel_iskonto_tutar;
    $net_total = $sub_total - $total_discount;
    $tax_amount = $net_total * ($kdv_rate / 100);
    $grand_total = $net_total + $tax_amount;

    $pdo->beginTransaction();
    try {
        // Yeni revizyonu ekle
        $sql = "INSERT INTO proposals (proposal_no, original_proposal_id, revision_number, revision_note, customer_id, user_id, status_id, proposal_date, currency, sub_total, total_discount, tax_rate, tax_amount, grand_total, subject, contact_person) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$proposal_no, $original_proposal_id, $new_revision_number, $revision_note, $customer_id, $_SESSION['user_id'], 2, $proposal_date, $currency, $sub_total, $total_discount, $kdv_rate, $tax_amount, $grand_total, $subject, $contact_person]);
        $new_proposal_id = $pdo->lastInsertId();

        // Yeni kalemleri ekle
        $sql_item = "INSERT INTO proposal_items (proposal_id, product_id, product_name, quantity, unit_price, discount_percent, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);
        foreach ($products as $p) {
            $p_name_stmt = $pdo->prepare("SELECT urun_adi FROM products WHERE id = ?"); $p_name_stmt->execute([$p['id']]);
            $p_name = $p_name_stmt->fetchColumn();
            $lt = (float)$p['quantity'] * (float)$p['unit_price'];
            $stmt_item->execute([$new_proposal_id, $p['id'], $p_name, $p['quantity'], $p['unit_price'], 0, $lt]);
        }

        // Eski teklifin durumunu güncelle (status_id = 3 -> Revize Edildi)
        $stmt_update_status = $pdo->prepare("UPDATE proposals SET status_id = 3 WHERE id = ?");
        $stmt_update_status->execute([$last_proposal_id_to_revise]);

        add_log($pdo, 'TEKLİF REVİZE EDİLDİ', 'Eski Teklif ID: ' . $last_proposal_id_to_revise . ', Yeni Teklif No: ' . $proposal_no);
        $pdo->commit();
        header("Location: teklif_listesi.php?status=revised");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("HATA: Revizyon oluşturulamadı. " . $e->getMessage());
    }
}

// SAYFA YÜKLENİRKEN VERİ ÇEKME
$stmt_last_rev = $pdo->prepare("SELECT * FROM proposals WHERE id = ? OR original_proposal_id = ? ORDER BY revision_number DESC LIMIT 1");
$stmt_last_rev->execute([$id, $id]);
$teklif = $stmt_last_rev->fetch(PDO::FETCH_ASSOC);
if (!$teklif) { header('Location: teklif_listesi.php'); exit(); }

$stmt_items = $pdo->prepare("SELECT pi.*, p.urun_adi as text, p.fotograf_yolu FROM proposal_items pi JOIN products p ON pi.product_id = p.id WHERE pi.proposal_id = ?");
$stmt_items->execute([$teklif['id']]);
$teklif_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

$stmt_customers = $pdo->query("SELECT id, unvan as text, yetkili_ismi FROM customers ORDER BY unvan ASC");
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
            <p class="lead">Orijinal Teklif No: <strong><?php echo htmlspecialchars($original_proposal_no); ?></strong></p>

            <form action="teklif_revize_et.php?id=<?php echo $id; ?>" method="POST" id="teklifFormu">
                <input type="hidden" name="original_proposal_id" value="<?php echo $original_proposal_id; ?>">
                <input type="hidden" name="original_proposal_no" value="<?php echo $original_proposal_no; ?>">
                <input type="hidden" name="last_revised_id" value="<?php echo $teklif['id']; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-5"><label class="form-label">Müşteri</label><input type="text" class="form-control" value="<?php $cust_stmt = $pdo->prepare("SELECT unvan FROM customers WHERE id=?"); $cust_stmt->execute([$teklif['customer_id']]); echo htmlspecialchars($cust_stmt->fetchColumn()); ?>" readonly><input type="hidden" name="customer_id" value="<?php echo $teklif['customer_id']; ?>"></div>
                    <div class="col-md-4"><label for="subject" class="form-label">Teklif Konusu</label><input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($teklif['subject'] ?? ''); ?>"></div>
                    <div class="col-md-3"><label for="proposal_date" class="form-label">Teklif Tarihi</label><input type="date" class="form-control" id="proposal_date" name="proposal_date" value="<?php echo date('Y-m-d'); ?>"></div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-5"><label for="contact_person" class="form-label">Kime (Yetkili)</label><input type="text" class="form-control" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($teklif['contact_person'] ?? ''); ?>"></div>
                    <div class="col-md-4"></div>
                    <div class="col-md-3"><label for="currency" class="form-label">Para Birimi</label><select class="form-control" id="currency" name="currency"><option value="TL" <?php if($teklif['currency']=='TL') echo 'selected'; ?>>Türk Lirası (TL)</option><option value="USD" <?php if($teklif['currency']=='USD') echo 'selected'; ?>>ABD Doları (USD)</option><option value="EUR" <?php if($teklif['currency']=='EUR') echo 'selected'; ?>>Euro (EUR)</option></select></div>
                </div>

                <div class="table-responsive">
                    <table class="table" id="teklifKalemleri">
                        <thead class="table-dark">
                            <tr><th style="width: 5%;">Görsel</th><th style="width: 45%;">Ürün/Hizmet</th><th style="width: 15%;">Adet</th><th style="width: 15%;">Birim Fiyat</th><th style="width: 20%;">Toplam</th><th style="width: 5%;"></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($teklif_items as $i => $item): ?>
                            <tr>
                                <td><img src="<?php echo htmlspecialchars(!empty($item['fotograf_yolu']) && file_exists($item['fotograf_yolu']) ? $item['fotograf_yolu'] : 'assets/images/placeholder.png'); ?>" class="product-image" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"></td>
                                <td><select class="form-control product-select" name="products[<?php echo $i; ?>][id]"><option value="<?php echo $item['product_id']; ?>" selected><?php echo htmlspecialchars($item['text']); ?></option></select></td>
                                <td><input type="number" class="form-control quantity" name="products[<?php echo $i; ?>][quantity]" value="<?php echo $item['quantity']; ?>" step="any"></td>
                                <td><input type="number" class="form-control unit-price" name="products[<?php echo $i; ?>][unit_price]" value="<?php echo $item['unit_price']; ?>" step="any"></td>
                                <td><input type="text" class="form-control text-end fw-bold total-price" readonly></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-start gap-2">
                    <button type="button" class="btn btn-info" id="urunEkleBtn"><i class="fas fa-plus"></i> Ürün Ekle</button>
                    <button type="button" class="btn btn-outline-secondary" id="toggleDiscountBtn"><i class="fas fa-percent"></i> İskonto Uygula</button>
                </div>

                <!-- Toplamlar Bölümü (YENİ DİNAMİK YAPI) -->
                <div class="row mt-4">
				                    <div class="col-md-6">
                        <label for="revision_note" class="form-label"><strong>Revizyon Notu (*)</strong></label>
                        <textarea name="revision_note" id="revision_note" class="form-control" rows="4" placeholder="Bu revizyonun neden yapıldığını açıklayın." required></textarea>
                    </div>
                    <div class="col-md-6 col-lg-5 ms-auto">
                        <div class="totals-summary p-3 border rounded-3 bg-light">
                            
                            <!-- Bu satırlar iskonto varsa görünür olacak -->
                            <div class="d-flex justify-content-between mb-2 discount-related d-none">
                                <span class="text-muted">TOPLAM</span>
                                <span id="toplamBrut" class="fw-bold">0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 discount-row d-none">
                                <span class="text-danger">İSKONTO</span>
                                <div class="input-group" style="width: 180px;">
                                    <input type="number" class="form-control form-control-sm" id="genelIskontoYuzde" placeholder="%">
                                    <span class="input-group-text p-1">%</span>
                                    <input type="number" class="form-control form-control-sm" id="genelIskontoTutar" name="genelIskontoTutar" placeholder="Tutar">
                                </div>
                            </div>
                            <hr class="my-2 discount-related d-none">
                            
                            <!-- Bu satır her zaman görünür -->
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
                </div>
                
                <input type="hidden" name="kdv_rate_hidden" id="kdv_rate_hidden">
                <hr class="my-4">
                <button type="submit" class="btn btn-primary btn-lg">Revizyonu Kaydet</button>
                <a href="teklif_listesi.php" class="btn btn-secondary btn-lg">Vazgeç</a>
            </form>
        </div>
    </div>
</div>
<?php include 'partials/footer.php'; ?>