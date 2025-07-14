<?php
require 'core/functions.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require 'config/database.php';

$id = $_GET['id'] ?? null;
$proforma = null;
$proforma_items = [];

// Form için gerekli verileri çek
$customers = $pdo->query("SELECT id, unvan as text, yetkili_ismi, email FROM customers ORDER BY unvan ASC")->fetchAll(PDO::FETCH_ASSOC);
$bank_accounts = $pdo->query("SELECT id, bank_name, currency FROM bank_accounts ORDER BY bank_name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($id) { // Düzenleme Modu
    $stmt = $pdo->prepare("SELECT * FROM proformas WHERE id = ?");
    $stmt->execute([$id]);
    $proforma = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$proforma) { header('Location: proforma_listesi.php'); exit(); }

    $stmt_items = $pdo->prepare("SELECT pi.*, p.urun_adi as text FROM proforma_items pi LEFT JOIN products p ON pi.product_id = p.id WHERE pi.proforma_id = ? ORDER BY pi.id ASC");
    $stmt_items->execute([$id]);
    $proforma_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formdan gelen tüm verileri al
    $proforma_id = $_POST['proforma_id'] ?? null;
    $customer_id = $_POST['customer_id'];
    $attention = $_POST['attention'];
    $proforma_date = $_POST['proforma_date'];
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $currency = $_POST['currency'];
    $payment_terms = $_POST['payment_terms'];
    $delivery_terms = $_POST['delivery_terms'];
    $country_of_origin = $_POST['country_of_origin'];
    $freight_type = $_POST['freight_type'];
    $bank_account_id = $_POST['bank_account_id'] ?: null;
    $document_type = $_POST['document_type'];
    $notes = $_POST['notes'];
    $items = $_POST['items'] ?? [];

    // Toplamları PHP'de yeniden hesapla
    $sub_total = 0;
    foreach ($items as $item) {
        $sub_total += (float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0);
    }
    $total_amount = $sub_total; // Proformada şimdilik sadece subtotal var

    $pdo->beginTransaction();
    try {
        if ($proforma_id) { // GÜNCELLEME
            $sql = "UPDATE proformas SET customer_id=?, attention=?, proforma_date=?, expiry_date=?, currency=?, payment_terms=?, delivery_terms=?, country_of_origin=?, freight_type=?, bank_account_id=?, document_type=?, notes=?, sub_total=?, total_amount=? WHERE id=?";
            $params = [$customer_id, $attention, $proforma_date, $expiry_date, $currency, $payment_terms, $delivery_terms, $country_of_origin, $freight_type, $bank_account_id, $document_type, $notes, $sub_total, $total_amount, $proforma_id];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $stmt_delete = $pdo->prepare("DELETE FROM proforma_items WHERE proforma_id = ?");
            $stmt_delete->execute([$proforma_id]);
        } else { // YENİ KAYIT
            // Numara oluşturma
            $proforma_no = "PI" . date("Ymd") . "-" . ($pdo->query("SELECT COUNT(*) FROM proformas WHERE proforma_date = CURDATE()")->fetchColumn() + 1);
            $sql = "INSERT INTO proformas (proforma_no, customer_id, user_id, attention, proforma_date, expiry_date, currency, payment_terms, delivery_terms, country_of_origin, freight_type, bank_account_id, document_type, notes, sub_total, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$proforma_no, $customer_id, $_SESSION['user_id'], $attention, $proforma_date, $expiry_date, $currency, $payment_terms, $delivery_terms, $country_of_origin, $freight_type, $bank_account_id, $document_type, $notes, $sub_total, $total_amount];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $proforma_id = $pdo->lastInsertId();
        }
        
        $sql_item = "INSERT INTO proforma_items (proforma_id, product_id, description, quantity, unit, unit_price, total_price, colour_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);
        foreach ($items as $item) {
            $total_price = (float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0);
            $stmt_item->execute([$proforma_id, $item['product_id'] ?: null, $item['description'], $item['quantity'], $item['unit'], $item['unit_price'], $total_price, $item['colour_code']]);
        }
        
        $pdo->commit();
        header("Location: proforma_listesi.php?status=success");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("HATA: Proforma kaydedilemedi. " . $e->getMessage());
    }
}

