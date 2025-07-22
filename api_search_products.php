<?php
// api_search_products.php (SIFIR TOLERANS VERSİYONU)

// Hata ayıklama için en başa ekleyelim
ini_set('display_errors', 1);
error_reporting(E_ALL);

// === Çıktı tamponlamayı başlat. Bu, istenmeyen boşlukları engeller. ===
ob_start();

// Veritabanı bağlantısı
require __DIR__ . '/config/database.php';

$term = $_GET['q'] ?? '';
$response = ['items' => []];

if (!isset($pdo)) {
    $response['error'] = 'Veritabani baglantisi ($pdo) bulunamadi.';
} else {
    try {
        $sql = "
            SELECT p.id, p.urun_adi as text, p.urun_aciklamasi, p.fotograf_yolu, pp.currency, pp.price 
            FROM products p
            LEFT JOIN product_prices pp ON p.id = pp.product_id
            WHERE p.urun_adi LIKE :searchTerm
            ORDER BY p.urun_adi";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':searchTerm' => '%' . $term . '%']); 
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped_products = [];
        foreach ($products as $product) {
            if (!isset($grouped_products[$product['id']])) {
                $grouped_products[$product['id']] = [
                    'id' => $product['id'],
                    'text' => $product['text'],
                    'urun_aciklamasi' => $product['urun_aciklamasi'],
                    'fotograf_yolu' => $product['fotograf_yolu'],
                    'prices' => []
                ];
            }
            if ($product['currency'] && $product['price'] !== null) {
                $grouped_products[$product['id']]['prices'][$product['currency']] = $product['price'];
            }
        }
        
        $response['items'] = array_values($grouped_products);

    } catch (Exception $e) {
        http_response_code(500);
        $response['error'] = 'API servisinde bir sorun oluştu: ' . $e->getMessage();
    }
}

// === Tamponu temizle, sadece JSON kalsın. ===
ob_end_clean();

// Yanıtın JSON olduğunu belirt ve sonucu bas.
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
// Bu dosyada ?> kapanış etiketi KULLANMAYIN.