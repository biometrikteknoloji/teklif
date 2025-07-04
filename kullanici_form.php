<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role_id'] != 1) {
    header('Location: dashboard.php');
    exit('Yetkisiz Erişim.');
}

require 'config/database.php';
require 'core/functions.php'; // add_log gibi fonksiyonlar için

// Değişkenleri ve başlangıç değerlerini tanımla
$user = [
    'id' => null,
    'full_name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'role_id' => 2, // Varsayılan olarak 'Kullanıcı' rolü
    'is_active' => 1 // Varsayılan olarak 'Aktif'
];
$page_title = 'Yeni Kullanıcı Ekle';
$error_message = '';
$success_message = '';

// Düzenleme modu mu? (URL'de id var mı?)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $page_title = 'Kullanıcıyı Düzenle';
    $user_id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Kullanıcı bulunamazsa listeye yönlendir
        header('Location: kullanici_listesi.php');
        exit();
    }
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formdan gelen verileri al
    $id = $_POST['id'];
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role_id = $_POST['role_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'];

    // Alanların dolu olup olmadığını kontrol et
    if (empty($full_name) || empty($username) || empty($email) || empty($role_id)) {
        $error_message = 'Ad Soyad, Kullanıcı Adı, E-posta ve Rol alanları zorunludur.';
    } 
    // Yeni kullanıcı oluşturuluyorsa şifre zorunlu
    elseif (empty($id) && empty($password)) {
        $error_message = 'Yeni kullanıcı için şifre alanı zorunludur.';
    }
    else {
        // E-posta ve Kullanıcı Adı'nın benzersiz olduğunu kontrol et
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
        $stmt_check->execute([$email, $username, $id ?: 0]);
        if ($stmt_check->fetch()) {
            $error_message = 'Bu e-posta adresi veya kullanıcı adı zaten başka bir kullanıcı tarafından kullanılıyor.';
        } else {
            // Hata yoksa veritabanı işlemini yap
            if (empty($id)) { // --- YENİ KULLANICI EKLEME ---
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (full_name, username, email, password, phone, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$full_name, $username, $email, $hashed_password, $phone, $role_id, $is_active]);
                add_log($pdo, 'YENİ KULLANICI EKLENDİ', 'Kullanıcı Adı: ' . $username);
                header('Location: kullanici_listesi.php?status=created');
                exit();
            } else { // --- MEVCUT KULLANICIYI GÜNCELLEME ---
                if (!empty($password)) {
                    // Eğer yeni şifre girildiyse, onu hash'le ve güncelle
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET full_name = ?, username = ?, email = ?, password = ?, phone = ?, role_id = ?, is_active = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$full_name, $username, $email, $hashed_password, $phone, $role_id, $is_active, $id]);
                } else {
                    // Eğer yeni şifre girilmediyse, şifre hariç diğer alanları güncelle
                    $sql = "UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, role_id = ?, is_active = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$full_name, $username, $email, $phone, $role_id, $is_active, $id]);
                }
                add_log($pdo, 'KULLANICI GÜNCELLENDİ', 'Kullanıcı ID: ' . $id);
                $success_message = 'Kullanıcı bilgileri başarıyla güncellendi.';
                // Güncellenen veriyi tekrar çekip formda göstermek için
                $stmt_refetch = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt_refetch->execute([$id]);
                $user = $stmt_refetch->fetch(PDO::FETCH_ASSOC);
            }
        }
    }
}

// Rolleri veritabanından çek (dropdown için)
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name ASC")->fetchAll(PDO::FETCH_ASSOC);

include 'partials/header.php';
?>
<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="user-info">Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!</div>
        </div>
        <div class="page-content">
            <h1><?php echo $page_title; ?></h1>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <form action="kullanici_form.php<?php echo $user['id'] ? '?id='.$user['id'] : ''; ?>" method="POST">
                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="full_name" class="form-label">Ad Soyad (*)</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı (*)</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">E-posta Adresi (*)</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Telefon Numarası</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                </div>

                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo $user['id'] ? '' : 'required'; ?>>
                        <?php if ($user['id']): ?>
                            <div class="form-text">Değiştirmek istemiyorsanız bu alanı boş bırakın.</div>
                        <?php endif; ?>
                    </div>
                 </div>
                
                <hr class="my-4">

                <div class="row align-items-end">
                    <div class="col-md-6 mb-3">
                        <label for="role_id" class="form-label">Kullanıcı Rolü (*)</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php echo ($user['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?php echo ($user['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Kullanıcı Aktif</label>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <button type="submit" class="btn btn-primary btn-lg">Kaydet</button>
                <a href="kullanici_listesi.php" class="btn btn-secondary btn-lg">Listeye Geri Dön</a>
            </form>
        </div>
    </div>
</div>
<?php include 'partials/footer.php'; ?>