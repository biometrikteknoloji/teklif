<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role_id'] != 1) {
    header('Location: dashboard.php');
    exit('Yetkisiz Erişim.');
}
require 'config/database.php';

// ORİJİNAL KAYDETME KODU (SADECE RENK EKLENDİ)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_data = $_POST;
    // Renk Ayarını Kaydet
    if (isset($post_data['proposal_theme_color'])) {
        $key = 'proposal_theme_color'; $value = $post_data['proposal_theme_color'];
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?"); $stmt_check->execute([$key]);
        if ($stmt_check->fetchColumn() > 0) { $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?"); $stmt->execute([$value, $key]); } 
        else { $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)"); $stmt->execute([$key, $value]); }
    }
	//
	    // === YENİ EKLENECEK KOD: FİRMA LOGOSU YÜKLEME ===
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] == UPLOAD_ERR_OK) {
        $logo_key = 'company_logo_path';
        $upload_dir = 'uploads/'; // Ana dizindeki uploads klasörü

        if (!is_dir(__DIR__ . '/../' . $upload_dir)) {
            mkdir(__DIR__ . '/../' . $upload_dir, 0755, true);
        }

        // Eski logoyu sil
        $old_logo_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $old_logo_stmt->execute([$logo_key]);
        if ($old_logo_path = $old_logo_stmt->fetchColumn()) {
            if (file_exists(__DIR__ . '/../' . $old_logo_path)) {
                unlink(__DIR__ . '/../' . $old_logo_path);
            }
        }
        
        // Yeni logoyu yükle
        $file_extension = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
        $file_name = 'logo_' . time() . '.' . $file_extension;
        $file_path_for_db = $upload_dir . $file_name;
        $destination = __DIR__ . '/../' . $file_path_for_db;
        
        if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $destination)) {
            // Veritabanına kaydet
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
            $stmt_check->execute([$logo_key]);
            if ($stmt_check->fetchColumn() > 0) {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$file_path_for_db, $logo_key]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$logo_key, $file_path_for_db]);
            }
        }
    }
    // --- YENİ KOD SONU ---
	//
    // Varsayılan Notları Kaydet
    if (isset($post_data['proposal_default_notes'])) {
        $key = 'proposal_default_notes'; $value = $post_data['proposal_default_notes'];
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
        $stmt_check->execute([$key]);
        if ($stmt_check->fetchColumn() > 0) { $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?"); $stmt->execute([$value, $key]); } 
        else { $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)"); $stmt->execute([$key, $value]); }
    }
    // Resim Yükleme
    $upload_dir = 'uploads/proposal_assets/';
    if (!file_exists($upload_dir)) { mkdir($upload_dir, 0755, true); }
    if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] == UPLOAD_ERR_OK) { /* ... header yükleme kodu ... */ }
    if (isset($_FILES['footer_image']) && $_FILES['footer_image']['error'] == UPLOAD_ERR_OK) { /* ... footer yükleme kodu ... */ }
    // Mail Ayarlarını Kaydet
    $mail_settings = ['mail_host' => $post_data['mail_host'], 'mail_port' => $post_data['mail_port'], 'mail_username' => $post_data['mail_username'], 'mail_security' => $post_data['mail_security'], 'mail_from_address' => $post_data['mail_from_address'], 'mail_from_name' => $post_data['mail_from_name']];
    if (!empty($post_data['mail_password'])) { $mail_settings['mail_password'] = $post_data['mail_password']; } 
    else { if(isset($post_data['current_mail_password'])) { $mail_settings['mail_password'] = $post_data['current_mail_password']; } }
    foreach ($mail_settings as $key => $value) {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?"); $stmt_check->execute([$key]);
        if ($stmt_check->fetchColumn() > 0) { $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?"); $stmt->execute([$value, $key]); } 
        else { $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)"); $stmt->execute([$key, $value]); }
    }
    if (isset($post_data['mail_signature_html'])) {
        $stmt_sig = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'mail_signature_html'");
        $stmt_sig->execute([$post_data['mail_signature_html']]);
    }
    header("Location: ayarlar.php?status=success");
    exit();
}
        // --- YENİ VE GÜVENLİ LOGO YÜKLEME KODU ---
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] == UPLOAD_ERR_OK) {
       $upload_dir = '../uploads/'; // Ana dizindeki uploads klasörü

        // 1. Klasör var mı diye kontrol et, yoksa oluştur.
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // 2. Eski logoyu sil
        $old_logo_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $old_logo_stmt->execute([$logo_key]);
        if ($old_logo_path = $old_logo_stmt->fetchColumn()) {
            // Ana dizinden itibaren yolu birleştir
            $full_old_path = '../' . $old_logo_path;
            if (file_exists($full_old_path)) {
                unlink($full_old_path);
            }
        }
        
        // 3. Yeni logoyu yükle
        $file_extension = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
        $file_name = 'logo_' . time() . '.' . $file_extension;
        $file_path_for_db = 'uploads/' . $file_name; // Veritabanına kaydedilecek yol
        $destination = $upload_dir . $file_name;      // Dosyanın taşınacağı gerçek yol
        
        if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $destination)) {
            // 4. Veritabanına yeni yolu kaydet
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
            $stmt_check->execute([$logo_key]);
            if ($stmt_check->fetchColumn() > 0) {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$file_path_for_db, $logo_key]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$logo_key, $file_path_for_db]);
            }
        }
    }
    // --- YENİ KOD SONU ---

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
                
                <div class="accordion" id="settingsAccordion">

                    <!-- 1. Akordiyon Öğesi: Görsel Ayarlar -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                <i class="fas fa-palette me-2"></i> Teklif Görsel Ayarları
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#settingsAccordion">
                            <div class="accordion-body">
                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <h5>Teklif Üst Bilgisi (Antet)</h5>
                                        <input class="form-control" type="file" name="header_image" accept="image/png,image/jpeg">
                                        <?php if (!empty($settings['proposal_header_path']) && file_exists($settings['proposal_header_path'])): ?><div class="mt-2 p-2 border rounded"><img src="<?php echo $settings['proposal_header_path']; ?>" class="img-fluid"></div><?php endif; ?>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <h5>Teklif Alt Bilgisi (Altbilgi)</h5>
                                        <input class="form-control" type="file" name="footer_image" accept="image/png,image/jpeg">
                                        <?php if (!empty($settings['proposal_footer_path']) && file_exists($settings['proposal_footer_path'])): ?><div class="mt-2 p-2 border rounded"><img src="<?php echo $settings['proposal_footer_path']; ?>" class="img-fluid"></div><?php endif; ?>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6 mb-3">
                                        <h5>Teklif Tema Rengi</h5>
                                        <p class="small text-muted">PDF'teki başlıkların ana rengi.</p>
                                        <input type="color" class="form-control form-control-color" name="proposal_theme_color" value="<?php echo htmlspecialchars($settings['proposal_theme_color'] ?? '#004a99'); ?>" title="Renk Seçin">
                                    </div>
                                                 <!-- === YENİ EKLENECEK KOD: FİRMA LOGOSU ALANI === -->
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <h5>Firma Logosu</h5>
                                <p class="small text-muted">Sol menüde görünecek olan ana logonuz.</p>
                                <input class="form-control" type="file" name="company_logo" accept="image/png,image/jpeg,image/svg+xml">
                                <?php if (!empty($settings['company_logo_path']) && file_exists($settings['company_logo_path'])): ?>
                                    <div class="mt-2 p-2 border rounded bg-light" style="max-width: 250px;">
                                        <p class="mb-1 small text-muted">Mevcut Logo:</p>
                                        <img src="<?php echo htmlspecialchars($settings['company_logo_path']); ?>" class="img-fluid">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <hr class="my-4">
                        <!-- === YENİ KOD SONU === -->
                        <!-- === YENİ KOD SONU === -->
								</div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Akordiyon Öğesi: Teklif Notları -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                <i class="fas fa-file-alt me-2"></i> Varsayılan Teklif Notları
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#settingsAccordion">
                            <div class="accordion-body">
                                <p class="small text-muted">Yeni bir teklif oluşturulduğunda otomatik olarak eklenecek olan standart metinler.</p>
                                <textarea id="proposal_notes_editor" name="proposal_default_notes"><?php echo htmlspecialchars($settings['proposal_default_notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Akordiyon Öğesi: Mail Ayarları (GERİ EKLENDİ) -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                <i class="fas fa-server me-2"></i> Mail Sunucu Ayarları (SMTP)
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#settingsAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label class="form-label">SMTP Sunucusu</label><input type="text" class="form-control" name="mail_host" value="<?php echo htmlspecialchars($settings['mail_host'] ?? ''); ?>"></div>
                                    <div class="col-md-3 mb-3"><label class="form-label">Port</label><input type="text" class="form-control" name="mail_port" value="<?php echo htmlspecialchars($settings['mail_port'] ?? ''); ?>"></div>
                                    <div class="col-md-3 mb-3"><label class="form-label">Güvenlik</label><select name="mail_security" class="form-select"><option value="tls" <?php if(($settings['mail_security'] ?? '') == 'tls') echo 'selected'; ?>>TLS</option><option value="ssl" <?php if(($settings['mail_security'] ?? '') == 'ssl') echo 'selected'; ?>>SSL</option><option value="none" <?php if(($settings['mail_security'] ?? '') == 'none') echo 'selected'; ?>>Yok</option></select></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">SMTP Kullanıcı Adı</label><input type="text" class="form-control" name="mail_username" value="<?php echo htmlspecialchars($settings['mail_username'] ?? ''); ?>"></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">SMTP Şifresi (Değiştirmek istemiyorsanız boş bırakın)</label><input type="password" class="form-control" name="mail_password"><input type="hidden" name="current_mail_password" value="<?php echo htmlspecialchars($settings['mail_password'] ?? ''); ?>"></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">Gönderen E-posta Adresi</label><input type="email" class="form-control" name="mail_from_address" value="<?php echo htmlspecialchars($settings['mail_from_address'] ?? ''); ?>"></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">Gönderen Adı</label><input type="text" class="form-control" name="mail_from_name" value="<?php echo htmlspecialchars($settings['mail_from_name'] ?? ''); ?>"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Akordiyon Öğesi: Mail İmzası (GERİ EKLENDİ) -->
                     <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                <i class="fas fa-signature me-2"></i> Mail İmzası
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#settingsAccordion">
                            <div class="accordion-body">
                                <p class="small text-muted">Sistemden gönderilen tüm maillerin sonuna eklenecek olan standart imza.</p>
                                <textarea id="mail_signature_editor" name="mail_signature_html"><?php echo htmlspecialchars($settings['mail_signature_html'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Tüm Ayarları Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<!-- DÜZELTİLMİŞ JAVASCRIPT KODU -->
<script src="https://cdn.tiny.cloud/1/xdu70thlu8r088pco1pluv39l1bb9bq5oey200y2wzmmmw6z/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    function initializeTinyMCE(selector) {
        if (tinymce.get(selector.substring(1))) { return; }
        tinymce.init({
            selector: selector,
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            height: 250
        });
    }

    var accordionElement = document.getElementById('settingsAccordion');
    if (accordionElement) {
        accordionElement.addEventListener('shown.bs.collapse', function (event) {
            var activePanel = event.target;
            var editorTextarea = activePanel.querySelector('textarea');
            if (editorTextarea && editorTextarea.id) {
                initializeTinyMCE('#' + editorTextarea.id);
            }
        });
    }

    var form = document.querySelector('form[action="ayarlar.php"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            tinymce.triggerSave();
        });
    }
});
</script>