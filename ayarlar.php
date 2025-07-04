<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role_id'] != 1) {
    header('Location: dashboard.php');
    exit('Yetkisiz Erişim.');
}
require 'config/database.php';

// --- YENİ BÖLÜM: TEKLİF DURUMU EKLEME/SİLME İŞLEMLERİ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_type'])) {
    // YENİ DURUM EKLEME
    if ($_POST['form_type'] == 'add_status' && !empty($_POST['new_status_name'])) {
        $new_status_name = trim($_POST['new_status_name']);
        $stmt = $pdo->prepare("INSERT INTO proposal_statuses (status_name) VALUES (?)");
        $stmt->execute([$new_status_name]);
        header("Location: ayarlar.php?status=status_added#collapseStatus");
        exit();
    }
    // DURUM SİLME
    if ($_POST['form_type'] == 'delete_status' && !empty($_POST['status_id'])) {
        $status_id_to_delete = $_POST['status_id'];
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE status_id = ?");
        $check_stmt->execute([$status_id_to_delete]);
        if ($check_stmt->fetchColumn() > 0) {
            header("Location: ayarlar.php?status=status_in_use#collapseStatus");
            exit();
        } else {
            $stmt = $pdo->prepare("DELETE FROM proposal_statuses WHERE id = ?");
            $stmt->execute([$status_id_to_delete]);
            header("Location: ayarlar.php?status=status_deleted#collapseStatus");
            exit();
        }
    }
}

// --- SENİN ÇALIŞAN ANA AYARLARI KAYDETME KODUN (HİÇ DOKUNULMADI) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['form_type'])) {
    $post_data = $_POST;
    if (isset($post_data['proposal_theme_color'])) { $key = 'proposal_theme_color'; $value = $post_data['proposal_theme_color']; $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?"); $stmt_check->execute([$key]); if ($stmt_check->fetchColumn() > 0) { $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?"); $stmt->execute([$value, $key]); }  else { $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)"); $stmt->execute([$key, $value]); } }
    if (isset($post_data['proposal_default_notes'])) { $key = 'proposal_default_notes'; $value = $post_data['proposal_default_notes']; $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?"); $stmt_check->execute([$key]); if ($stmt_check->fetchColumn() > 0) { $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?"); $stmt->execute([$value, $key]); } else { $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)"); $stmt->execute([$key, $value]); } }
    $upload_dir = 'uploads/proposal_assets/';
    if (!file_exists($upload_dir)) { mkdir($upload_dir, 0755, true); }
    if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] == UPLOAD_ERR_OK) { $old_file_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'proposal_header_path'"); $old_file_stmt->execute(); $old_file_path = $old_file_stmt->fetchColumn(); if ($old_file_path && file_exists($old_file_path)) { unlink($old_file_path); } $file_name = 'header_' . time() . '.png'; $file_path = $upload_dir . $file_name; move_uploaded_file($_FILES['header_image']['tmp_name'], $file_path); $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'proposal_header_path'"); $stmt->execute([$file_path]); }
    if (isset($_FILES['footer_image']) && $_FILES['footer_image']['error'] == UPLOAD_ERR_OK) { $old_file_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'proposal_footer_path'"); $old_file_stmt->execute(); $old_file_path = $old_file_stmt->fetchColumn(); if ($old_file_path && file_exists($old_file_path)) { unlink($old_file_path); } $file_name = 'footer_' . time() . '.png'; $file_path = $upload_dir . $file_name; move_uploaded_file($_FILES['footer_image']['tmp_name'], $file_path); $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'proposal_footer_path'"); $stmt->execute([$file_path]); }
    $mail_settings = ['mail_host' => $post_data['mail_host'], 'mail_port' => $post_data['mail_port'], 'mail_username' => $post_data['mail_username'], 'mail_security' => $post_data['mail_security'], 'mail_from_address' => $post_data['mail_from_address'], 'mail_from_name' => $post_data['mail_from_name']];
    if (!empty($post_data['mail_password'])) { $mail_settings['mail_password'] = $post_data['mail_password']; }  else { if(isset($post_data['current_mail_password'])) { $mail_settings['mail_password'] = $post_data['current_mail_password']; } }
    foreach ($mail_settings as $key => $value) { $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?"); $stmt_check->execute([$key]); if ($stmt_check->fetchColumn() > 0) { $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?"); $stmt->execute([$value, $key]); }  else { $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)"); $stmt->execute([$key, $value]); } }
    if (isset($post_data['mail_signature_html'])) { $stmt_sig = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'mail_signature_html'"); $stmt_sig->execute([$post_data['mail_signature_html']]); }
    header("Location: ayarlar.php?status=success");
    exit();
}

