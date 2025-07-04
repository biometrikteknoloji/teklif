<?php
session_start();
// Sadece Admin (rol ID 1) bu sayfaya erişebilir
if (!isset($_SESSION['user_id']) || $_SESSION['user_role_id'] != 1) {
    header('Location: dashboard.php');
    exit('Yetkisiz Erişim.');
}
require 'config/database.php';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $upload_dir = 'uploads/proposal_assets/';
    
    // Üst Bilgi (Header) için dosya yükleme
    if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] == UPLOAD_ERR_OK) {
        $old_file_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'proposal_header_path'");
        $old_file_stmt->execute();
        $old_file_path = $old_file_stmt->fetchColumn();
        if ($old_file_path && file_exists($old_file_path)) {
            unlink($old_file_path);
        }
        $file_name = 'header_' . time() . '.png';
        $file_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES['header_image']['tmp_name'], $file_path);
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'proposal_header_path'");
        $stmt->execute([$file_path]);
    }

    // Alt Bilgi (Footer) için dosya yükleme
    if (isset($_FILES['footer_image']) && $_FILES['footer_image']['error'] == UPLOAD_ERR_OK) {
        $old_file_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'proposal_footer_path'");
        $old_file_stmt->execute();
        $old_file_path = $old_file_stmt->fetchColumn();
        if ($old_file_path && file_exists($old_file_path)) {
            unlink($old_file_path);
        }
        $file_name = 'footer_' . time() . '.png';
        $file_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES['footer_image']['tmp_name'], $file_path);
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'proposal_footer_path'");
        $stmt->execute([$file_path]);
    }

    // Mail ayarlarını güncelle
    $mail_settings = [
        'mail_host' => $_POST['mail_host'],
        'mail_port' => $_POST['mail_port'],
        'mail_username' => $_POST['mail_username'],
        'mail_security' => $_POST['mail_security'],
        'mail_from_address' => $_POST['mail_from_address'],
        'mail_from_name' => $_POST['mail_from_name'],
    ];
    // Şifre alanı doluysa güncelle, değilse eskisini koru
    if (!empty($_POST['mail_password'])) {
        $mail_settings['mail_password'] = $_POST['mail_password'];
    } else {
        $mail_settings['mail_password'] = $_POST['current_mail_password'];
    }
    foreach ($mail_settings as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }

    // Mail İmzasını güncelle
    if (isset($_POST['mail_signature_html'])) {
        $stmt_sig = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'mail_signature_html'");
        $stmt_sig->execute([$_POST['mail_signature_html']]);
    }
    
    header("Location: ayarlar.php?status=success");
    exit();
}

// Mevcut ayarları veritabanından çek
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

include 'partials/header.php';
?>
<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="user-info">Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!</div>
        </div>
        <div class="page-content">
            <h1>Sistem Ayarları</h1>
            <p class="lead">Tekliflerde kullanılacak genel ayarları buradan yönetebilirsiniz.</p>
            
            <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <div class="alert alert-success">Ayarlar başarıyla kaydedildi.</div>
            <?php endif; ?>

            <form action="ayarlar.php" method="POST" enctype="multipart/form-data">
                
                <h3>Teklif Görsel Ayarları</h3>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h5>Teklif Üst Bilgisi (Antet)</h5>
                        <p class="small text-muted">Tekliflerin en üstünde yer alacak görsel. (Önerilen boyut: Genişlik 800px)</p>
                        <input class="form-control" type="file" name="header_image" accept="image/png">
                        <?php if (!empty($settings['proposal_header_path']) && file_exists($settings['proposal_header_path'])): ?>
                            <div class="mt-2 p-2 border rounded"><img src="<?php echo $settings['proposal_header_path']; ?>" class="img-fluid"></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h5>Teklif Alt Bilgisi (Altbilgi)</h5>
                        <p class="small text-muted">Tekliflerin en altında yer alacak görsel. (Banka bilgileri, kaşe, imza vb.)</p>
                        <input class="form-control" type="file" name="footer_image" accept="image/png">
                        <?php if (!empty($settings['proposal_footer_path']) && file_exists($settings['proposal_footer_path'])): ?>
                            <div class="mt-2 p-2 border rounded"><img src="<?php echo $settings['proposal_footer_path']; ?>" class="img-fluid"></div>
                        <?php endif; ?>
                    </div>
                </div>
                <hr class="my-4">

                <h3>Mail Sunucu Ayarları (SMTP)</h3>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">SMTP Sunucusu</label><input type="text" class="form-control" name="mail_host" value="<?php echo htmlspecialchars($settings['mail_host'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label class="form-label">Port</label><input type="text" class="form-control" name="mail_port" value="<?php echo htmlspecialchars($settings['mail_port'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label class="form-label">Güvenlik</label><select name="mail_security" class="form-select"><option value="tls" <?php if(($settings['mail_security'] ?? '') == 'tls') echo 'selected'; ?>>TLS</option><option value="ssl" <?php if(($settings['mail_security'] ?? '') == 'ssl') echo 'selected'; ?>>SSL</option><option value="none" <?php if(($settings['mail_security'] ?? '') == 'none') echo 'selected'; ?>>Yok</option></select></div>
                    <div class="col-md-6 mb-3"><label class="form-label">SMTP Kullanıcı Adı</label><input type="text" class="form-control" name="mail_username" value="<?php echo htmlspecialchars($settings['mail_username'] ?? ''); ?>"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">SMTP Şifresi (Değiştirmek istemiyorsanız boş bırakın)</label><input type="password" class="form-control" name="mail_password"><input type="hidden" name="current_mail_password" value="<?php echo htmlspecialchars($settings['mail_password'] ?? ''); ?>"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Gönderen E-posta Adresi</label><input type="email" class="form-control" name="mail_from_address" value="<?php echo htmlspecialchars($settings['mail_from_address'] ?? ''); ?>"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Gönderen Adı</label><input type="text" class="form-control" name="mail_from_name" value="<?php echo htmlspecialchars($settings['mail_from_name'] ?? ''); ?>"></div>
                </div>
                <hr class="my-4">

                <h3>Mail İmzası</h3>
                <p class="small text-muted">Sistemden gönderilen tüm maillerin sonuna eklenecek olan standart imza. Resim ekleyebilirsiniz.</p>
                <div class="mb-3">
                    <textarea id="mail_signature_editor" name="mail_signature_html"><?php echo htmlspecialchars($settings['mail_signature_html'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">Ayarları Kaydet</button>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<!-- TinyMCE'yi ve Kaydetme Tetikleyicisini Aktif Eden Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Form gönderilmeden önce TinyMCE'nin içeriğini textarea'ya kaydet
    var form = document.querySelector('form[action="ayarlar.php"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            tinymce.triggerSave();
        });
    }

    // TinyMCE editörünü başlat
    tinymce.init({
        selector: '#mail_signature_editor',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        height: 300
    });
});
</script>