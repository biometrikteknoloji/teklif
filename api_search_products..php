<?php
// Hata raporlamayı en üste alalım ki her şeyi görelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tarayıcıya JSON göndereceğimizi en başta söyleyelim
header('Content-Type: application/json');

// Cevap iskeletimizi hazırlayalım
$response = [
    'status' => 'error', // Varsayılan durum
    'items' => [],
    'message' => ''
];

// ADIM 1: Veritabanı bağlantı dosyasını çağıralım
try {
    require 'config/database.php';
    // Eğer buraya kadar geldiyse, config dosyası bulundu demektir.
    $response['message'] = 'config/database.php basariyla cagirildi.';
} catch (Exception $e) {
    $response['message'] = 'HATA: config/database.php cagrilamadi. ' . $e->getMessage();
    echo json_encode($response);
    exit(); // Script'i burada sonlandır
}

// ADIM 2: Veritabanı bağlantısının ($pdo) var olup olmadığını kontrol edelim
if (!isset($pdo)) {
    $response['message'] = 'HATA: Veritabani baglantisi ($pdo) bulunamadi. config/database.php dosyasini kontrol edin.';
    echo json_encode($response);
    exit();
}

// ADIM 3: Asıl SQL sorgusunu çalıştıralım
try {
    $searchTerm = trim($_GET['q'] ?? '');
    $currency = trim($_GET['currency'] ?? 'EUR');

    $sql = "SELECT id, urun_adi as text FROM products WHERE urun_adi LIKE :searchTerm LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['status'] = 'success';
    $response['items'] = $results;
    $response['message'] = count($results) . ' adet sonuc bulundu.';

} catch (Exception $e) {
    $response['message'] = 'SQL Sorgu Hatasi: ' . $e->getMessage();
}

// ADIM 4: Sonucu ekrana basalım
echo json_encode($response);
```*(Not: Bu kodda, fiyat tablosu (`product_prices`) ile birleştirmeyi kasıtlı olarak kaldırdım. Amacımız, sadece en temel ürün aramanın çalışıp çalışmadığını test etmek.)*

<?php
// Hata raporlamayı en üste alalım ki her şeyi görelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tarayıcıya JSON göndereceğimizi en başta söyleyelim
header('Content-Type: application/json');

// Cevap iskeletimizi hazırlayalım
$response = [
    'status' => 'error', // Varsayılan durum
    'items' => [],
    'message' => ''
];

// ADIM 1: Veritabanı bağlantı dosyasını çağıralım
try {
    require 'config/database.php';
    // Eğer buraya kadar geldiyse, config dosyası bulundu demektir.
    $response['message'] = 'config/database.php basariyla cagirildi.';
} catch (Exception $e) {
    $response['message'] = 'HATA: config/database.php cagrilamadi. ' . $e->getMessage();
    echo json_encode($response);
    exit(); // Script'i burada sonlandır
}

// ADIM 2: Veritabanı bağlantısının ($pdo) var olup olmadığını kontrol edelim
if (!isset($pdo)) {
    $response['message'] = 'HATA: Veritabani baglantisi ($pdo) bulunamadi. config/database.php dosyasini kontrol edin.';
    echo json_encode($response);
    exit();
}

// ADIM 3: Asıl SQL sorgusunu çalıştıralım
try {
    $searchTerm = trim($_GET['q'] ?? '');
    $currency = trim($_GET['currency'] ?? 'EUR');

    $sql = "SELECT id, urun_adi as text FROM products WHERE urun_adi LIKE :searchTerm LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['status'] = 'success';
    $response['items'] = $results;
    $response['message'] = count($results) . ' adet sonuc bulundu.';

} catch (Exception $e) {
    $response['message'] = 'SQL Sorgu Hatasi: ' . $e->getMessage();
}

// ADIM 4: Sonucu ekrana basalım
echo json_encode($response);
```*(Not: Bu kodda, fiyat tablosu (`product_prices`) ile birleştirmeyi kasıtlı olarak kaldırdım. Amacımız, sadece en temel ürün aramanın çalışıp çalışmadığını test etmek.)*

### Son Test

Bu değişikliği yaptıktan sonra, lütfen tarayıcıda şu adresi tekrar açın:
`http://localhost/teklif-yonetim-sistemi/api_search_products.php?q=a`

**Lütfen çıkan sonucun ekran görüntüsünü paylaşın.**

*   Eğer `{"status":"success", ...}` ile başlayan bir sonuç alırsanız, **SORUN ÇÖZÜLMÜŞTÜR** ve artık `proforma_form`'da ürün arama çalışacaktır.
*   Eğer `{"status":"error", ...}` ile başlayan bir sonuç alırsanız, `message` kısmında yazan hata, sorunun tam olarak nerede olduğunu bize söyleyecektir.