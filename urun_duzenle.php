<?php
session_start();
// Yetki kontrolü
if ($_SESSION['user_role_id'] != 1) { // 1 = Admin rolü ID'si
    // Admin değilse, panele yönlendir ve işlemi durdur.
    header('Location: dashboard.php');
    exit('Bu sayfaya erişim yetkiniz yok.');
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'config/database.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header('Location: urun_listesi.php');
    exit();
}

// Form gönderildi mi kontrolü
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Form verilerini al
    $urun_adi = trim($_POST['urun_adi']);
    $urun_aciklamasi = trim($_POST['urun_aciklamasi']);
    $price_tl = $_POST['price_tl'] ?: null;
    $price_usd = $_POST['price_usd'] ?: null;
    $price_eur = $_POST['price_eur'] ?: null;
    $mevcut_fotograf = $_POST['mevcut_fotograf'];

    $fotograf_yolu = $mevcut_fotograf;

    // 1. Yeni fotoğraf yüklendi mi?
    if (isset($_FILES['fotograf']) && $_FILES['fotograf']['error'] == UPLOAD_ERR_OK) {
        // Eski fotoğrafı sil (eğer varsa)
        if ($mevcut_fotograf && file_exists($mevcut_fotograf)) {
            unlink($mevcut_fotograf);
        }
        
        $upload_dir = 'uploads/product_photos/';
        $gecici_isim = $_FILES['fotograf']['tmp_name'];
        $yeni_dosya_adi = uniqid('', true) . '_' . basename($_FILES['fotograf']['name']);
        $fotograf_yolu = $upload_dir . $yeni_dosya_adi;

        if (!move_uploaded_file($gecici_isim, $fotograf_yolu)) {
            die('Yeni dosya yüklenirken bir hata oluştu.');
        }
    }

    $pdo->beginTransaction();
    try {
        // 2. Ana ürün bilgisini `products` tablosunda güncelle
        $sql_product = "UPDATE products SET urun_adi = ?, urun_aciklamasi = ?, fotograf_yolu = ? WHERE id = ?";
        $stmt_product = $pdo->prepare($sql_product);
        $stmt_product->execute([$urun_adi, $urun_aciklamasi, $fotograf_yolu, $id]);

        // 3. Bu ürüne ait eski fiyatları `product_prices` tablosundan sil
        $stmt_delete = $pdo->prepare("DELETE FROM product_prices WHERE product_id = ?");
        $stmt_delete->execute([$id]);

        // 4. Yeni fiyatları `product_prices` tablosuna ekle
        $sql_price = "INSERT INTO product_prices (product_id, currency, price) VALUES (?, ?, ?)";
        $stmt_price = $pdo->prepare($sql_price);
        
        if ($price_tl !== null) $stmt_price->execute([$id, 'TL', $price_tl]);
        if ($price_usd !== null) $stmt_price->execute([$id, 'USD', $price_usd]);
        if ($price_eur !== null) $stmt_price->execute([$id, 'EUR', $price_eur]);

        $pdo->commit();
        header("Location: urun_listesi.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Veritabanı hatası: " . $e->getMessage());
    }
}


// Sayfa ilk yüklendiğinde formu doldurmak için verileri çek
$sql = "
    SELECT 
        p.id, p.urun_adi, p.urun_aciklamasi, p.fotograf_yolu,
        MAX(CASE WHEN pp.currency = 'TL' THEN pp.price ELSE NULL END) as price_tl,
        MAX(CASE WHEN pp.currency = 'USD' THEN pp.price ELSE NULL END) as price_usd,
        MAX(CASE WHEN pp.currency = 'EUR' THEN pp.price ELSE NULL END) as price_eur
    FROM products p
    LEFT JOIN product_prices pp ON p.id = pp.product_id
    WHERE p.id = ?
    GROUP BY p.id, p.urun_adi, p.urun_aciklamasi, p.fotograf_yolu
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$urun = $stmt->fetch();

if (!$urun) {
    header('Location: urun_listesi.php');
    exit();
}

include 'partials/header.php';
?>

<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="user-info">
                Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>!
            </div>
        </div>

        <div class="page-content">
            <h1>Ürün Düzenle</h1>
            <p class="lead">"<?php echo htmlspecialchars($urun['urun_adi']); ?>" adlı ürünü güncelleyin.</p>

            <form action="urun_duzenle.php?id=<?php echo $urun['id']; ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="mevcut_fotograf" value="<?php echo htmlspecialchars($urun['fotograf_yolu']); ?>">
                
                <div class="mb-3">
                    <label for="urun_adi" class="form-label">Ürün Adı (*)</label>
                    <input type="text" class="form-control" id="urun_adi" name="urun_adi" value="<?php echo htmlspecialchars($urun['urun_adi']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="urun_aciklamasi" class="form-label">Ürün Açıklaması</label>
                    <textarea class="form-control" id="urun_aciklamasi" name="urun_aciklamasi" rows="4"><?php echo htmlspecialchars($urun['urun_aciklamasi']); ?></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="fotograf" class="form-label">Ürün Fotoğrafı (Değiştirmek için yenisini seçin)</label>
                    <div class="d-flex align-items-center">
                        <?php if ($urun['fotograf_yolu'] && file_exists($urun['fotograf_yolu'])): ?>
                            <img src="<?php echo htmlspecialchars($urun['fotograf_yolu']); ?>" alt="Mevcut Fotoğraf" class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover;">
                        <?php endif; ?>
                        <input class="form-control" type="file" id="fotograf" name="fotograf" accept="image/jpeg, image/png, image/gif">
                    </div>
                </div>

                <hr>
                <h5>Fiyat Bilgileri</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="price_tl" class="form-label">Fiyat (TL)</label>
                        <div class="input-group">
                            <span class="input-group-text">₺</span>
                            <input type="number" class="form-control" id="price_tl" name="price_tl" step="0.01" value="<?php echo htmlspecialchars($urun['price_tl']); ?>" placeholder="Örn: 1500.50">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="price_usd" class="form-label">Fiyat (USD)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="price_usd" name="price_usd" step="0.01" value="<?php echo htmlspecialchars($urun['price_usd']); ?>" placeholder="Örn: 85.00">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="price_eur" class="form-label">Fiyat (EUR)</label>
                         <div class="input-group">
                            <span class="input-group-text">€</span>
                            <input type="number" class="form-control" id="price_eur" name="price_eur" step="0.01" value="<?php echo htmlspecialchars($urun['price_eur']); ?>" placeholder="Örn: 79.90">
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                <a href="urun_listesi.php" class="btn btn-secondary">Vazgeç</a>
            </form>
        </div>
    </div>
</div>

<?php
include 'partials/footer.php'; 
?>