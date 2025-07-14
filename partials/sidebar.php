<?php
// Mevcut sayfanın adını alıyoruz
$current_page = basename($_SERVER['PHP_SELF']);

// Logo yolunu veritabanından çekme
$logo_path = false; 
if (isset($pdo)) { 
    $logo_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'company_logo_path'");
    if($logo_stmt) {
        $logo_path = $logo_stmt->fetchColumn();
    }
}
?>
<!-- === YENİ VE MODERN HTML YAPISI === -->
<aside class="sidebar">
    <div class="logo">
        <a href="dashboard.php" class="d-block text-center text-decoration-none">
            <?php if ($logo_path && file_exists($logo_path)): ?>
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Firma Logosu" style="max-height: 50px; width: auto;">
            <?php else: ?>
                <span style="color: #fff;">FİRMA ADI</span>
            <?php endif; ?>
        </a>
    </div>

    <ul class="nav-links">
        <li>
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="musteri_listesi.php" class="<?php echo (in_array($current_page, ['musteri_listesi.php', 'musteri_ekle.php', 'musteri_duzenle.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Müşteriler
            </a>
        </li>
		        <li>
            <a href="proforma_listesi.php" class="<?php echo (in_array($current_page, ['proforma_listesi.php', 'proforma_form.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-globe-americas"></i> Proforma
            </a>
        </li>
        <li>
            <a href="urun_listesi.php" class="<?php echo (in_array($current_page, ['urun_listesi.php', 'urun_ekle.php', 'urun_duzenle.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-box-open"></i> Ürünler
            </a>
        </li>
        <li>
            <a href="teklif_listesi.php" class="<?php echo (in_array($current_page, ['teklif_listesi.php', 'teklif_view.php', 'teklif_revize_et.php', 'teklif_olustur.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i> Teklifler
            </a>
        </li>
        <li>
            <a href="raporlar.php" class="<?php echo ($current_page == 'raporlar.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Raporlar
            </a>
        </li>
		        <li>
            <a href="sablon_listesi.php" class="<?php echo (in_array($current_page, ['sablon_listesi.php', 'sablon_form.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Şablonlar
            </a>
        </li>

        <?php if (isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] == 1): // Sadece Admin görebilir ?>
            <hr style="border-color: #444;">
            <li>
                <a href="kullanici_listesi.php" class="<?php $user_pages = ['kullanici_listesi.php', 'kullanici_form.php']; echo (in_array($current_page, $user_pages)) ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i> Kullanıcılar
                </a>
            </li>
            <li>
                <a href="durum_yonetimi.php" class="<?php echo ($current_page == 'durum_yonetimi.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Durum Yönetimi
                </a>
            </li>
             <li>
                <a href="ayarlar.php" class="<?php echo ($current_page == 'ayarlar.php') ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Ayarlar
                </a>
            </li>
        <?php endif; ?>

        <hr style="border-color: #444;">
        <li class="mt-auto"> <!-- Çıkış Yap butonunu en alta iter -->
            <a href="logout.php" style="background-color: #dc3545; color: white;">
                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
            </a>
        </li>
    </ul>
</aside>