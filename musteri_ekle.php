<?php
require 'core/functions.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if ($_SESSION['user_role_id'] != 1) { header('Location: dashboard.php'); exit('Yetkisiz Erişim.'); }
require 'config/database.php';

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $unvan = trim($_POST['unvan']);
    $adres = trim($_POST['adres']);
    $telefon = trim($_POST['telefon']);
    $vergi_dairesi = trim($_POST['vergi_dairesi']);
    $vergi_no = trim($_POST['vergi_no']) ?: null;
    $email = trim($_POST['email']);
    $cep_telefonu = trim($_POST['cep_telefonu']);
    $yetkili_ismi = trim($_POST['yetkili_ismi']);
    // === YENİ ALANLAR ===
    $country_id = $_POST['country_id'] ?: null;
    $city_id = $_POST['city_id'] ?: null;
    $customer_type = $_POST['customer_type'];
    
    try {
        $sql = "INSERT INTO customers (unvan, adres, telefon, vergi_dairesi, vergi_no, email, cep_telefonu, yetkili_ismi, country_id, city_id, customer_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$unvan, $adres, $telefon, $vergi_dairesi, $vergi_no, $email, $cep_telefonu, $yetkili_ismi, $country_id, $city_id, $customer_type]);
        add_log($pdo, 'YENİ MÜŞTERİ EKLENDİ', 'Müşteri Ünvanı: ' . $unvan);
        header("Location: musteri_listesi.php?status=success");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
             $error_message = "Bu vergi numarası veya e-posta adresi zaten kayıtlı.";
        } else {
             $error_message = "HATA: Kayıt işlemi başarısız. " . $e->getMessage();
        }
    }
}

// Form için ülkeleri çek
$countries = $pdo->query("SELECT * FROM countries ORDER BY country_name ASC")->fetchAll();

include 'partials/header.php';
?>
<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="user-info">Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!</div>
        </div>
        <div class="page-content">
            <h1>Yeni Müşteri Ekle</h1>
            <p class="lead">Lütfen yeni müşteri bilgilerini eksiksiz girin.</p>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

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

                <!-- === YENİ ALANLAR === -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="customer_type" class="form-label">Müşteri Tipi (*)</label>
                        <select class="form-select" id="customer_type" name="customer_type" required>
                            <option value="Bayi">Bayi</option>
                            <option value="Son Kullanıcı" selected>Son Kullanıcı</option>
                            <option value="Proje Firması">Proje Firması</option>
                            <option value="Diğer">Diğer</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="country_id" class="form-label">Ülke</label>
                        <select class="form-select" id="country_id" name="country_id">
                            <option value="">Seçiniz...</option>
                            <?php foreach($countries as $country): ?>
                                <option value="<?php echo $country['id']; ?>"><?php echo htmlspecialchars($country['country_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="city_id" class="form-label">Şehir</label>
                        <select class="form-select" id="city_id" name="city_id" disabled>
                            <option value="">Önce Ülke Seçin</option>
                        </select>
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
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
<!-- Dinamik şehir seçimi için JavaScript -->
<script>
$(document).ready(function() {
    $('#country_id').on('change', function() {
        var countryId = $(this).val();
        var citySelect = $('#city_id');
        citySelect.prop('disabled', true).html('<option value="">Yükleniyor...</option>');
        if (!countryId) {
            citySelect.html('<option value="">Önce Ülke Seçin</option>');
            return;
        }
        $.ajax({
            url: 'api_get_cities.php',
            type: 'GET',
            data: { country_id: countryId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.cities.length > 0) {
                    citySelect.prop('disabled', false).empty().append('<option value="">Seçiniz...</option>');
                    $.each(response.cities, function(key, city) {
                        citySelect.append($('<option>', { value: city.id, text: city.city_name }));
                    });
                } else {
                    citySelect.html('<option value="">Bu ülkeye ait şehir bulunamadı</option>');
                }
            },
            error: function() {
                citySelect.html('<option value="">Şehirler getirilemedi!</option>');
            }
        });
    });
});
</script>