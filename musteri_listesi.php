<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require 'config/database.php';

$market = $_GET['market'] ?? 'yurt_ici'; 

$search_term = $_GET['search'] ?? '';
$search_type = $_GET['type'] ?? '';

$sql = "
    SELECT c.*, co.country_name, ci.city_name 
    FROM customers c
    LEFT JOIN countries co ON c.country_id = co.id
    LEFT JOIN cities ci ON c.city_id = ci.id
    WHERE 1=1 
";
$params = [];

if ($market === 'yurt_ici') {
    $sql .= " AND c.market_type = 'Yurt İçi'";
} elseif ($market === 'yurt_disi') {
    $sql .= " AND c.market_type = 'Yurt Dışı'";
}

if (!empty($search_term)) {
    $sql .= " AND (c.unvan LIKE ? OR c.yetkili_ismi LIKE ? OR c.email LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

if (!empty($search_type)) {
    $sql .= " AND c.customer_type = ?";
    $params[] = $search_type;
}

$sql .= " ORDER BY c.unvan ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$musteriler = $stmt->fetchAll();

if ($market === 'yurt_ici') {
    $page_title = "Yurt İçi Müşteri Listesi";
    // DEĞİŞİKLİK: Link artık 'musteri_ekle.php'
    $add_link = "musteri_ekle.php"; 
    $add_button_text = "Yeni Yurt İçi Müşteri Ekle";
    $edit_link_prefix = "musteri_duzenle_yurtici.php"; // (Bunu da daha sonra yapacağız)
} else {
    $page_title = "Yurt Dışı Müşteri Listesi";
    $add_link = "musteri_ekle_yurtdisi.php";
    $add_button_text = "Yeni Yurt Dışı Müşteri Ekle";
    $edit_link_prefix = "musteri_duzenle_yurtdisi.php"; // (Bunu da daha sonra yapacağız)
}

include 'partials/header.php';
?>

<div class="main-wrapper">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <!-- ... topbar içeriği ... -->
        </div>
        <div class="page-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?php echo $page_title; ?></h1>
                <?php if ($_SESSION['user_role_id'] == 1): ?>
                  <a href="<?php echo $add_link; ?>" class="btn btn-success"><i class="fas fa-plus me-2"></i><?php echo $add_button_text; ?></a>
                <?php endif; ?>
            </div>

            <div class="btn-group mb-4">
                <a href="musteri_listesi.php?market=yurt_ici" class="btn <?php echo ($market === 'yurt_ici') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Yurt İçi
                </a>
                <a href="musteri_listesi.php?market=yurt_disi" class="btn <?php echo ($market === 'yurt_disi') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Yurt Dışı
                </a>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <input type="hidden" name="market" value="<?php echo htmlspecialchars($market); ?>">
                        <div class="col-md-5">
                            <label for="search" class="form-label">Arama Yap</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Müşteri adı, yetkili, e-posta..." value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="type" class="form-label">Müşteri Tipi</label>
                            <select id="type" name="type" class="form-select">
                                <option value="">Tümü</option>
                                <option value="Bayi" <?php if($search_type == 'Bayi') echo 'selected'; ?>>Bayi</option>
                                <option value="Son Kullanıcı" <?php if($search_type == 'Son Kullanıcı') echo 'selected'; ?>>Son Kullanıcı</option>
                                <option value="Proje Firması" <?php if($search_type == 'Proje Firması') echo 'selected'; ?>>Proje Firması</option>
                                <option value="Diğer" <?php if($search_type == 'Diğer') echo 'selected'; ?>>Diğer</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Filtrele</button>
                            <a href="musteri_listesi.php?market=<?php echo htmlspecialchars($market); ?>" class="btn btn-secondary w-100 mt-2">Temizle</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Ünvan</th>
                            <th>Müşteri Tipi</th>
                            <th>Ülke / Şehir</th>
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
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($musteri['customer_type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($musteri['country_name'] ?? '-'); ?> / <?php echo htmlspecialchars($musteri['city_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($musteri['email']); ?></td>
                                    <td><?php echo htmlspecialchars($musteri['telefon']); ?></td>
                                    <td class="text-center">
                                        <?php if ($_SESSION['user_role_id'] == 1): ?>
                                            <a href="<?php echo $edit_link_prefix; ?>?id=<?php echo $musteri['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Düzenle"><i class="fas fa-edit"></i></a>
                                            <a href="musteri_sil.php?id=<?php echo $musteri['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu müşteriyi silmek istediğinizden emin misiniz?');"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center p-4">Bu kritere uygun müşteri bulunamadı.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>