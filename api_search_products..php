<?php
// api_search_products.php (Referans modele göre düzenlendi)
require 'config/database.php';
header('Content-Type: application/json');

$response = ['items' => []];

try {
    $searchTerm = trim($_GET['q'] ?? '');
    $currency = trim($_GET['currency'] ?? 'EUR');

    // Sadece temel bilgileri getiriyoruz
    $sql = "
        SELECT 
            p.id, 
            p.urun_adi as text,
            p.urun_aciklamasi,
            pp.price
        FROM 
            products p
        LEFT JOIN 
            product_prices pp ON p.id = pp.product_id AND pp.currency = :currency
        WHERE 
            p.urun_adi LIKE :searchTerm
        ORDER BY 
            p.urun_adi ASC
        LIMIT 20
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
    $stmt->bindValue(':currency', $currency, PDO::PARAM_STR);
    $stmt->execute();
    $response['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500); 
    $response['error'] = 'API servisinde bir sorun oluştu.';
}

echo json_encode($response);