<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role_id'] != 1) {
    header('Location: dashboard.php');
    exit('Yetkisiz Erişim.');
}

require 'config/database.php';

$sql = "SELECT u.id, u.full_name, u.username, u.email, u.phone, u.is_active, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        ORDER BY u.full_name ASC";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();

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
                <h1>Kullanıcı Yönetimi</h1>
                <a href="kullanici_form.php" class="btn btn-success"><i class="fas fa-plus me-2"></i>Yeni Kullanıcı Ekle</a>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="userSearchInput" placeholder="Ad, e-posta veya telefon ile ara...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="usersTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Ad Soyad</th>
                            <th>Kullanıcı Adı</th>
                            <th>E-posta</th>
                            <th>Telefon</th>
                            <th>Rol</th>
                            <th class="text-center">Durum</th>
                            <th class="text-center" style="width: 120px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($user['role_name']); ?></span></td>
                                    <td class="text-center">
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Pasif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
    <!-- HATA TESPİTİ İÇİN DEĞİŞKENLERİ YAZDIRALIM -->
    <!-- Döngüdeki Kullanıcı ID: <?php echo $user['id']; ?> / Session ID: <?php echo $_SESSION['user_id']; ?> -->

    <div class="d-flex justify-content-center align-items-center gap-3">
        <!-- Düzenle Butonu -->
        <a href="kullanici_form.php?id=<?php echo $user['id']; ?>" class="action-icon" data-bs-toggle="tooltip" title="Düzenle">
            <i class="fas fa-edit text-primary fs-5"></i>
        </a>
        
        <!-- SİL BUTONU -->
        <?php if ($user['id'] != $_SESSION['user_id']): ?>
            <a href="kullanici_sil.php?id=<?php echo $user['id']; ?>" class="action-icon" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');" data-bs-toggle="tooltip" title="Sil">
                <i class="fas fa-trash-alt text-danger fs-5"></i>
            </a>
        <?php endif; ?>
    </div>
</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center p-4">Kayıtlı kullanıcı bulunmuyor.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $("#userSearchInput").on("keyup", function() {
        var value = $(this).val().toLocaleLowerCase('tr-TR');
        $("#usersTable tbody tr").filter(function() {
            var rowText = $(this).text().toLocaleLowerCase('tr-TR');
            $(this).toggle(rowText.indexOf(value) > -1);
        });
    });
});
</script>

<?php include 'partials/footer.php'; ?>