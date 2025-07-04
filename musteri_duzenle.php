<?php
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

// Düzenlenecek müşterinin ID'sini al
$id = $_GET['id'] ?? null;

// Eğer ID gelmemişse veya sayısal değilse, listeye geri yönlendir
if (!$id || !is_numeric($id)) {
    header('Location: musteri_listesi.php');
    exit();
}

// Form gönderildi mi (yani "Güncelle" butonuna basıldı mı) diye kontrol et
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formdan gelen verileri al
    $unvan = trim($_POST['unvan']);
    $adres = trim($_POST['adres']);
    $telefon = trim($_POST['telefon']);
    $vergi_dairesi = trim($_POST['vergi_dairesi']);
    $vergi_no = trim($_POST['vergi_no']);
    if (empty($vergi_no)) { $vergi_no = null; } // Boşsa NULL yap
    $email = trim($_POST['email']);
    $cep_telefonu = trim($_POST['cep_telefonu']);
    $yetkili_ismi = trim($_POST['yetkili_ismi']);

    // Veritabanını güncelleme sorgusu
    $sql = "UPDATE customers SET 
                unvan = ?, 
                adres = ?, 
                telefon = ?, 
                vergi_dairesi = ?, 
                vergi_no = ?, 
                email = ?, 
                cep_telefonu = ?, 
                yetkili_ismi = ? 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$unvan, $adres, $telefon, $vergi_dairesi, $vergi_no, $email, $cep_telefonu, $yetkili_ismi, $id]);

    // İşlem bittikten sonra müşteri listesine geri yönlendir
    header("Location: musteri_listesi.php");
    exit();
}


// Eğer form GÖNDERİLMEDİYSE, sayfa ilk kez yükleniyordur.
// O zaman form alanlarını doldurmak için veritabanından mevcut müşteri bilgilerini çek.
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$musteri = $stmt->fetch();

// Eğer bu ID ile bir müşteri bulunamazsa, yine listeye yönlendir
if (!$musteri) {
    header('Location: musteri_listesi.php');
    exit();
}


// Header'ı dahil et
include 'partials/header.php';
?>

<div class="main-wrapper">
    
    <?php include 'partials/sidebar.php'; // Sol menüyü dahil et ?>

    <div class="main-content">
        <div class="topbar">
            <div class="user-info">
                Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>!
            </div>
        </div>

        <div class="page-content">
            
            <h1>Müşteri Düzenle</h1>
            <p class="lead">"<?php echo htmlspecialchars($musteri['unvan']); ?>" adlı müşterinin bilgilerini güncelleyin.</p>

            <form action="musteri_duzenle.php?id=<?php echo $musteri['id']; ?>" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="unvan" class="form-label">Firma Ünvanı (*)</label>
                        <input type="text" class="form-control" id="unvan" name="unvan" value="<?php echo htmlspecialchars($musteri['unvan']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="yetkili_ismi" class="form-label">Yetkili İsmi</label>
                        <input type="text" class="form-control" id="yetkili_ismi" name="yetkili_ismi" value="<?php echo htmlspecialchars($musteri['yetkili_ismi']); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="adres" class="form-label">Adres</label>
                    <textarea class="form-control" id="adres" name="adres" rows="3"><?php echo htmlspecialchars($musteri['adres']); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">E-posta Adresi</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($musteri['email']); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="telefon" class="form-label">Sabit Telefon</label>
                        <input type="text" class="form-control" id="telefon" name="telefon" value="<?php echo htmlspecialchars($musteri['telefon']); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="cep_telefonu" class="form-label">Cep Telefonu</label>
                        <input type="text" class="form-control" id="cep_telefonu" name="cep_telefonu" value="<?php echo htmlspecialchars($musteri['cep_telefonu']); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="vergi_dairesi" class="form-label">Vergi Dairesi</label>
                        <input type="text" class="form-control" id="vergi_dairesi" name="vergi_dairesi" value="<?php echo htmlspecialchars($musteri['vergi_dairesi']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="vergi_no" class="form-label">Vergi Numarası</label>
                        <input type="text" class="form-control" id="vergi_no" name="vergi_no" value="<?php echo htmlspecialchars($musteri['vergi_no']); ?>">
                    </div>
                </div>
                
                <hr class="my-4">

                <button type="submit" class="btn btn-primary">Güncelle</button>
                <a href="musteri_listesi.php" class="btn btn-secondary">Vazgeç</a>
            </form>

        </div>
    </div>
</div>

<?php
include 'partials/footer.php'; 
?>