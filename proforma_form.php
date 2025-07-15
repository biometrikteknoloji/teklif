<?php
require 'core/functions.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require 'config/database.php';

// 1. ÖNCE $id değişkenini URL'den alıyoruz. 
$id = $_GET['id'] ?? null;

// 2. ŞİMDİ $id'nin dolu olup olmadığını kontrol ederek düzenleme modunda olup olmadığımıza karar veriyoruz.
$is_editing = !is_null($id);

// 3. Değişkenleri her zaman tanımlı başlatıyoruz.
$proforma = null;
$proforma_items = []; 

// Form için gerekli verileri çek
$customers = $pdo->query("SELECT id, unvan as text, yetkili_ismi FROM customers WHERE market_type = 'Yurt Dışı' ORDER BY unvan ASC")->fetchAll(PDO::FETCH_ASSOC);
$bank_accounts = $pdo->query("SELECT id, bank_name, currency FROM bank_accounts ORDER BY bank_name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($is_editing) {
    $stmt = $pdo->prepare("SELECT * FROM proformas WHERE id = ?");
    $stmt->execute([$id]);
    $proforma = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$proforma) { header('Location: proforma_listesi.php'); exit('Proforma bulunamadı.'); }

    $stmt_items = $pdo->prepare("
        SELECT pi.*, p.urun_adi as text
        FROM proforma_items pi 
        LEFT JOIN products p ON pi.product_id = p.id 
        WHERE pi.proforma_id = ? 
        ORDER BY pi.id ASC
    ");
    $stmt_items->execute([$id]);
    $proforma_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
}

