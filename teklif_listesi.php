<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require 'config/database.php';

// Veritabanı sorgusu - Değişiklik yok
$sql = "SELECT p.id, p.proposal_no, p.proposal_date, p.grand_total, p.currency, p.is_sent_by_mail, p.mail_sent_at, c.unvan, ps.status_name, ps.id as status_id, u.full_name as user_name, (SELECT COUNT(*) FROM proposals r WHERE r.original_proposal_id = p.id) as revision_count FROM proposals p JOIN customers c ON p.customer_id = c.id JOIN proposal_statuses ps ON p.status_id = ps.id JOIN users u ON p.user_id = u.id WHERE p.original_proposal_id IS NULL";
$params = [];
if ($_SESSION['user_role_id'] != 1) { $sql .= " AND p.user_id = ?"; $params[] = $_SESSION['user_id']; }
$sql .= " ORDER BY p.created_at DESC, p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$teklifler = $stmt->fetchAll();

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
                <h1>Gönderilen Teklifler</h1>
                <a href="teklif_olustur.php" class="btn btn-success"><i class="fas fa-plus me-2"></i>Yeni Teklif Oluştur</a>
            </div>

            <!-- === YENİ ARAMA KUTUSU === -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text" id="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="proposalSearchInput" placeholder="Teklif no veya müşteri adıyla ara...">
                    </div>
                </div>
            </div>
            <!-- === YENİ ARAMA KUTUSU SONU === -->

            <div class="table-responsive">
                <!-- === TABLOYA ID EKLENDİ === -->
                <table class="table table-striped table-hover align-middle" id="proposalsTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="min-width: 150px;">Teklif No</th>
                            <th>Müşteri</th>
                            <th class="text-end">Tutar</th>
                            <th>Durum</th>
                            <th class="text-center">Rev.</th>
                            <th class="text-center">Mail</th>
                            <th class="text-center" style="width: 120px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($teklifler) > 0): ?>
                            <?php foreach ($teklifler as $teklif): ?>
                                <tr>
                                    <td>
                                        <a href="teklif_view.php?id=<?php echo $teklif['id']; ?>">
                                            <strong><?php echo htmlspecialchars($teklif['proposal_no']); ?></strong>
                                        </a><br>
                                        <small class="text-muted"><?php echo date('d.m.Y', strtotime($teklif['proposal_date'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($teklif['unvan']); ?></td>
                                    <td class="text-end fw-bold"><?php echo number_format($teklif['grand_total'], 2, ',', '.') . ' ' . $teklif['currency']; ?></td>
                                    <td>
                                        <?php 
                                            $status_class = 'bg-secondary';
                                            if($teklif['status_id'] == 5) $status_class = 'bg-success';
                                            if($teklif['status_id'] == 1 || $teklif['status_id'] == 2) $status_class = 'bg-warning text-dark';
                                            if($teklif['status_id'] == 3 || $teklif['status_id'] == 4) $status_class = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($teklif['status_name']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($teklif['revision_count'] > 0): ?>
                                            <a href="teklif_view.php?id=<?php echo $teklif['id']; ?>" class="badge bg-info rounded-pill text-dark text-decoration-none">
                                                R<?php echo $teklif['revision_count']; ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($teklif['is_sent_by_mail']): ?>
                                            <i class="fas fa-check-circle text-success fs-5" data-bs-toggle="tooltip" title="Mail Gönderildi (<?php echo date('d.m.Y H:i', strtotime($teklif['mail_sent_at'])); ?>)"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-muted fs-5" data-bs-toggle="tooltip" title="Mail Gönderilmedi"></i>
                                        <?php endif; ?>
                                    </td>
                                   <td class="action-buttons">
                                        <div class="d-flex justify-content-center align-items-center gap-3">
                                            <a href="teklif_view.php?id=<?php echo $teklif['id']; ?>" class="action-icon" data-bs-toggle="tooltip" title="Görüntüle/Gönder">
                                                <i class="fas fa-envelope text-info fs-5"></i>
                                            </a>
                                            <a href="teklif_revize_et.php?id=<?php echo $teklif['id']; ?>" class="action-icon" data-bs-toggle="tooltip" title="Revize Et">
                                                <i class="fas fa-history text-success fs-5"></i>
                                            </a>
                                            <?php if ($_SESSION['user_role_id'] == 1): ?>
                                                <a href="teklif_sil.php?id=<?php echo $teklif['id']; ?>" class="action-icon" onclick="return confirm('Bu teklifi ve tüm revizyonlarını silmek istediğinizden emin misiniz?');" data-bs-toggle="tooltip" title="Sil">
                                                    <i class="fas fa-trash-alt text-danger fs-5"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center p-4">Gösterilecek teklif bulunmuyor.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- === YENİ JAVASCRIPT KODU === -->
<script>
$(document).ready(function(){
    // Arama kutusuna her tuşa basıldığında bu fonksiyon çalışacak
    $("#proposalSearchInput").on("keyup", function() {
        var value = $(this).val().toLowerCase(); // Aranan kelimeyi al ve küçük harfe çevir
        
        // Tablonun tbody'sindeki tüm satırlarda (tr) döngü kur
        $("#proposalsTable tbody tr").filter(function() {
            // "Gösterilecek teklif bulunmuyor." satırını her zaman atla
            if ($(this).find('td[colspan="7"]').length > 0) {
                return false; // Bu satırı filtreleme dışında tut
            }

            // Mevcut satırın içeriğini al ve küçük harfe çevir
            var rowText = $(this).text().toLowerCase();
            
            // Satırın içeriği, aranan kelimeyi içeriyor mu diye bak
            // İçermiyorsa gizle, içeriyorsa göster
            $(this).toggle(rowText.indexOf(value) > -1);
        });
    });
});
</script>
<!-- === YENİ JAVASCRIPT KODU SONU === -->

<?php include 'partials/footer.php'; ?>