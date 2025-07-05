<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role_id'] != 1) {
    header('Location: dashboard.php');
    exit('Yetkisiz Erişim.');
}
require 'config/database.php';

// FORM İŞLEMLERİ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // YENİ DURUM EKLEME
    if (isset($_POST['add_status'])) {
        $stmt = $pdo->prepare("INSERT INTO proposal_statuses (status_name, status_color) VALUES (?, ?)");
        $stmt->execute([trim($_POST['new_status_name']), $_POST['new_status_color']]);
        header("Location: durum_yonetimi.php?status=added");
        exit();
    }

    // MEVCUT DURUMLARI GÜNCELLEME
    if (isset($_POST['update_statuses'])) {
        if (isset($_POST['statuses'])) {
            $stmt = $pdo->prepare("UPDATE proposal_statuses SET status_name = ?, status_color = ? WHERE id = ?");
            foreach ($_POST['statuses'] as $id => $status) {
                $stmt->execute([trim($status['name']), $status['color'], $id]);
            }
        }
        header("Location: durum_yonetimi.php?status=updated");
        exit();
    }

    // DURUM SİLME
    if (isset($_POST['delete_status'])) {
        $status_id_to_delete = $_POST['status_id'];
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE status_id = ?");
        $check_stmt->execute([$status_id_to_delete]);
        if ($check_stmt->fetchColumn() > 0) {
            header("Location: durum_yonetimi.php?status=in_use");
        } else {
            $stmt = $pdo->prepare("DELETE FROM proposal_statuses WHERE id = ?");
            $stmt->execute([$status_id_to_delete]);
            header("Location: durum_yonetimi.php?status=deleted");
        }
        exit();
    }
}

// Sayfa yüklendiğinde mevcut durumları çek
$statuses_stmt = $pdo->query("SELECT id, status_name, status_color FROM proposal_statuses ORDER BY id ASC");
$proposal_statuses = $statuses_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'partials/header.php';
?>
<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="user-info">Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!</div>
        </div>
        <div class="page-content">
            <h1>Teklif Durum Yönetimi</h1>
            <p class="lead">Teklif durumlarını, renklerini ve sıralamasını buradan yönetebilirsiniz.</p>
            
            <?php // Bildirim Mesajları ... ?>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Mevcut Durumları Güncelleme Formu -->
                    <form action="durum_yonetimi.php" method="POST" class="mb-5">
                        <h5>Mevcut Durumlar</h5>
                        <?php foreach ($proposal_statuses as $status): ?>
                        <div class="input-group mb-2">
                            <input type="color" class="form-control form-control-color" name="statuses[<?php echo $status['id']; ?>][color]" value="<?php echo htmlspecialchars($status['status_color']); ?>" title="Durum Rengini Seçin">
                            <input type="text" class="form-control" name="statuses[<?php echo $status['id']; ?>][name]" value="<?php echo htmlspecialchars($status['status_name']); ?>">
                            <button type="submit" name="delete_status" value="1" onclick="document.getElementById('statusIdToDelete').value='<?php echo $status['id']; ?>'; return confirm('`<?php echo htmlspecialchars($status['status_name']); ?>` durumunu silmek istediğinizden emin misiniz?');" class="btn btn-outline-danger">Sil</button>
                            <input type="hidden" id="statusIdToDelete" name="status_id" value="">
                        </div>
                        <?php endforeach; ?>
                        <button type="submit" name="update_statuses" class="btn btn-info mt-2">Tüm Değişiklikleri Kaydet</button>
                    </form>
                    
                    <!-- Yeni Durum Ekleme Formu -->
                    <form action="durum_yonetimi.php" method="POST">
                        <h5>Yeni Durum Ekle</h5>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" name="new_status_color" value="#6c757d">
                            <input type="text" name="new_status_name" class="form-control" placeholder="Örn: Takip Ediliyor" required>
                            <button class="btn btn-success" type="submit" name="add_status">Ekle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'partials/footer.php'; ?>