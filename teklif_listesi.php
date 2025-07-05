<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require 'config/database.php';

// Arkaplan rengine göre yazı rengini belirleyen fonksiyon
function getTextColorForBackground($hexColor) {
    if (!$hexColor) return '#ffffff';
    $hexColor = str_replace('#', '', $hexColor);
    if (strlen($hexColor) != 6) return '#ffffff';
    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance > 0.5 ? '#000000' : '#ffffff';
}

// Renk bilgisini de içeren ana teklif sorgusu
$sql = "SELECT p.id, p.proposal_no, p.proposal_date, p.grand_total, p.currency, p.is_sent_by_mail, p.mail_sent_at, c.unvan, ps.status_name, ps.id as status_id, ps.status_color, u.full_name as user_name, (SELECT COUNT(*) FROM proposals r WHERE r.original_proposal_id = p.id) as revision_count FROM proposals p JOIN customers c ON p.customer_id = c.id JOIN proposal_statuses ps ON p.status_id = ps.id JOIN users u ON p.user_id = u.id WHERE p.original_proposal_id IS NULL";
$params = [];
if ($_SESSION['user_role_id'] != 1) { 
    $sql .= " AND p.user_id = ?"; 
    $params[] = $_SESSION['user_id']; 
}
$sql .= " ORDER BY p.created_at DESC, p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$teklifler = $stmt->fetchAll();

// Renk bilgisini de içeren dropdown menü için tüm durumları çek
$statuses_stmt = $pdo->query("SELECT id, status_name, status_color FROM proposal_statuses ORDER BY id ASC");
$all_statuses = $statuses_stmt->fetchAll(PDO::FETCH_ASSOC);

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

            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="proposalSearchInput" placeholder="Teklif no veya müşteri adıyla ara...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="proposalsTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="min-width: 150px;">Teklif No</th>
                            <th>Müşteri</th>
                            <th class="text-end">Tutar</th>
                            <th>Durum</th>
                            <th class="text-center">Rev.</th>
                            <th class="text-center">Mail</th>
                            <th class="text-center">İşlemler</th>
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
                                    <td class="text-end fw-bold"><?php echo number_format($teklif['grand_total'], 2, ',', '.'); ?> <?php echo $teklif['currency']; ?></td>
                                    <td>
                                        <div class="dropdown status-dropdown">
                                            <?php $textColor = getTextColorForBackground($teklif['status_color']); ?>
                                            <button class="btn btn-sm dropdown-toggle" type="button" 
                                                    id="dropdownMenuButton-<?php echo $teklif['id']; ?>" 
                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                    style="background-color: <?php echo htmlspecialchars($teklif['status_color']); ?>; color: <?php echo $textColor; ?>; border-color: <?php echo htmlspecialchars($teklif['status_color']); ?>; min-width: 120px;">
                                                <?php echo htmlspecialchars($teklif['status_name']); ?>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton-<?php echo $teklif['id']; ?>">
                                                <?php foreach ($all_statuses as $status): ?>
                                                    <li>
                                                        <a class="dropdown-item change-status-btn" href="#" 
                                                           data-proposal-id="<?php echo $teklif['id']; ?>" 
                                                           data-status-id="<?php echo $status['id']; ?>">
                                                            <?php echo htmlspecialchars($status['status_name']); ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
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
                                            <a href="teklif_view.php?id=<?php echo $teklif['id']; ?>" class="action-icon" data-bs-toggle="tooltip" title="Görüntüle/Gönder"><i class="fas fa-envelope text-info fs-5"></i></a>
                                            <a href="teklif_revize_et.php?id=<?php echo $teklif['id']; ?>" class="action-icon" data-bs-toggle="tooltip" title="Revize Et"><i class="fas fa-history text-success fs-5"></i></a>
                                            <?php if ($_SESSION['user_role_id'] == 1): ?>
                                                <a href="teklif_sil.php?id=<?php echo $teklif['id']; ?>" class="action-icon" onclick="return confirm('Bu teklifi ve tüm revizyonlarını silmek istediğinizden emin misiniz?');" data-bs-toggle="tooltip" title="Sil"><i class="fas fa-trash-alt text-danger fs-5"></i></a>
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

<?php include 'partials/footer.php'; ?>

<script>
$(document).ready(function(){
    // ... (Arama ve getTextColorForBackgroundJS fonksiyonları aynı, değişiklik yok)

    // Durum güncelleme AJAX scripti (URL DÜZELTİLDİ)
    $('#proposalsTable').on('click', '.change-status-btn', function(e) {
        e.preventDefault(); 
        var link = $(this);
        var proposalId = link.data('proposal-id');
        var newStatusId = link.data('status-id');
        
        $.ajax({
            url: 'admin/ajax/update_proposal_status.php', // <--- DEĞİŞİKLİK BURADA
            type: 'GET',
            data: {
                proposal_id: proposalId,
                status_id: newStatusId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var button = $('#dropdownMenuButton-' + proposalId);
                    button.text(response.new_status_name);
                    
                    var newStatusColor = response.new_status_color;
                    button.css('background-color', newStatusColor);
                    button.css('border-color', newStatusColor);
                    
                    var textColor = getTextColorForBackgroundJS(newStatusColor);
                    button.css('color', textColor);
                } else {
                    alert('Hata: ' + response.message);
                }
            },
            error: function() {
                alert('Sunucuyla iletişim kurulamadı. Lütfen tekrar deneyin.');
            }
        });
    });
});
</script>