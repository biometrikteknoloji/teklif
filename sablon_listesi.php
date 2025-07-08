<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require 'config/database.php';

// Veritabanından tüm şablonları çek
$stmt = $pdo->query("SELECT * FROM proposal_templates ORDER BY template_name ASC");
$sablonlar = $stmt->fetchAll();

// Silme veya başarı durumları için mesajları kontrol et
$status_message = '';
$status_class = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted') {
        $status_message = 'Şablon başarıyla silindi.';
        $status_class = 'alert-success';
    } elseif ($_GET['status'] == 'success') {
        $status_message = 'Şablon başarıyla kaydedildi.';
        $status_class = 'alert-success';
    } elseif ($_GET['status'] == 'error') {
        $status_message = 'Bir hata oluştu.';
        $status_class = 'alert-danger';
    }
}

include 'partials/header.php';
?>
<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="user-info">Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!</div>
        </div>
        <div class="page-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Teklif Şablonları</h1>
                <a href="sablon_form.php" class="btn btn-success"><i class="fas fa-plus me-2"></i>Yeni Şablon Oluştur</a>
            </div>

            <?php if ($status_message): ?>
            <div class="alert <?php echo $status_class; ?> alert-dismissible fade show" role="alert">
                <?php echo $status_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- === YENİ VE MODERN TABLO YAPISI === -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Şablon Adı</th>
                                    <th class="text-end" style="width: 180px;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($sablonlar) > 0): ?>
                                    <?php foreach ($sablonlar as $sablon): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($sablon['template_name']); ?></strong></td>
                                            <td class="text-end">
                                                <a href="sablon_form.php?id=<?php echo $sablon['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Düzenle">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="sablon_sil.php?id=<?php echo $sablon['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu şablonu silmek istediğinizden emin misiniz?');" data-bs-toggle="tooltip" title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center p-4 text-muted">Henüz oluşturulmuş bir şablon yok.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'partials/footer.php'; ?>