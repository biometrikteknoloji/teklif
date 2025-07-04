<?php
// Oturumu başlat. Kullanıcının giriş yaptığını hatırlamak için bu gereklidir.
session_start();

// Eğer kullanıcı zaten giriş yapmışsa, onu dashboard'a yönlendir.
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Hata mesajı için bir değişken tanımlayalım
$error_message = '';

// Eğer form POST metodu ile gönderilmişse...
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Veritabanı bağlantı dosyasını dahil et
    require 'config/database.php';

    // Formdan gelen verileri al
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Kullanıcıyı e-posta adresine göre bul
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 2. Kullanıcı bulunduysa VE girilen şifre veritabanındaki hash ile eşleşiyorsa...
    if ($user && password_verify($password, $user['password'])) {
        
        // Giriş başarılı!
        // Kullanıcı bilgilerini session'a kaydet
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name']; // Düzeltilmiş sütun adı
        $_SESSION['user_role_id'] = $user['role_id'];

        // Kullanıcıyı ana panele (dashboard) yönlendir
        header('Location: dashboard.php');
        exit();

    } else {
        // Giriş başarısız oldu. Hata mesajını ayarla.
        $error_message = 'E-posta veya şifre hatalı.';
    }
}

// HTML kısmı başlıyor
include 'partials/header.php'; 
?>

<div class="login-container">
    <div class="login-card">
        
        <div class="logo">
            FİRMA ADI
        </div>

        <h2>Teklif Yönetim Sistemi</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="E-posta Adresiniz" required>
            </div>
            <div class="mb-4">
                <input type="password" class="form-control" name="password" placeholder="Şifreniz" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Giriş Yap</button>
            </div>
        </form>

    </div>
</div>

<?php include 'partials/footer.php'; ?>