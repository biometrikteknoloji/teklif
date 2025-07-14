<?php
require 'core/functions.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if ($_SESSION['user_role_id'] != 1) { header('Location: dashboard.php'); exit('Yetkisiz Erişim.'); }

require 'config/database.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header('Location: musteri_listesi.php');
    exit();
}

$error_message = '';

// Form gönderildi mi kontrolü
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formdan gelen verileri al
    $unvan = trim($_POST['unvan']);
    $adres = trim($_POST['adres']);
    $telefon = trim($_POST['telefon']);
    $vergi_dairesi = trim($_POST['vergi_dairesi']);
    $vergi_no = trim($_POST['vergi_no']) ?: null;
    $email = trim($_POST['email']);
    $cep_telefonu = trim($_POST['cep_telefonu']);
    $yetkili_ismi = trim($_POST['yetkili_ismi']);
    $country_id = $_POST['country_id'] ?: null;
    $city_id = $_POST['city_id'] ?: null;
    $customer_type = $_POST['customer_type'];

    try {
        // Veritabanını güncelleme sorgusu
        $sql = "UPDATE customers SET 
                    unvan = ?, adres = ?, telefon = ?, vergi_dairesi = ?, vergi_no = ?, 
                    email = ?, cep_telefonu = ?, yetkili_ismi = ?, country_id = ?, 
                    city_id = ?, customer_type = ? 
                WHERE id = ?";
        $params = [
            $unvan, $adres, $telefon, $vergi_dairesi, $vergi_no, $email, 
            $cep_telefonu, $yetkili_ismi, $country_id, $city_id, $customer_type, 
            $id
        ];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        add_log($pdo, 'MÜŞTERİ GÜNCELLENDİ', 'Müşteri ID: ' . $id);
        header("Location: musteri_listesi.php?status=success");
        exit();
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
             $error_message = "Bu vergi numarası veya e-posta adresi zaten başka bir müşteriye ait.";
        } else {
             $error_message = "HATA: Güncelleme işlemi başarısız. " . $e->getMessage();
        }
    }
}

// Sayfa ilk yüklendiğinde, form alanlarını doldurmak için mevcut müşteri bilgilerini çek.
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header('Location: musteri_listesi.php');
    exit();
}

// Form için ülkeleri çek
$countries = $pdo->query("SELECT * FROM countries ORDER BY country_name ASC")->fetchAll();
// Eğer müşterinin bir ülkesi varsa, o ülkenin şehirlerini de önceden yükle
$cities = [];
if (!empty($customer['country_id'])) {
    $stmt_cities = $pdo->prepare("SELECT * FROM cities WHERE country_id = ? ORDER BY city_name ASC");
    $stmt_cities->execute([$customer['country_id']]);
    $cities = $stmt_cities->fetchAll(PDO::FETCH_ASSOC);
}

include 'partials/header.php';
?>
<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="user-info">Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!</div>
        </div>
        <div class="page-content">
            <h1>Müşteri Düzenle</h1>
            <p class="lead">"<?php echo htmlspecialchars($customer['unvan']); ?>" adlı müşterinin bilgilerini güncelleyin.</p>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="musteri_duzenle.php?id=<?php echo $customer['id']; ?>" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="unvan" class="form-label">Firma Ünvanı (*)</label>
                        <input type="text" class="form-control" id="unvan" name="unvan" value="<?php echo htmlspecialchars($customer['unvan'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="yetkili_ismi" class="form-label">Yetkili İsmi</label>
                        <input type="text" class="form-control" id="yetkili_ismi" name="yetkili_ismi" value="<?php echo htmlspecialchars($customer['yetkili_ismi'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="customer_type" class="form-label">Müşteri Tipi (*)</label>
                        <select class="form-select" id="customer_type" name="customer_type" required>
                            <option value="Bayi" <?php if(($customer['customer_type'] ?? '') == 'Bayi') echo 'selected'; ?>>Bayi</option>
                            <option value="Son Kullanıcı" <?php if(($customer['customer_type'] ?? '') == 'Son Kullanıcı') echo 'selected'; ?>>Son Kullanıcı</option>
                            <option value="Proje Firması" <?php if(($customer['customer_type'] ?? '') == 'Proje Firması') echo 'selected'; ?>>Proje Firması</option>
                            <option value="Diğer" <?php if(($customer['customer_type'] ?? '') == 'Diğer') echo 'selected'; ?>>Diğer</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="country_id" class="form-label">Ülke</label>
                        <select class="form-select" id="country_id" name="country_id">
                            <option value="">Seçiniz...</option>
                            <?php foreach($countries as $country): ?>
                                <option value="<?php echo $country['id']; ?>" <?php if(($customer['country_id'] ?? '') == $country['id']) echo 'selected'; ?>><?php echo htmlspecialchars($country['country_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="city_id" class="form-label">Şehir</label>
                        <select class="form-select" id="city_id" name="city_id" <?php if(empty($cities)) echo 'disabled'; ?>>
                            <option value="">Önce Ülke Seçin</option>
                            <?php foreach($cities as $city): ?>
                                <option value="<?php echo $city['id']; ?>" <?php if(($customer['city_id'] ?? '') == $city['id']) echo 'selected'; ?>><?php echo htmlspecialchars($city['city_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="adres" class="form-label">Adres</label>
                    <textarea class="form-control" id="adres" name="adres" rows="3"><?php echo htmlspecialchars($customer['adres'] ?? ''); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">E-posta Adresi</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="telefon" class="form-label">Sabit Telefon</label>
                        <input type="text" class="form-control" id="telefon" name="telefon" value="<?php echo htmlspecialchars($customer['telefon'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="cep_telefonu" class="form-label">Cep Telefonu</label>
                        <input type="text" class="form-control" id="cep_telefonu" name="cep_telefonu" value="<?php echo htmlspecialchars($customer['cep_telefonu'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="vergi_dairesi" class="form-label">Vergi Dairesi</label>
                        <input type="text" class="form-control" id="vergi_dairesi" name="vergi_dairesi" value="<?php echo htmlspecialchars($customer['vergi_dairesi'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="vergi_no" class="form-label">Vergi Numarası</label>
                        <input type="text" class="form-control" id="vergi_no" name="vergi_no" value="<?php echo htmlspecialchars($customer['vergi_no'] ?? ''); ?>">
                    </div>
                </div>
                
                <hr class="my-4">
                <button type="submit" class="btn btn-primary">Güncelle</button>
                <a href="musteri_listesi.php" class="btn btn-secondary">Vazgeç</a>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
<script>
$(document).ready(function() {
    // Dinamik şehir seçimi
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