// POST işlemleri (Bu blokta değişiklik yok, olduğu gibi kalıyor)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

    $sub_total = 0;
    foreach ($items as $item) { $sub_total += (float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0); }
    $total_amount = $sub_total;

    $pdo->beginTransaction();
    try {
        if ($proforma_id) {
            $sql = "UPDATE proformas SET customer_id=?, attention=?, proforma_date=?, expiry_date=?, currency=?, payment_terms=?, delivery_terms=?, country_of_origin=?, freight_type=?, bank_account_id=?, document_type=?, notes=?, sub_total=?, total_amount=? WHERE id=?";
            $params = [$customer_id, $attention, $proforma_date, $expiry_date, $currency, $payment_terms, $delivery_terms, $country_of_origin, $freight_type, $bank_account_id, $document_type, $notes, $sub_total, $total_amount, $proforma_id];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $stmt_delete = $pdo->prepare("DELETE FROM proforma_items WHERE proforma_id = ?");
            $stmt_delete->execute([$proforma_id]);
        } else {
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
        <div class="page-content">
            <h1><?php echo $is_editing ? 'Proformayı Düzenle' : 'Yeni Proforma Oluştur'; ?></h1>
            <form method="POST" id="proformaForm">
                <?php if($is_editing): ?><input type="hidden" name="proforma_id" value="<?php echo htmlspecialchars($id); ?>"><?php endif; ?>

                <!-- Müşteri ve Temel Bilgiler -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h5 class="mb-0">Müşteri Bilgileri</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_id" class="form-label">Müşteri (*)</label>
                                <select class="form-control" id="customer_id" name="customer_id" required>
                                    <option value="">Müşteri Seçin...</option>
                                    <?php foreach($customers as $customer_data): ?>
                                        <option value="<?php echo $customer_data['id']; ?>" data-attention="<?php echo htmlspecialchars($customer_data['yetkili_ismi']); ?>" <?php if(isset($proforma['customer_id']) && $proforma['customer_id'] == $customer_data['id']) echo 'selected'; ?>>
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
                            <div class="col-md-3 mb-3"><label class="form-label">Tarih (*)</label><input type="date" class="form-control" name="proforma_date" value="<?php echo htmlspecialchars($proforma['proforma_date'] ?? date('Y-m-d')); ?>" required></div>
                            <div class="col-md-3 mb-3"><label class="form-label">Geçerlilik Tarihi</label><input type="date" class="form-control" name="expiry_date" value="<?php echo htmlspecialchars($proforma['expiry_date'] ?? ''); ?>"></div>
                            <div class="col-md-3 mb-3"><label class="form-label">Para Birimi (*)</label><select class="form-control" id="currency" name="currency"><option value="EUR" <?php if(($proforma['currency'] ?? 'EUR') == 'EUR') echo 'selected'; ?>>EUR</option><option value="USD" <?php if(($proforma['currency'] ?? '') == 'USD') echo 'selected'; ?>>USD</option><option value="TL" <?php if(($proforma['currency'] ?? '') == 'TL') echo 'selected'; ?>>TL</option></select></div>
                            <div class="col-md-3 mb-3"><label class="form-label">Belge Tipi (*)</label><select class="form-control" name="document_type"><option value="PROFORMA INVOICE" <?php if(($proforma['document_type'] ?? 'PROFORMA INVOICE') == 'PROFORMA INVOICE') echo 'selected'; ?>>Proforma Invoice</option><option value="INVOICE" <?php if(($proforma['document_type'] ?? '') == 'INVOICE') echo 'selected'; ?>>Invoice</option></select></div>
                        </div>
                    </div>
                </div>

                <!-- Ürün Kalemleri -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h5 class="mb-0">Ürün/Hizmet Kalemleri</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40%;">Açıklama</th>
                                        <th style="width: 8%;">Miktar</th>
                                        <th style="width: 8%;">Birim</th>
                                        <th style="width: 12%;">Birim Fiyat</th>
                                        <th style="width: 12%;">Toplam</th>
                                        <th style="width: 15%;">Renk Kodu/Link</th>
                                        <th style="width: 5%;" class="text-center">Sil</th>
                                    </tr>
                                </thead>
                                <tbody id="proformaItems">
                                    <?php if ($is_editing && !empty($proforma_items)): ?>
                                        <?php foreach($proforma_items as $index => $item): ?>
                                            <tr>
                                                <td>
                                                    <select class="form-control product-select" name="items[<?php echo $index; ?>][product_id]"><option value="<?php echo htmlspecialchars($item['product_id']); ?>" selected><?php echo htmlspecialchars($item['text']); ?></option></select>
                                                    <textarea class="form-control mt-1" name="items[<?php echo $index; ?>][description]" placeholder="Detaylı Açıklama"><?php echo htmlspecialchars($item['description']); ?></textarea>
                                                </td>
                                                <td><input type="number" class="form-control quantity" name="items[<?php echo $index; ?>][quantity]" value="<?php echo htmlspecialchars($item['quantity']); ?>" step="1" min="1"></td>
                                                <td><input type="text" class="form-control" name="items[<?php echo $index; ?>][unit]" value="<?php echo htmlspecialchars($item['unit']); ?>"></td>
                                                <td><input type="number" class="form-control unit-price" name="items[<?php echo $index; ?>][unit_price]" value="<?php echo htmlspecialchars($item['unit_price']); ?>" step="0.01"></td>
                                                <td><input type="text" class="form-control total-price" value="0.00" readonly></td>
                                                <td><input type="text" class="form-control" name="items[<?php echo $index; ?>][colour_code]" value="<?php echo htmlspecialchars($item['colour_code']); ?>"></td>
                                                <td class="text-center"><button type="button" class="btn btn-danger btn-sm removeItemBtn"><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-info mt-2" id="addItemBtn"><i class="fas fa-plus"></i> Kalem Ekle</button>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="row justify-content-end"><div class="col-md-4"><div class="d-flex justify-content-between"><strong>Ara Toplam:</strong><span id="subTotalDisplay">0.00</span></div></div></div>
                    </div>
                </div>

                <!-- Diğer form alanları... -->
                
                <hr class="my-4">
                <button type="submit" class="btn btn-primary btn-lg">Kaydet</button>
                <a href="proforma_listesi.php" class="btn btn-secondary btn-lg">Vazgeç</a>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<!-- Nihai JavaScript Bloğu -->
<script>
$(document).ready(function () {
    let itemIndex = <?php echo !empty($proforma_items) ? count($proforma_items) : 0; ?>;
    const proformaItemsTbody = $('#proformaItems');

    // Müşteri arama kutusunu başlat
    $('#customer_id').select2({ theme: 'bootstrap-5', placeholder: 'Müşteri seçin veya arayın...' });

    // Mevcut satırlardaki ürün arama kutularını başlat
    $('#proformaItems .product-select').each(function() {
        initializeProductSelect(this);
    });

    // "+ Kalem Ekle" butonu
    $('#addItemBtn').on('click', function() {
        let newRowHtml = `
            <tr>
                <td>
                    <select class="form-control product-select" name="items[${itemIndex}][product_id]"></select>
                    <textarea class="form-control mt-1" name="items[${itemIndex}][description]" placeholder="Detaylı Açıklama"></textarea>
                </td>
                <td><input type="number" class="form-control quantity" name="items[${itemIndex}][quantity]" value="1" step="1" min="1"></td>
                <td><input type="text" class="form-control" name="items[${itemIndex}][unit]" value="Pcs"></td>
                <td><input type="number" class="form-control unit-price" name="items[${itemIndex}][unit_price]" value="0.00" step="0.01"></td>
                <td><input type="text" class="form-control total-price" readonly></td>
                <td><input type="text" class="form-control" name="items[${itemIndex}][colour_code]"></td>
                <td class="text-center"><button type="button" class="btn btn-danger btn-sm removeItemBtn"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
        proformaItemsTbody.append(newRowHtml);
        initializeProductSelect(`[name="items[${itemIndex}][product_id]"]`);
        itemIndex++;
    });

    // Silme butonu için olay dinleyici
    proformaItemsTbody.on('click', '.removeItemBtn', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    // Ürün arama kutusunu başlatan fonksiyon
    function initializeProductSelect(selector) {
        $(selector).select2({
            theme: 'bootstrap-5',
            placeholder: 'Ürün ara...',
            minimumInputLength: 1,
            ajax: {
                url: 'api_search_products.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term, currency: $('#currency').val() };
                },
                processResults: function(data) {
                    return { results: data.items };
                }
            }
        });
    }

    // Ürün seçildiğinde olay dinleyici
    proformaItemsTbody.on('select2:select', '.product-select', function(e) {
        let data = e.params.data;
        let row = $(this).closest('tr');
        row.find('textarea[name$="[description]"]').val(data.urun_aciklamasi || '');
        row.find('.unit-price').val(data.price || '0.00');
        calculateRowTotal(row);
        calculateTotals();
    });

    // Hesaplama fonksiyonları...
    function calculateRowTotal(row) {
        let quantity = parseFloat(row.find('.quantity').val()) || 0;
        let unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        row.find('.total-price').val((quantity * unitPrice).toFixed(2));
    }
    
    function calculateTotals() {
        let subTotal = 0;
        $('#proformaItems tr').each(function() {
            subTotal += parseFloat($(this).find('.total-price').val()) || 0;
        });
        $('#subTotalDisplay').text(subTotal.toFixed(2));
    }
    
    proformaItemsTbody.on('input', '.quantity, .unit-price', function() {
        calculateRowTotal($(this).closest('tr'));
        calculateTotals();
    });

    // Sayfa ilk yüklendiğinde mevcut kalemlerin toplamlarını hesapla
    $('#proformaItems tr').each(function() {
        calculateRowTotal($(this));
    });
    calculateTotals();
});
</script>