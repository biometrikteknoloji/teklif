<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'config/database.php';

$sql = "
    SELECT 
        p.id, p.urun_adi, p.fotograf_yolu,
        MAX(CASE WHEN pp.currency = 'TL' THEN pp.price ELSE NULL END) as price_tl,
        MAX(CASE WHEN pp.currency = 'USD' THEN pp.price ELSE NULL END) as price_usd,
        MAX(CASE WHEN pp.currency = 'EUR' THEN pp.price ELSE NULL END) as price_eur
    FROM products p
    LEFT JOIN product_prices pp ON p.id = pp.product_id
    GROUP BY p.id, p.urun_adi, p.fotograf_yolu
    ORDER BY p.urun_adi ASC
";
$stmt = $pdo->query($sql);
$urunler = $stmt->fetchAll();

include 'partials/header.php';
?>

<div class="main-wrapper">
    
    <?php include 'partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="user-info">
                Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!
            </div>
        </div>

        <div class="page-content">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Ürün Listesi</h1>
                <?php if ($_SESSION['user_role_id'] == 1): // Sadece Admin görebilir ?>
                    <a href="urun_ekle.php" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Yeni Ürün Ekle
                    </a>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 70px;">Fotoğraf</th>
                            <th>Ürün Adı</th>
                            <th class="text-end">Fiyat (TL)</th>
                            <th class="text-end">Fiyat (USD)</th>
                            <th class="text-end">Fiyat (EUR)</th>
                            <th class="text-center" style="width: 120px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($urunler) > 0): ?>
                            <?php foreach ($urunler as $urun): ?>
                                <tr>
                                    <td>
                                        <?php if ($urun['fotograf_yolu'] && file_exists($urun['fotograf_yolu'])): ?>
                                            <img src="<?php echo htmlspecialchars($urun['fotograf_yolu']); ?>" alt="<?php echo htmlspecialchars($urun['urun_adi']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="img-thumbnail text-center bg-light d-flex justify-content-center align-items-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($urun['urun_adi']); ?></td>
                                    <td class="text-end"><?php echo $urun['price_tl'] ? number_format($urun['price_tl'], 2, ',', '.') . ' ₺' : '-'; ?></td>
                                    <td class="text-end"><?php echo $urun['price_usd'] ? '$' . number_format($urun['price_usd'], 2, '.', ',') : '-'; ?></td>
                                    <td class="text-end"><?php echo $urun['price_eur'] ? number_format($urun['price_eur'], 2, ',', '.') . ' €' : '-'; ?></td>
                                    <td class="text-center action-buttons">
                                        <?php if ($_SESSION['user_role_id'] == 1): // Sadece Admin görebilir ?>
                                            <a href="urun_duzenle.php?id=<?php echo $urun['id']; ?>" class="action-icon" data-bs-toggle="tooltip" title="Düzenle">
                                                <i class="fas fa-pencil-alt text-primary"></i>
                                            </a>
                                            <a href="urun_sil.php?id=<?php echo $urun['id']; ?>" class="action-icon" onclick="return confirm('Bu ürünü silmek istediğinizden emin misiniz?');" data-bs-toggle="tooltip" title="Sil">
                                                <i class="fas fa-trash-alt text-danger"></i>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center p-4">Henüz kayıtlı ürün bulunmuyor.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php
include 'partials/footer.php'; 
?>