<?php
require 'core/functions.php';
session_start();
// Yetki kontrolü
if ($_SESSION['user_role_id'] != 1) { // 1 = Admin rolü ID'si
    // Admin değilse, panele yönlendir ve işlemi durdur.
    header('Location: dashboard.php');
    exit('Bu sayfaya erişim yetkiniz yok.');
}

// Güvenlik: Giriş yapılmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'config/database.php';

// Form gönderildi mi diye kontrol et
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formdan gelen verileri al
    $unvan = trim($_POST['unvan']);
    $adres = trim($_POST['adres']);
    $telefon = trim($_POST['telefon']);
    $vergi_dairesi = trim($_POST['vergi_dairesi']);
    // Düzeltilmiş Kod
$vergi_no = trim($_POST['vergi_no']);
if (empty($vergi_no)) {
    $vergi_no = null; // Eğer alan boşsa, PHP'de null olarak ayarla
}
    $email = trim($_POST['email']);
    $cep_telefonu = trim($_POST['cep_telefonu']);
    $yetkili_ismi = trim($_POST['yetkili_ismi']);

    // Veritabanına ekleme sorgusu
    // SQL Injection'a karşı korumalı, güvenli yöntem (Prepared Statements)
    $sql = "INSERT INTO customers (unvan, adres, telefon, vergi_dairesi, vergi_no, email, cep_telefonu, yetkili_ismi) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    
    // Sorguyu çalıştır
    $stmt->execute([$unvan, $adres, $telefon, $vergi_dairesi, $vergi_no, $email, $cep_telefonu, $yetkili_ismi]);
	add_log($pdo, 'YENİ MÜŞTERİ EKLENDİ', 'Müşteri Ünvanı: ' . $unvan);

    // İşlem bittikten sonra müşteri listesine geri yönlendir
    header("Location: musteri_listesi.php");
    exit();
}

// Header'ı dahil et
include 'partials/header.php';
?>

<div class="main-wrapper">
    
    <?php include 'partials/sidebar.php'; // Sol menüyü dahil et ?>

    <!-- Sayfanın ana içerik bölümü -->
    <div class="main-content">
        
        <!-- Üst Bar -->
        <div class="topbar">
            <div class="user-info">
                Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>!
            </div>
        </div>

        <!-- Asıl sayfa içeriği -->
        <div class="page-content">
            
            <h1>Yeni Müşteri Ekle</h1>
            <p class="lead">Lütfen yeni müşteri bilgilerini eksiksiz girin.</p>

            <form action="musteri_ekle.php" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="unvan" class="form-label">Firma Ünvanı (*)</label>
                        <input type="text" class="form-control" id="unvan" name="unvan" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="yetkili_ismi" class="form-label">Yetkili İsmi</label>
                        <input type="text" class="form-control" id="yetkili_ismi" name="yetkili_ismi">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="adres" class="form-label">Adres</label>
                    <textarea class="form-control" id="adres" name="adres" rows="3"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">E-posta Adresi</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="telefon" class="form-label">Sabit Telefon</label>
                        <input type="text" class="form-control" id="telefon" name="telefon">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="cep_telefonu" class="form-label">Cep Telefonu</label>
                        <input type="text" class="form-control" id="cep_telefonu" name="cep_telefonu">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="vergi_dairesi" class="form-label">Vergi Dairesi</label>
                        <input type="text" class="form-control" id="vergi_dairesi" name="vergi_dairesi">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="vergi_no" class="form-label">Vergi Numarası</label>
                        <input type="text" class="form-control" id="vergi_no" name="vergi_no">
                    </div>
                </div>
                
                <hr class="my-4">

                <button type="submit" class="btn btn-primary">Kaydet</button>
                <a href="musteri_listesi.php" class="btn btn-secondary">Vazgeç</a>
            </form>

        </div> <!-- .page-content sonu -->
    </div> <!-- .main-content sonu -->
</div> <!-- .main-wrapper sonu -->

<?php
// Footer'ı dahil et
include 'partials/footer.php'; 
?>