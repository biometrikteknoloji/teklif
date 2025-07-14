<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require 'config/database.php';

$stmt = $pdo->query("
    SELECT pr.*, c.unvan 
    FROM proformas pr
    JOIN customers c ON pr.customer_id = c.id
    ORDER BY pr.proforma_date DESC, pr.id DESC
");
$proformas = $stmt->fetchAll();

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
                <h1>Proforma Faturalar</h1>
                <a href="proforma_form.php" class="btn btn-success"><i class="fas fa-plus me-2"></i>Yeni Proforma Oluştur</a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Proforma No</th>
                                    <th>Müşteri</th>
                                    <th>Tarih</th>
                                    <th class="text-end">Tutar</th>
                                    <th class="text-center">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($proformas) > 0): ?>
                                    <?php foreach ($proformas as $proforma): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($proforma['proforma_no']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($proforma['unvan']); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($proforma['proforma_date'])); ?></td>
                                            <td class="text-end fw-bold"><?php echo number_format($proforma['total_amount'], 2, ',', '.') . ' ' . $proforma['currency']; ?></td>
                                            <td class="text-center">
                                                <a href="proforma_pdf.php?id=<?php echo $proforma['id']; ?>" target="_blank" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="PDF Görüntüle"><i class="fas fa-file-pdf"></i></a>
                                                <a href="proforma_form.php?id=<?php echo $proforma['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Düzenle"><i class="fas fa-edit"></i></a>
                                                <a href="proforma_sil.php?id=<?php echo $proforma['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu proformayı silmek istediğinizden emin misiniz?');" data-bs-toggle="tooltip" title="Sil"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center p-4 text-muted">Henüz proforma oluşturulmamış.</td></tr>
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