<?php
// api_get_cities.php

// Veritabanı bağlantısı
require 'config/database.php';

// Tarayıcıya cevabın JSON formatında olduğunu söylüyoruz
header('Content-Type: application/json');

// Başarısız durum için varsayılan cevap
$response = ['success' => false, 'cities' => []];

// Gelen ülke ID'sini alıyoruz
$countryId = $_GET['country_id'] ?? 0;

if ($countryId) {
    try {
        $sql = "SELECT id, city_name FROM cities WHERE country_id = ? ORDER BY city_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$countryId]);
        $cities = $stmt->fetchAll();
        
        $response['success'] = true;
        $response['cities'] = $cities;

    } catch (PDOException $e) {
        // Hata durumunda loglama yapılabilir
        $response['error'] = $e->getMessage();
    }
}

// Sonucu JSON olarak ekrana basıyoruz
echo json_encode($response);