// Verileri Çek
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$statuses_stmt = $pdo->query("SELECT * FROM proposal_statuses ORDER BY id ASC");
$proposal_statuses = $statuses_stmt->fetchAll();

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
            
            <?php // Bildirim Mesajları
            if (isset($_GET['status'])) {
                $status_messages = [
                    'success' => ['class' => 'success', 'text' => 'Genel ayarlar başarıyla kaydedildi.'],
                    'status_added' => ['class' => 'success', 'text' => 'Yeni teklif durumu başarıyla eklendi.'],
                    'status_deleted' => ['class' => 'success', 'text' => 'Teklif durumu başarıyla silindi.'],
                    'status_in_use' => ['class' => 'danger', 'text' => 'HATA: Bu durum aktif tekliflerde kullanıldığı için silinemez!']
                ];
                if(array_key_exists($_GET['status'], $status_messages)) {
                    $msg = $status_messages[$_GET['status']];
                    echo "<div class='alert alert-{$msg['class']}'>{$msg['text']}</div>";
                }
            }
            ?>
            
            <!-- Ana Form -->
            <form action="ayarlar.php" method="POST" enctype="multipart/form-data">
                
                <div class="accordion" id="settingsAccordion">

                    <!-- 1. Görsel Ayarlar -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne"><i class="fas fa-palette me-2"></i> Teklif Görsel Ayarları</button>
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Teklif Notları -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo"><i class="fas fa-file-alt me-2"></i> Varsayılan Teklif Notları</button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#settingsAccordion">
                            <div class="accordion-body"><p class="small text-muted">Yeni bir teklif oluşturulduğunda otomatik olarak eklenecek olan standart metinler.</p><textarea id="proposal_notes_editor" name="proposal_default_notes"><?php echo htmlspecialchars($settings['proposal_default_notes'] ?? ''); ?></textarea></div>
                        </div>
                    </div>

                    <!-- 3. Mail Ayarları -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree"><i class="fas fa-server me-2"></i> Mail Sunucu Ayarları (SMTP)</button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#settingsAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label class="form-label">SMTP Sunucusu</label><input type="text" class="form-control" name="mail_host" value="<?php echo htmlspecialchars($settings['mail_host'] ?? ''); ?>"></div>
                                    <div class="col-md-3 mb-3"><label class="form-label">Port</label><input type="text" class="form-control" name="mail_port" value="<?php echo htmlspecialchars($settings['mail_port'] ?? ''); ?>"></div>
                                    <div class="col-md-3 mb-3"><label class="form-label">Güvenlik</label><select name="mail_security" class="form-select"><option value="tls" <?php if(isset($settings['mail_security']) && $settings['mail_security'] == 'tls') echo 'selected'; ?>>TLS</option><option value="ssl" <?php if(isset($settings['mail_security']) && $settings['mail_security'] == 'ssl') echo 'selected'; ?>>SSL</option><option value="none" <?php if(isset($settings['mail_security']) && $settings['mail_security'] == 'none') echo 'selected'; ?>>Yok</option></select></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">SMTP Kullanıcı Adı</label><input type="text" class="form-control" name="mail_username" value="<?php echo htmlspecialchars($settings['mail_username'] ?? ''); ?>"></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">SMTP Şifresi (Değiştirmek istemiyorsanız boş bırakın)</label><input type="password" class="form-control" name="mail_password"><input type="hidden" name="current_mail_password" value="<?php echo htmlspecialchars($settings['mail_password'] ?? ''); ?>"></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">Gönderen E-posta Adresi</label><input type="email" class="form-control" name="mail_from_address" value="<?php echo htmlspecialchars($settings['mail_from_address'] ?? ''); ?>"></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">Gönderen Adı</label><input type="text" class="form-control" name="mail_from_name" value="<?php echo htmlspecialchars($settings['mail_from_name'] ?? ''); ?>"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 4. Mail İmzası -->
                     <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour"><i class="fas fa-signature me-2"></i> Mail İmzası</button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#settingsAccordion">
                            <div class="accordion-body"><p class="small text-muted">Sistemden gönderilen tüm maillerin sonuna eklenecek olan standart imza.</p><textarea id="mail_signature_editor" name="mail_signature_html"><?php echo htmlspecialchars($settings['mail_signature_html'] ?? ''); ?></textarea></div>
                        </div>
                    </div>
                    
                    <!-- 5. Akordiyon Öğesi: Teklif Durumları -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingStatus">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStatus" aria-expanded="false" aria-controls="collapseStatus">
                                <i class="fas fa-tags me-2"></i> Teklif Durum Ayarları
                            </button>
                        </h2>
                        <div id="collapseStatus" class="accordion-collapse collapse" aria-labelledby="headingStatus" data-bs-parent="#settingsAccordion">
                            <div class="accordion-body">
                                <p class="small text-muted">Teklif listesinde kullanıcılara sunulacak durum seçeneklerini buradan yönetebilirsiniz.</p>
                                <h5>Mevcut Durumlar</h5>
                                <ul class="list-group mb-4">
                                    <?php foreach ($proposal_statuses as $status): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($status['status_name']); ?>
                                        <form action="ayarlar.php" method="POST" onsubmit="return confirm('Bu durumu silmek istediğinizden emin misiniz?');" style="display:inline;">
                                            <input type="hidden" name="form_type" value="delete_status">
                                            <input type="hidden" name="status_id" value="<?php echo $status['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Sil"><i class="fas fa-times-circle"></i></button>
                                        </form>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <h5>Yeni Durum Ekle</h5>
                                <form action="ayarlar.php" method="POST">
                                    <input type="hidden" name="form_type" value="add_status">
                                    <div class="input-group">
                                        <input type="text" name="new_status_name" class="form-control" placeholder="Örn: Müşteri Onayladı" required>
                                        <button class="btn btn-outline-success" type="submit">Ekle</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div> <!-- Akordiyon sonu -->

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Genel Ayarları Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<!-- Script'ler -->
<script src="https://cdn.tiny.cloud/1/xdu70thlu8r088pco1pluv39l1bb9bq5oey200y2wzmmmw6z/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // ... (TinyMCE script'i aynı, değişiklik yok) ...
});
</script>