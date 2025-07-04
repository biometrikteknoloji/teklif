<?php
// Lütfen giriş için kullanmak istediğiniz şifreyi buraya yazın.
$yeni_sifre = '123456';

// Şifreyi PHP'nin güvenli fonksiyonu ile hash'leyelim.
$hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şifre Hash Yenileme</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .container { border: 1px solid #ccc; padding: 20px; max-width: 600px; }
        textarea { width: 100%; padding: 10px; font-family: monospace; }
        h2 { color: #c0392b; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Şifre Yenileme Aracı</h1>
        <p>
            <strong>Giriş için kullanılacak Şifre:</strong> <?php echo $yeni_sifre; ?>
        </p>
        
        <h2>Veritabanına Kaydedilecek Hash (Bunu Kopyalayın):</h2>
        <textarea rows="3" readonly onclick="this.select();"><?php echo $hash; ?></textarea>
        <p>Yukarıdaki kutucuğa tıklayıp tüm metni (Ctrl+C veya Cmd+C ile) kopyalayın.</p>
    </div>
</body>
</html>