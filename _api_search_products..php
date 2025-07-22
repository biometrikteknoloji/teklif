<?php
// api_search_products.php (DÜZELTİLMİŞ DOSYA YOLU İLE)
header('Content-Type: application/json; charset=utf-8');

/**
 * DÜZELTME: Dosya yolunu daha sağlam hale getiriyoruz.
 * __DIR__ komutu, bu dosyanın (api_search_products.php) bulunduğu klasörün tam yolunu verir.
 * Bu sayede, require komutu her zaman doğru yere bakar.
 */
require __DIR__ . '/config/database.php';

$term = $_GET['q'] ?? '';
$response = ['items' => []];

// Veritabanı bağlantısının var olup olmadığını kontrol edelim.
if (!isset($pdo)) {
    http_response_code(500);
    $response['error'] = 'Veritabani baglantisi ($pdo) bulunamadi. config/database.php dosyasini kontrol edin.';
    echo json_encode($response);
    exit();
}

try {
    // ... SQL sorgusu ve geri kalan kod aynı ...
    $sql = "SELECT p.id, p.urun_adi as text, p.urun_aciklamasi, pp.currency, pp.price FROM products p LEFT JOIN product_prices pp ON p.id = pp.product_id WHERE p.urun_adi LIKE :searchTerm ORDER BY p.urun_adi";
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

echo json_encode($response);