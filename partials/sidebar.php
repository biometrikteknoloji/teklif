<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <!-- Üst Kısım: Ana Menü -->
    <div class="sidebar-main-menu">
        <div class="sidebar-header">
            <a href="dashboard.php">FİRMA ADI</a>
        </div>
        <ul class="sidebar-menu">
            <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php">Dashboard</a>
            </li>
            <li class="<?php echo (in_array($current_page, ['musteri_listesi.php', 'musteri_ekle.php', 'musteri_duzenle.php'])) ? 'active' : ''; ?>">
                <a href="musteri_listesi.php">Müşteriler</a>
            </li>
            <li class="<?php echo (in_array($current_page, ['urun_listesi.php', 'urun_ekle.php', 'urun_duzenle.php'])) ? 'active' : ''; ?>">
                <a href="urun_listesi.php">Ürünler</a>
            </li>
            <li class="<?php echo (in_array($current_page, ['teklif_listesi.php', 'teklif_view.php', 'teklif_revize_et.php'])) ? 'active' : ''; ?>">
                <a href="teklif_listesi.php">Teklifler</a>
            </li>
            <li class="<?php echo ($current_page == 'teklif_olustur.php') ? 'active' : ''; ?>">
                <a href="teklif_olustur.php">Yeni Teklif Oluştur</a>
            </li>
        </ul>
    </div>

    <!-- Alt Kısım: Ayarlar ve Çıkış -->
    <div class="sidebar-bottom-menu">
        <?php if ($_SESSION['user_role_id'] == 1): // Sadece Admin görebilir ?>
        <ul class="sidebar-menu">
            
            <!-- === YENİ KULLANICI YÖNETİMİ MENÜSÜ === -->
            <?php 
                // Kullanıcı ile ilgili sayfalarda bu menünün aktif olması için bir dizi tanımlıyoruz
                $user_pages = ['kullanici_listesi.php', 'kullanici_form.php'];
            ?>
            <li class="<?php echo (in_array($current_page, $user_pages)) ? 'active' : ''; ?>">
                <a href="kullanici_listesi.php">
                    <i class="fas fa-users-cog me-2"></i>Kullanıcılar
                </a>
            </li>
            <!-- === YENİ MENÜ SONU === -->

            <li class="<?php echo ($current_page == 'ayarlar.php') ? 'active' : ''; ?>">
                <a href="ayarlar.php">
                    <i class="fas fa-cog me-2"></i>Ayarlar
                </a>
				<!-- ... diğer menü öğeleri ... -->
<li class="<?php echo ($currentPage == 'ayarlar.php') ? 'active' : ''; ?>">
    <a href="ayarlar.php"><i class="fas fa-cog"></i> Ayarlar</a>
</li>

<!-- === YENİ LİNKİ BURAYA YAPIŞTIR === -->
<li class="<?php echo ($currentPage == 'durum_yonetimi.php') ? 'active' : ''; ?>">
    <a href="durum_yonetimi.php"><i class="fas fa-tags"></i> Durum Yönetimi</a>
</li>

<li>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
</li>
<!-- ... -->
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