include 'partials/header.php';
?>
<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <!-- ... -->
        </div>
        <div class="page-content">
            <h1><?php echo $id ? 'Proformayı Düzenle' : 'Yeni Proforma Oluştur'; ?></h1>
            <form method="POST">
                <?php if($id): ?><input type="hidden" name="proforma_id" value="<?php echo $id; ?>"><?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="customer_id" class="form-label">Müşteri (*)</label>
                        <select class="form-control" id="customer_id" name="customer_id" required>
                            <option value="">Müşteri Seçin...</option>
                            <?php foreach($customers as $customer_data): ?>
                                <option value="<?php echo $customer_data['id']; ?>" 
                                        data-attention="<?php echo htmlspecialchars($customer_data['yetkili_ismi']); ?>"
                                        <?php if(($proforma['customer_id'] ?? '') == $customer_data['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($customer_data['text']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="attention" class="form-label">Dikkatine (ATTN)</label>
                        <input type="text" class="form-control" id="attention" name="attention" value="<?php echo htmlspecialchars($proforma['attention'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3"><label class="form-label">Tarih (*)</label><input type="date" class="form-control" name="proforma_date" value="<?php echo $proforma['proforma_date'] ?? date('Y-m-d'); ?>" required></div>
                    <div class="col-md-3 mb-3"><label class="form-label">Geçerlilik Tarihi</label><input type="date" class="form-control" name="expiry_date" value="<?php echo $proforma['expiry_date'] ?? ''; ?>"></div>
                    <div class="col-md-3 mb-3"><label class="form-label">Para Birimi (*)</label><select class="form-control" name="currency"><option value="EUR" <?php if(($proforma['currency'] ?? 'EUR') == 'EUR') echo 'selected'; ?>>EUR</option><option value="USD" <?php if(($proforma['currency'] ?? '') == 'USD') echo 'selected'; ?>>USD</option><option value="TL" <?php if(($proforma['currency'] ?? '') == 'TL') echo 'selected'; ?>>TL</option></select></div>
                    <div class="col-md-3 mb-3"><label class="form-label">Belge Tipi (*)</label><select class="form-control" name="document_type"><option value="PROFORMA INVOICE" <?php if(($proforma['document_type'] ?? '') == 'PROFORMA INVOICE') echo 'selected'; ?>>Proforma Invoice</option><option value="INVOICE" <?php if(($proforma['document_type'] ?? '') == 'INVOICE') echo 'selected'; ?>>Invoice</option></select></div>
                </div>

                <h5 class="mt-4">Ürün/Hizmet Kalemleri</h5>
                <div class="table-responsive">
                    <table class="table" id="proformaItems">
                        <!-- tablo başlıkları... -->
                        <tbody>
                            <!-- PHP ile mevcut kalemleri bas -->
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-info mt-2" id="addItemBtn"><i class="fas fa-plus"></i> Kalem Ekle</button>

                <h5 class="mt-4">Şartlar ve Notlar</h5>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Ödeme Şartları</label><input type="text" class="form-control" name="payment_terms" value="<?php echo htmlspecialchars($proforma['payment_terms'] ?? '%100 CASH BEFORE SHIPMENT'); ?>"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Teslimat Şartları</label><input type="text" class="form-control" name="delivery_terms" value="<?php echo htmlspecialchars($proforma['delivery_terms'] ?? '1 week'); ?>"></div>
                </div>
                 <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Menşei Ülke</label><input type="text" class="form-control" name="country_of_origin" value="<?php echo htmlspecialchars($proforma['country_of_origin'] ?? 'TURKEY'); ?>"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Nakliye Tipi</label><input type="text" class="form-control" name="freight_type" value="<?php echo htmlspecialchars($proforma['freight_type'] ?? ''); ?>"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Genel Notlar</label>
                    <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($proforma['notes'] ?? ''); ?></textarea>
                </div>
                
                <h5 class="mt-4">Banka Bilgileri</h5>
                <div class="mb-3">
                    <select class="form-control" name="bank_account_id">
                        <option value="">Banka Seçilmedi</option>
                        <?php foreach($bank_accounts as $account): ?>
                            <option value="<?php echo $account['id']; ?>" <?php if(($proforma['bank_account_id'] ?? '') == $account['id']) echo 'selected'; ?>><?php echo htmlspecialchars($account['bank_name'] . ' (' . $account['currency'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr class="my-4">
                <button type="submit" class="btn btn-primary btn-lg">Kaydet</button>
                <a href="proforma_listesi.php" class="btn btn-secondary btn-lg">Vazgeç</a>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
<!-- Bu sayfa için özel JavaScript -->
<script>
// Bu script, satır ekleme, silme, otomatik doldurma gibi işlemleri yönetecek.
// Detayları bir sonraki adımda ekleyebiliriz.
</script>