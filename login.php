<?php
session_start();

// Önce "Beni Hatırla" çerezini kontrol et
if (isset($_COOKIE['remember_user_token']) && !isset($_SESSION['user_id'])) {
    require 'config/database.php';
    $token = $_COOKIE['remember_user_token'];
    
    // Veritabanında bu token'a sahip kullanıcıyı bul
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND is_active = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Kullanıcı bulundu, session'ı başlat
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role_id'] = $user['role_id'];
    } else {
        // Geçersiz token, çerezi sil
        setcookie('remember_user_token', '', time() - 3600, "/");
    }
}

// Session kontrolünü çerez kontrolünden sonra yap
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'config/database.php';
    $email = $_POST['email'];
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role_id'] = $user['role_id'];

        if ($remember_me) {
            // Güvenli bir "Beni Hatırla" token'ı oluştur
            $token = bin2hex(random_bytes(32));
            
            // Token'ı veritabanına kaydet
            $stmt_token = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt_token->execute([$token, $user['id']]);
            
            // Token'ı 30 gün geçerli bir çerez olarak ayarla
            setcookie('remember_user_token', $token, time() + (86400 * 30), "/"); // 86400 = 1 gün
        }

        header('Location: dashboard.php');
        exit();
    } else {
        $error_message = 'E-posta veya şifre hatalı.';
    }
}

// === header.php'yi ve custom.css'i bu sayfa için özel olarak düzenleyeceğiz ===
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Teklif Yönetim Sistemi</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="text-center mb-4">
            <h3>Teklif Yönetim Sistemi</h3>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">E-posta Adresiniz</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Şifreniz</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <!-- === "BENİ HATIRLA" CHECKBOX'I === -->
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                <label class="form-check-label" for="remember_me">Beni Hatırla</label>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Giriş Yap</button>
            </div>
        </form>
    </div>
</div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>