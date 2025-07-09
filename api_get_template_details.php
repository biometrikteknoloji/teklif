<?php
header('Content-Type: application/json');
require 'config/database.php';

$template_id = $_GET['id'] ?? 0;
if (!$template_id) {
    echo json_encode(['success' => false, 'message' => 'Şablon ID eksik.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            ti.product_id as id,
            ti.quantity,
            p.urun_adi as text,
            p.fotograf_yolu,
            (SELECT GROUP_CONCAT(CONCAT(currency, ':', price) SEPARATOR ';') FROM product_prices WHERE product_id = p.id) as prices_str
        FROM proposal_template_items ti
        JOIN products p ON ti.product_id = p.id
        WHERE ti.template_id = ?
        ORDER BY ti.id ASC
    ");
    $stmt->execute([$template_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $key => $item) {
        if(!empty($item['prices_str'])) {
            $price_pairs = explode(';', $item['prices_str']);
            $prices = [];
            foreach ($price_pairs as $pair) {
                list($currency, $price) = explode(':', $pair);
                $prices[$currency] = $price;
            }
            $items[$key]['prices'] = $prices;
        } else {
             $items[$key]['prices'] = [];
        }
        unset($items[$key]['prices_str']);
    }

    echo json_encode(['success' => true, 'items' => $items]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Veri çekme hatası: ' . $e->getMessage()]);
}
?>