<?php
// proforma_form.php (NİHAİ, TAM VE BÜTÜN SÜRÜM)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'core/functions.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require 'config/database.php';

// === DEĞİŞKEN TANIMLAMALARI ===
$id = $_GET['id'] ?? null;
$is_editing = !is_null($id);
$proforma = null;
$proforma_items = [];
$customers = [];
$bank_accounts = [];

// === VERİTABANI İŞLEMLERİ ===
try {
    // Proforma için sadece Yurt Dışı müşterileri çekiyoruz
    $customers = $pdo->query("SELECT id, unvan as text, yetkili_ismi FROM customers WHERE market_type = 'Yurt Dışı' ORDER BY unvan ASC")->fetchAll(PDO::FETCH_ASSOC);
    $bank_accounts = $pdo->query("SELECT id, bank_name, currency FROM bank_accounts ORDER BY bank_name ASC")->fetchAll(PDO::FETCH_ASSOC);

    if ($is_editing) {
        $stmt = $pdo->prepare("SELECT * FROM proformas WHERE id = ?");
        $stmt->execute([$id]);
        $proforma = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$proforma) {
            header('Location: proforma_listesi.php');
            exit('Proforma bulunamadı.');
        }

        $stmt_items = $pdo->prepare("SELECT pi.*, p.urun_adi as text, p.fotograf_yolu FROM proforma_items pi LEFT JOIN products p ON pi.product_id = p.id WHERE pi.proforma_id = ? ORDER BY pi.id ASC");
        $stmt_items->execute([$id]);
        $proforma_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// === POST İŞLEMLERİ ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... Sizin POST kodunuz buraya gelecek ...
    // Bu kısım projenin son aşamasıdır, önce arayüzün tam çalışması önemlidir.
}

// === HTML BAŞLANGICI ===
include 'partials/header.php';
?>
<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="page-content">
            <h1><?php echo $is_editing ? 'Proformayı Düzenle' : 'Yeni Proforma Oluştur'; ?></h1>
            <form method="POST" id="proformaForm" action="proforma_form.php<?php echo $is_editing ? '?id='.htmlspecialchars($id) : ''; ?>">
                <?php if($is_editing): ?><input type="hidden" name="proforma_id" value="<?php echo htmlspecialchars($id); ?>"><?php endif; ?>

                <!-- Müşteri ve Temel Bilgiler (NİHAİ HALİ) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h5 class="mb-0">Müşteri Bilgileri</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_id" class="form-label">Müşteri (*)</label>
                                <select class="form-control" id="customer_id" name="customer_id" required>
                                    <option value="">Müşteri arayın veya seçin...</option>
                                    <?php foreach($customers as $customer_data): ?>
                                        <option value="<?php echo $customer_data['id']; ?>" 
                                                data-attention="<?php echo htmlspecialchars($customer_data['yetkili_ismi']); ?>" 
                                                <?php if(isset($proforma['customer_id']) && $proforma['customer_id'] == $customer_data['id']) echo 'selected'; ?>>
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
                        <!-- ... Diğer üst form alanları ... -->
                    </div>
                </div>

                <!-- Ürün Kalemleri (NİHAİ HALİ) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h5 class="mb-0">Ürün/Hizmet Kalemleri</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 8%;" class="text-center">Görsel</th>
                                        <th style="width: 37%;">Açıklama</th>
                                        <th style="width: 10%;">Adet</th>
                                        <th style="width: 15%;">Birim Fiyat</th>
                                        <th style="width: 15%;">Toplam</th>
                                        <th style="width: 15%;">Renk Kodu/Link</th>
                                        <th style="width: 5%;" class="text-center">Sil</th>
                                    </tr>
                                </thead>
                                <tbody id="proformaItems">
                                    <!-- Mevcut kalemler PHP ile buraya basılır -->
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-info mt-2" id="addItemBtn"><i class="fas fa-plus"></i> Kalem Ekle</button>
                    </div>
                </div>
                
                <hr class="my-4">
                <button type="submit" class="btn btn-primary btn-lg">Proformayı Kaydet</button>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<!-- proforma_form.php'nin en altına eklenecek -->
<script>
$(document).ready(function () {
    let itemIndex = $('#proformaItems tr').length;
    const proformaItemsTbody = $('#proformaItems');
    
    $('#customer_id').select2({
        theme: 'bootstrap-5',
        placeholder: "Müşteri arayın veya seçin...",
    }).on('select2:select', function (e) {
        let attention = $(e.params.data.element).data('attention');
        $('#attention').val(attention || '');
    });

    $('#addItemBtn').on('click', function() {
        let newRowHtml = `
            <tr data-index="${itemIndex}">
                <td><select class="form-control product-select" name="items[${itemIndex}][product_id]" required></select><textarea class="form-control mt-1" name="items[${itemIndex}][description]" placeholder="Detaylı Açıklama"></textarea></td>
                <td><input type="number" class="form-control quantity" name="items[${itemIndex}][quantity]" value="1" step="1" min="1"></td>
                <td><input type="text" class="form-control" name="items[${itemIndex}][unit]" value="Pcs"></td>
                <td><input type="number" class="form-control unit-price" name="items[${itemIndex}][unit_price]" step="0.01"></td>
                <td><input type="text" class="form-control total-price" readonly></td>
                <td class="text-center"><button type="button" class="btn btn-danger btn-sm removeItemBtn"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
        proformaItemsTbody.append(newRowHtml);
        initializeProductSelect(`tr[data-index="${itemIndex}"] .product-select`);
        itemIndex++;
    });

    proformaItemsTbody.on('click', '.removeItemBtn', function() {
        $(this).closest('tr').remove();
    });

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
                    return { q: params.term };
                },
                processResults: function(data, params) {
                    if (data.error) {
                        console.error("API Hatası:", data.error);
                        return { results: [] };
                    }
                    return { results: data.items };
                }
            }
        }).on('select2:select', function(e) {
            let data = e.params.data;
            let row = $(this).closest('tr');
            row.data('product-data', data);
            row.find('textarea[name$="[description]"]').val(data.urun_aciklamasi || '');
            updatePrice(row);
        });
    }

    $('#currency').on('change', function() {
        $('#proformaItems tr').each(function() {
            updatePrice($(this));
        });
    });

    function updatePrice(row) {
        let selectedCurrency = $('#currency').val();
        let productData = row.data('product-data');
        if (productData && productData.prices) {
            let newPrice = productData.prices[selectedCurrency] || '0.00';
            row.find('.unit-price').val(newPrice);
        }
    }
    
    // Mevcut satırlar için Select2'yi başlat
    $('#proformaItems .product-select').each(function() {
        initializeProductSelect(this);
    });

    // Sayfa ilk yüklendiğinde boş satır ekle
    if (itemIndex === 0) {
        $('#addItemBtn').trigger('click');
    }
});
</script>