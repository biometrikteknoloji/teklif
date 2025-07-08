<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require 'config/database.php';

$id = $_GET['id'] ?? null;
$sablon = null;
$sablon_items = []; 

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM proposal_templates WHERE id = ?");
    $stmt->execute([$id]);
    $sablon = $stmt->fetch();
    if (!$sablon) { header('Location: sablon_listesi.php'); exit(); }
    
    // Fiyatları da çekecek şekilde sorguyu güncelledik
    $stmt_items = $pdo->prepare("
        SELECT ti.*, p.urun_adi as text, p.fotograf_yolu,
               (SELECT GROUP_CONCAT(CONCAT(currency, ':', price) SEPARATOR ';') FROM product_prices WHERE product_id = p.id) as prices_str
        FROM proposal_template_items ti 
        JOIN products p ON ti.product_id = p.id 
        WHERE ti.template_id = ? 
        ORDER BY ti.id ASC
    ");
    $stmt_items->execute([$id]);
    $sablon_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Fiyatları JS'in anlayacağı formata çevir
    foreach ($sablon_items as $key => $item) {
        if(!empty($item['prices_str'])) {
            $price_pairs = explode(';', $item['prices_str']);
            $prices = [];
            foreach ($price_pairs as $pair) {
                list($currency, $price) = explode(':', $pair);
                $prices[$currency] = $price;
            }
            $sablon_items[$key]['prices'] = $prices;
        } else {
            $sablon_items[$key]['prices'] = [];
        }
    }
}

// === DEĞİŞİKLİK BURADA: POST BLOĞU DOLDURULDU ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $template_name = $_POST['template_name'];
    $products = $_POST['products'] ?? [];
    $template_id = $_POST['template_id'] ?? null;

    // Veritabanı işlemlerini güvene al
    $pdo->beginTransaction();
    try {
        if ($template_id) { // Eğer template_id varsa, bu bir GÜNCELLEME işlemidir.
            // 1. Ana şablonun adını güncelle
            $stmt = $pdo->prepare("UPDATE proposal_templates SET template_name = ? WHERE id = ?");
            $stmt->execute([$template_name, $template_id]);
            
            // 2. Bu şablona ait eski ürünleri tamamen sil (en temiz yöntem)
            $stmt_delete = $pdo->prepare("DELETE FROM proposal_template_items WHERE template_id = ?");
            $stmt_delete->execute([$template_id]);
        } else { // Eğer template_id yoksa, bu bir YENİ KAYIT işlemidir.
            // 1. Yeni şablonu ana tabloya ekle
            $stmt = $pdo->prepare("INSERT INTO proposal_templates (template_name) VALUES (?)");
            $stmt->execute([$template_name]);
            // 2. Yeni oluşturulan şablonun ID'sini al
            $template_id = $pdo->lastInsertId();
        }
        
        // 3. Formdan gelen tüm ürünleri (güncelleme de olsa, yeni de olsa) yeniden ekle
        $stmt_item = $pdo->prepare("INSERT INTO proposal_template_items (template_id, product_id, quantity) VALUES (?, ?, ?)");
        foreach ($products as $product) {
            // Sadece ID'si ve adedi olan geçerli satırları ekle
            if (!empty($product['id']) && !empty($product['quantity'])) {
                $stmt_item->execute([$template_id, $product['id'], $product['quantity']]);
            }
        }
        
        // Her şey yolunda gittiyse, veritabanı değişikliklerini onayla
        $pdo->commit();
        // Kullanıcıyı başarı mesajıyla listeleme sayfasına yönlendir
        header('Location: sablon_listesi.php?status=success');
        exit();

    } catch (Exception $e) {
        // Herhangi bir hata olursa, tüm işlemleri geri al
        $pdo->rollBack();
        die("HATA: Şablon kaydedilemedi. " . $e->getMessage());
    }
}

include 'partials/header.php';
?>

