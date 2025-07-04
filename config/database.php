<?php

// Veritabanı bağlantı bilgileri
$host = 'localhost';
$dbname = 'teklif_yonetim_sistemi'; // Doğru isim bu olmalı.
$user = 'root'; // XAMPP için varsayılan kullanıcı adı
$pass = '';     // XAMPP için varsayılan şifre boştur

// Veri Kaynağı Adı (DSN)
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

// PDO bağlantı seçenekleri
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Hataları yakalamak için
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Sonuçları dizi olarak almak için
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Veritabanına bağlanmayı dene
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Bağlantı başarısız olursa, hatayı göster ve programı durdur
    // Gerçek bir sunucuda bu kadar detaylı hata gösterilmez, loglanır.
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Bu dosya başka bir dosyaya dahil edildiğinde, $pdo değişkeni kullanılabilir olacak.