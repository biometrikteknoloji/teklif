<?php
header('Content-Type: application/json');
require 'config/database.php';

$country_id = $_GET['country_id'] ?? 0;

if (!$country_id) {
    echo json_encode(['success' => false, 'message' => 'Ülke ID eksik.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, city_name FROM cities WHERE country_id = ? ORDER BY city_name ASC");
    $stmt->execute([$country_id]);
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'cities' => $cities]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Veri çekme hatası: ' . $e->getMessage()]);
}
?>