<!-- ... (HTML VE JAVASCRIPT KISMINDA HİÇBİR DEĞİŞİKLİK YOK) ... -->
<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="user-info">Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!</div>
        </div>
        <div class="page-content">
            <h1><?php echo $id ? 'Şablonu Düzenle' : 'Yeni Şablon Oluştur'; ?></h1>
            <form method="POST">
                <?php if($id): ?>
                    <input type="hidden" name="template_id" value="<?php echo $id; ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="template_name" class="form-label">Şablon Adı (*)</label>
                    <input type="text" class="form-control" id="template_name" name="template_name" value="<?php echo htmlspecialchars($sablon['template_name'] ?? ''); ?>" required>
                </div>
                
                <h5 class="mt-4">Şablon Ürünleri</h5>
                <p class="small text-muted">Bu şablona dahil edilecek ürünleri ve varsayılan adetlerini seçin.</p>
                
                <div class="table-responsive">
                    <table class="table" id="sablonKalemleri">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 5%;">Görsel</th>
                                <th style="width: 45%;">Ürün/Hizmet</th>
                                <th style="width: 15%;">Adet</th>
                                <th style="width: 15%;">Birim Fiyat (Bilgi)</th>
                                <th style="width: 15%;">Toplam (Bilgi)</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($sablon_items as $i => $item): ?>
                            <tr class="align-middle">
                                <td><img src="<?php echo htmlspecialchars(!empty($item['fotograf_yolu']) && file_exists($item['fotograf_yolu']) ? $item['fotograf_yolu'] : 'assets/images/placeholder.png'); ?>" class="product-image"></td>
                                <td>
                                    <select class="form-control product-select" name="products[<?php echo $i; ?>][id]" data-prices='<?php echo json_encode($item['prices']); ?>'>
                                        <option value="<?php echo $item['product_id']; ?>" selected><?php echo htmlspecialchars($item['text']); ?></option>
                                    </select>
                                </td>
                                <td><input type="number" class="form-control quantity" name="products[<?php echo $i; ?>][quantity]" value="<?php echo $item['quantity']; ?>" min="1" step="1"></td>
                                <td><input type="text" class="form-control unit-price text-end" readonly></td>
                                <td><input type="text" class="form-control total-price text-end fw-bold" readonly></td>
                                <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-info mt-2" id="urunEkleBtn"><i class="fas fa-plus"></i> Ürün Ekle</button>
                
                <hr class="my-4">
                <button type="submit" class="btn btn-primary btn-lg">Kaydet</button>
                <a href="sablon_listesi.php" class="btn btn-secondary btn-lg">Vazgeç</a>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
<script>
$(document).ready(function() {
    function applyStylesAndInit(row) {
        row.find('.product-image').css({'width': '40px', 'height': '40px', 'object-fit': 'cover', 'border-radius': '4px'});
        
        row.find('.product-select').select2({
            placeholder: 'Ürün arayın...',
            dropdownParent: row.find('.product-select').parent(),
            ajax: { url: 'api_get_products.php', dataType: 'json', delay: 250, processResults: data => ({ results: data.results }), cache: true }
        }).on('select2:select', function(e) {
            var selectedProduct = e.params.data;
            $(this).data('prices', selectedProduct.prices); 
            var imagePath = selectedProduct && selectedProduct.fotograf_yolu ? selectedProduct.fotograf_yolu : 'assets/images/placeholder.png';
            $(this).closest('tr').find('.product-image').attr('src', imagePath);
            updatePriceInfo($(this).closest('tr'));
        });
    }
    
    function updatePriceInfo(row) {
        var quantity = parseFloat(row.find('.quantity').val()) || 0;
        var prices = row.find('.product-select').data('prices');
        
        if (prices) {
            var price = prices['TL'] || prices['USD'] || prices['EUR'] || 0;
            row.find('.unit-price').val(parseFloat(price).toFixed(2));
            row.find('.total-price').val((price * quantity).toFixed(2));
        } else {
            row.find('.unit-price').val('');
            row.find('.total-price').val('');
        }
    }

    function addProductRow() {
        var rowCount = new Date().getTime();
        var newRowHTML = `
            <tr>
                <td><img src="assets/images/placeholder.png" class="product-image"></td>
                <td><select class="form-control product-select" name="products[${rowCount}][id]"></select></td>
                <td><input type="number" class="form-control quantity" name="products[${rowCount}][quantity]" value="1" min="1" step="1"></td>
                <td><input type="text" class="form-control unit-price text-end" readonly></td>
                <td><input type="text" class="form-control total-price text-end fw-bold" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
        var newRow = $(newRowHTML);
        $('#sablonKalemleri tbody').append(newRow);
        applyStylesAndInit(newRow);
    }

    $('#sablonKalemleri tbody tr').each(function() {
        var row = $(this);
        applyStylesAndInit(row);
        updatePriceInfo(row); // Sayfa yüklendiğinde mevcut fiyatları da göster
    });

    $('#sablonKalemleri').on('change keyup', '.quantity', function() {
        updatePriceInfo($(this).closest('tr'));
    });

    $('#urunEkleBtn').on('click', addProductRow);
    $('#sablonKalemleri').on('click', '.remove-row', function() { $(this).closest('tr').remove(); });

    if ($('#sablonKalemleri tbody tr').length === 0) {
        addProductRow();
    }
});
</script>