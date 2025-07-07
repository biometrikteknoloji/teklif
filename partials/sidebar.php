<?php
$logo_path = false; 
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
            <a href="dashboard.php" class="d-block text-center p-2">
                <?php if ($logo_path && file_exists($logo_path)): ?>
                    <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Firma Logosu" style="max-height: 50px; width: auto;">
                <?php else: ?>
                    FİRMA ADI
                <?php endif; ?>
            </a>
        </div>
        
        <!-- === ANA MENÜ - İKONLAR EKLENDİ, RAPORLAR DAHİL EDİLDİ === -->
        <ul class="sidebar-menu">
            <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            </li>
            <li class="<?php echo (in_array($current_page, ['musteri_listesi.php', 'musteri_ekle.php', 'musteri_duzenle.php'])) ? 'active' : ''; ?>">
                <a href="musteri_listesi.php"><i class="fas fa-users me-2"></i>Müşteriler</a>
            </li>
            <li class="<?php echo (in_array($current_page, ['urun_listesi.php', 'urun_ekle.php', 'urun_duzenle.php'])) ? 'active' : ''; ?>">
                <a href="urun_listesi.php"><i class="fas fa-box-open me-2"></i>Ürünler</a>
            </li>
            <li class="<?php echo (in_array($current_page, ['teklif_listesi.php', 'teklif_view.php', 'teklif_revize_et.php', 'teklif_olustur.php'])) ? 'active' : ''; ?>">
                <a href="teklif_listesi.php"><i class="fas fa-file-invoice-dollar me-2"></i>Teklifler</a>
            </li>
            <li class="<?php echo ($current_page == 'raporlar.php') ? 'active' : ''; ?>">
                <a href="raporlar.php"><i class="fas fa-chart-pie me-2"></i>Raporlar</a>
            </li>
        </ul>
    </div>

    <div class="sidebar-bottom-menu">
        <?php if ($_SESSION['user_role_id'] == 1): ?>
            <!-- === YÖNETİM MENÜSÜ - AÇILIR/KAPANIR YAPI === -->
            <ul class="sidebar-menu">
                <li>
                    <a href="#managementSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-cogs me-2"></i>Yönetim
                    </a>
                    <ul class="collapse list-unstyled" id="managementSubmenu">
                        <?php $user_pages = ['kullanici_listesi.php', 'kullanici_form.php']; ?>
                        <li class="<?php echo (in_array($current_page, $user_pages)) ? 'active' : ''; ?>">
                            <a href="kullanici_listesi.php"><i class="fas fa-users-cog me-2"></i>Kullanıcılar</a>
                        </li>
                        <li class="<?php echo ($current_page == 'durum_yonetimi.php') ? 'active' : ''; ?>">
                            <a href="durum_yonetimi.php"><i class="fas fa-tags me-2"></i>Durum Yönetimi</a>
                        </li>
                         <li class="<?php echo ($current_page == 'ayarlar.php') ? 'active' : ''; ?>">
                            <a href="ayarlar.php"><i class="fas fa-cog me-2"></i>Ayarlar</a>
                        </li>
                    </ul>
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