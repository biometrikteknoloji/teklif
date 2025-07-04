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

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Form verilerini al
    $urun_adi = trim($_POST['urun_adi']);
    $urun_aciklamasi = trim($_POST['urun_aciklamasi']);
    $price_tl = $_POST['price_tl'] ?: null; // Boşsa null yap
    $price_usd = $_POST['price_usd'] ?: null;
    $price_eur = $_POST['price_eur'] ?: null;

    $fotograf_yolu = null;

    // 1. Fotoğraf yükleme işlemi
    if (isset($_FILES['fotograf']) && $_FILES['fotograf']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/product_photos/';
        $gecici_isim = $_FILES['fotograf']['tmp_name'];
        // Güvenli ve benzersiz bir dosya adı oluştur
        $yeni_dosya_adi = uniqid('', true) . '_' . basename($_FILES['fotograf']['name']);
        $fotograf_yolu = $upload_dir . $yeni_dosya_adi;

        // Dosyayı belirtilen klasöre taşı
        if (!move_uploaded_file($gecici_isim, $fotograf_yolu)) {
            die('Dosya yüklenirken bir hata oluştu.');
        }
    }

    // Veritabanı işlemlerini bir transaction içinde yapalım.
    // Bu sayede bir işlem başarısız olursa, hepsi geri alınır.
    $pdo->beginTransaction();

    try {
        // 2. Ana ürün bilgisini `products` tablosuna ekle
        $sql_product = "INSERT INTO products (urun_adi, urun_aciklamasi, fotograf_yolu) VALUES (?, ?, ?)";
        $stmt_product = $pdo->prepare($sql_product);
        $stmt_product->execute([$urun_adi, $urun_aciklamasi, $fotograf_yolu]);

        // Az önce eklenen ürünün ID'sini al
        $last_product_id = $pdo->lastInsertId();

        // 3. Fiyatları `product_prices` tablosuna ekle
        $sql_price = "INSERT INTO product_prices (product_id, currency, price) VALUES (?, ?, ?)";
        $stmt_price = $pdo->prepare($sql_price);

        // Sadece dolu olan fiyatları ekle
        if ($price_tl !== null) {
            $stmt_price->execute([$last_product_id, 'TL', $price_tl]);
        }
        if ($price_usd !== null) {
            $stmt_price->execute([$last_product_id, 'USD', $price_usd]);
        }
        if ($price_eur !== null) {
            $stmt_price->execute([$last_product_id, 'EUR', $price_eur]);
        }

        // Tüm işlemler başarılıysa, transaction'ı onayla
        $pdo->commit();

        // Ürün listesine yönlendir
        header("Location: urun_listesi.php");
        exit();

    } catch (Exception $e) {
        // Bir hata olursa, tüm işlemleri geri al
        $pdo->rollBack();
        // Hatayı ekrana bas (geliştirme aşamasında)
        die("Veritabanı hatası: " . $e->getMessage());
    }
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
            <h1>Yeni Ürün Ekle</h1>
            <p class="lead">Yeni ürünün bilgilerini ve fiyatlarını girin.</p>

            <form action="urun_ekle.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="urun_adi" class="form-label">Ürün Adı (*)</label>
                    <input type="text" class="form-control" id="urun_adi" name="urun_adi" required>
                </div>

                <div class="mb-3">
                    <label for="urun_aciklamasi" class="form-label">Ürün Açıklaması</label>
                    <textarea class="form-control" id="urun_aciklamasi" name="urun_aciklamasi" rows="4"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="fotograf" class="form-label">Ürün Fotoğrafı</label>
                    <input class="form-control" type="file" id="fotograf" name="fotograf" accept="image/jpeg, image/png, image/gif">
                </div>

                <hr>
                <h5>Fiyat Bilgileri</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="price_tl" class="form-label">Fiyat (TL)</label>
                        <div class="input-group">
                            <span class="input-group-text">₺</span>
                            <input type="number" class="form-control" id="price_tl" name="price_tl" step="0.01" placeholder="Örn: 1500.50">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="price_usd" class="form-label">Fiyat (USD)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="price_usd" name="price_usd" step="0.01" placeholder="Örn: 85.00">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="price_eur" class="form-label">Fiyat (EUR)</label>
                         <div class="input-group">
                            <span class="input-group-text">€</span>
                            <input type="number" class="form-control" id="price_eur" name="price_eur" step="0.01" placeholder="Örn: 79.90">
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <button type="submit" class="btn btn-primary">Ürünü Kaydet</button>
                <a href="urun_listesi.php" class="btn btn-secondary">Vazgeç</a>
            </form>
        </div>
    </div>
</div>

<?php
include 'partials/footer.php'; 
?>