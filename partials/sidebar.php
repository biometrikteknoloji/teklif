<?php
// Bu sidebar her sayfada çağrıldığı için, veritabanı bağlantısının
// zaten yapılmış olduğunu varsayıyoruz. $pdo değişkeni mevcut olmalı.

// Ayarlardan logo yolunu çek
$logo_path = false; // Varsayılan olarak logo yok
if (isset($pdo)) { 
    $logo_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'company_logo_path'");
    if($logo_stmt) {
        $logo_path = $logo_stmt->fetchColumn();
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-main-menu">
        <div class="sidebar-header">
            <!-- === DEĞİŞİKLİK BURADA: LOGO GÖSTERİMİ === -->
            <a href="dashboard.php" class="d-block text-center p-2">
                <?php if ($logo_path && file_exists($logo_path)): ?>
                    <!-- Eğer logo yüklenmişse, logoyu göster -->
                    <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Firma Logosu" style="max-height: 50px; width: auto;">
                <?php else: ?>
                    <!-- Logo yoksa, varsayılan metni göster -->
                    FİRMA ADI
                <?php endif; ?>
            </a>
            <!-- === DEĞİŞİKLİK SONU === -->
        </div>
        <ul class="sidebar-menu">
            <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><a href="dashboard.php">Dashboard</a></li>
            <li class="<?php echo (in_array($current_page, ['musteri_listesi.php', 'musteri_ekle.php', 'musteri_duzenle.php'])) ? 'active' : ''; ?>"><a href="musteri_listesi.php">Müşteriler</a></li>
            <li class="<?php echo (in_array($current_page, ['urun_listesi.php', 'urun_ekle.php', 'urun_duzenle.php'])) ? 'active' : ''; ?>"><a href="urun_listesi.php">Ürünler</a></li>
            <li class="<?php echo (in_array($current_page, ['teklif_listesi.php', 'teklif_view.php', 'teklif_revize_et.php'])) ? 'active' : ''; ?>"><a href="teklif_listesi.php">Teklifler</a></li>
            <li class="<?php echo ($current_page == 'teklif_olustur.php') ? 'active' : ''; ?>"><a href="teklif_olustur.php">Yeni Teklif Oluştur</a></li>
        </ul>
    </div>
    <div class="sidebar-bottom-menu">
        <?php if ($_SESSION['user_role_id'] == 1): ?>
        <ul class="sidebar-menu">
            <?php $user_pages = ['kullanici_listesi.php', 'kullanici_form.php']; ?>
            <li class="<?php echo (in_array($current_page, $user_pages)) ? 'active' : ''; ?>">
                <a href="kullanici_listesi.php"><i class="fas fa-users-cog me-2"></i>Kullanıcılar</a>
            </li>
            <li class="<?php echo ($current_page == 'ayarlar.php') ? 'active' : ''; ?>">
                <a href="ayarlar.php"><i class="fas fa-cog me-2"></i>Ayarlar</a>
            </li>
            <li class="<?php echo ($current_page == 'durum_yonetimi.php') ? 'active' : ''; ?>">
                <a href="durum_yonetimi.php"><i class="fas fa-tags me-2"></i>Durum Yönetimi</a>
            </li>
        </ul>
        <?php endif; ?>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-button">
                <i class="fas fa-sign-out-alt me-2"></i>
                <span>Çıkış Yap</span>
            </a>
        </div>
    </div>
</div>