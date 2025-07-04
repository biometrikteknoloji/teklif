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
                    <a href="musteri_ekle.php" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Yeni Müşteri Ekle
                    </a>
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
    </div>
</div>

<?php
include 'partials/footer.php'; 
?>