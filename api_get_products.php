<?php
// Bu dosyanın çıktısının JSON olduğunu tarayıcıya bildiriyoruz.
header('Content-Type: application/json; charset=utf-8');

// Veritabanı bağlantımızı dahil ediyoruz.
require 'config/database.php';

// Select2'nin arama kutusuna yazılan kelimeyi alıyoruz.
// Eğer bir şey yazılmadıysa boş bir string ('') olarak alıyoruz.
$term = $_GET['term'] ?? '';

// SQL Sorgusu: Ürünleri ve onlara ait fiyatları çekiyoruz.
// LIKE :searchTerm ile kullanıcının girdiği kelimeye göre arama yapıyoruz.
$sql = "
    SELECT 
        p.id, 
        p.urun_adi as text, 
        p.fotograf_yolu, 
        pp.currency, 
        pp.price 
    FROM products p
    LEFT JOIN product_prices pp ON p.id = pp.product_id
    WHERE p.urun_adi LIKE :searchTerm  -- Named placeholder for security
    ORDER BY p.urun_adi
";

$stmt = $pdo->prepare($sql);
// Sorguyu çalıştırırken arama teriminin başına ve sonuna % ekliyoruz.
$stmt->execute([':searchTerm' => '%' . $term . '%']); 
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Veriyi Select2'nin anlayacağı formata dönüştürme işlemi
$grouped_products = [];
foreach ($products as $product) {
    // Eğer bu ürün ID'si daha önce işlenmediyse, temel bilgileri ata.
    if (!isset($grouped_products[$product['id']])) {
        $grouped_products[$product['id']] = [
            'id' => $product['id'],
            'text' => $product['text'],
            'fotograf_yolu' => $product['fotograf_yolu'],
            'prices' => [] // Fiyatlar için boş bir dizi oluştur.
        ];
    }
    // Her bir para birimi için fiyatı 'prices' dizisine ekle.
    if ($product['currency'] && $product['price'] !== null) {
        $grouped_products[$product['id']]['prices'][$product['currency']] = $product['price'];
    }
}

// Sonuçları Select2'nin beklediği 'results' anahtarı altında bir diziye koyuyoruz.
// array_values, anahtarları (1, 2, 3 gibi) atıp sadece değerleri içeren sıralı bir dizi oluşturur.
$final_results = [
    'results' => array_values($grouped_products)
];

// Sonucu JSON formatında ekrana basıyoruz.
echo json_encode($final_results);