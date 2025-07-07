<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'config/database.php';

$stmt = $pdo->query("SELECT * FROM customers ORDER BY unvan ASC");
$musteriler = $stmt->fetchAll();

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
                <h1>Müşteri Listesi</h1>
                <?php if ($_SESSION['user_role_id'] == 1): // Sadece Admin görebilir ?>
                  <!-- === DEĞİŞTİRİLECEK KOD BLOĞU === -->
<div class="btn-group">
    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#importExcelModal">
        <i class="fas fa-file-import me-2"></i>Excel'den İçe Aktar
    </button>
    <a href="musteri_ekle.php" class="btn btn-success">
        <i class="fas fa-plus me-2"></i>Yeni Müşteri Ekle
    </a>
</div>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Ünvan</th>
                            <th>Yetkili İsmi</th>
                            <th>Email</th>
                            <th>Telefon</th>
                            <th class="text-center" style="width: 120px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($musteriler) > 0): ?>
                            <?php foreach ($musteriler as $musteri): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($musteri['unvan']); ?></td>
                                    <td><?php echo htmlspecialchars($musteri['yetkili_ismi']); ?></td>
                                    <td><?php echo htmlspecialchars($musteri['email']); ?></td>
                                    <td><?php echo htmlspecialchars($musteri['telefon']); ?></td>
                                    <td class="text-center action-buttons">
                                        <?php if ($_SESSION['user_role_id'] == 1): // Sadece Admin görebilir ?>
                                            <a href="musteri_duzenle.php?id=<?php echo $musteri['id']; ?>" class="action-icon" data-bs-toggle="tooltip" title="Düzenle">
                                                <i class="fas fa-pencil-alt text-primary"></i>
                                            </a>
                                            <a href="musteri_sil.php?id=<?php echo $musteri['id']; ?>" class="action-icon" onclick="return confirm('Bu müşteriyi silmek istediğinizden emin misiniz?');" data-bs-toggle="tooltip" title="Sil">
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
                                <td colspan="5" class="text-center p-4">Henüz kayıtlı müşteri bulunmuyor.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
		<!-- === SAYFA SONUNA EKLENECEK KOD === -->

<!-- 1. Modal Penceresinin HTML Kodu -->
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importExcelModalLabel">Excel'den Müşteri Aktar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="import_customers.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="excelFile" class="form-label">Excel Dosyası (.xlsx)</label>
                        <input class="form-control" type="file" id="excelFile" name="excelFile" accept=".xlsx" required>
                    </div>
                    <div class="alert alert-info">
                        <strong>Önemli:</strong> Yükleyeceğiniz Excel dosyasının ilk satırı başlık olmalı ve sırasıyla şu sütunları içermelidir:
                        <br><code>unvan</code>, <code>adres</code>, <code>telefon</code>, <code>vergi_dairesi</code>, <code>vergi_no</code>, <code>email</code>, <code>cep_telefonu</code>, <code>yetkili_ismi</code>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" class="btn btn-primary">Yükle ve Aktar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. Modal'ın Çalışması İçin Gerekli JavaScript Kütüphaneleri -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
    </div>
</div>

<?php
include 'partials/footer.php'; 
?>