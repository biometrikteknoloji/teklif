<?php
header('Content-Type: application/json');
require 'config/database.php';

$searchTerm = $_GET['term'] ?? '';

try {
    // Ürünleri ve fiyatlarını çek
    $sql = "
        SELECT 
            p.id, 
            p.urun_adi as text,
            p.urun_aciklamasi as description,
            (SELECT GROUP_CONCAT(CONCAT(currency, ':', price) SEPARATOR ';') FROM product_prices WHERE product_id = p.id) as prices_str
        FROM products p
        WHERE p.urun_adi LIKE ?
        ORDER BY p.urun_adi ASC
        LIMIT 20
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$searchTerm%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fiyatları JS'in anlayacağı formata çevir
    foreach ($products as $key => $product) {
        if(!empty($product['prices_str'])) {
            $price_pairs = explode(';', $product['prices_str']);
            $prices = [];
            foreach ($price_pairs as $pair) {
                list($currency, $price) = explode(':', $pair);
                $prices[$currency] = $price;
            }
            $products[$key]['prices'] = $prices;
        } else {
             $products[$key]['prices'] = [];
        }
        unset($products[$key]['prices_str']);
    }
    
    echo json_encode(['results' => $products]);

} catch (Exception $e) {
    echo json_encode(['results' => [], 'error' => $e->getMessage()]);
}
?>