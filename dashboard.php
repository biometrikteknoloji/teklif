<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'config/database.php';

// ----- DASHBOARD VERİLERİNİ ÇEKME -----

// 1. İstatistik Kartları için Veriler
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$approved_proposals = $pdo->query("SELECT COUNT(*) FROM proposals WHERE status_id = 5")->fetchColumn();
$pending_proposals = $pdo->query("SELECT COUNT(*) FROM proposals WHERE status_id IN (1, 2)")->fetchColumn();

// 2. Son 5 Teklif
$stmt_proposals = $pdo->query("
    SELECT p.id, p.proposal_no, p.grand_total, p.currency, c.unvan, ps.status_name, ps.id as status_id
    FROM proposals p
    JOIN customers c ON p.customer_id = c.id
    JOIN proposal_statuses ps ON p.status_id = ps.id
    WHERE p.original_proposal_id IS NULL
    ORDER BY p.created_at DESC
    LIMIT 5
");
$son_teklifler = $stmt_proposals->fetchAll();

// 3. Son 5 Aktivite (SÜTUN ADI DÜZELTİLDİ)
$stmt_logs = $pdo->query("
    SELECT a.*, u.full_name as user_name 
    FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 5
");
$son_aktiviteler = $stmt_logs->fetchAll();


include 'partials/header.php';
?>

<div class="main-wrapper">
    
    <?php include 'partials/sidebar.php'; ?>

    <div class="main-content">
      <div class="topbar">
    <!-- === YENİ HAMBURGER BUTONU === -->
    <button class="mobile-menu-toggle d-lg-none" type="button">
        <i class="fas fa-bars"></i>
    </button>
    <!-- === BUTON SONU === -->
    <div class="user-info ms-auto"> <!-- ms-auto ile sağa yasladık -->
        Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!
    </div>
</div>

        <div class="page-content">
            <h1>Dashboard</h1>
            <p class="lead mb-4">Sisteme genel bakış ve son aktiviteler.</p>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary shadow-sm">
                        <div class="card-body"><div class="d-flex justify-content-between align-items-center"><div><h5 class="card-title fs-2"><?php echo $total_customers; ?></h5><p class="card-text">Toplam Müşteri</p></div><i class="fas fa-users fa-3x opacity-50"></i></div></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success shadow-sm">
                         <div class="card-body"><div class="d-flex justify-content-between align-items-center"><div><h5 class="card-title fs-2"><?php echo $approved_proposals; ?></h5><p class="card-text">Onaylanan Teklifler</p></div><i class="fas fa-check-circle fa-3x opacity-50"></i></div></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-warning shadow-sm">
                        <div class="card-body"><div class="d-flex justify-content-between align-items-center"><div><h5 class="card-title fs-2"><?php echo $pending_proposals; ?></h5><p class="card-text">Bekleyen Teklifler</p></div><i class="fas fa-hourglass-half fa-3x opacity-50"></i></div></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-7">
                    <h4>Son Teklifler</h4>
                    <table class="table table-hover">
                        <thead><tr><th>Teklif No</th><th>Müşteri</th><th>Tutar</th><th>Durum</th></tr></thead>
                        <tbody>
                            <?php if (count($son_teklifler) > 0): ?>
                                <?php foreach ($son_teklifler as $teklif): ?>
                                    <tr>
                                        <td><a href="teklif_view.php?id=<?php echo $teklif['id']; ?>"><?php echo htmlspecialchars($teklif['proposal_no']); ?></a></td>
                                        <td><?php echo htmlspecialchars($teklif['unvan']); ?></td>
                                        <td><?php echo number_format($teklif['grand_total'], 2, ',', '.') . ' ' . $teklif['currency']; ?></td>
                                        <td>
                                            <?php 
                                                $status_class = 'bg-secondary';
                                                if($teklif['status_id'] == 5) $status_class = 'bg-success';
                                                if($teklif['status_id'] == 1 || $teklif['status_id'] == 2) $status_class = 'bg-warning text-dark';
                                                if($teklif['status_id'] == 3 || $teklif['status_id'] == 4) $status_class = 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($teklif['status_name']); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center">Henüz teklif yok.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-5">
                    <h4>Son Aktiviteler</h4>
                    <ul class="list-group">
                        <?php if (count($son_aktiviteler) > 0): ?>
                            <?php foreach ($son_aktiviteler as $log): ?>
                                <li class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($log['action']); ?></h6>
                                        <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($log['details']); ?></p>
                                    <small class="text-muted">Yapan: <?php echo htmlspecialchars($log['user_name'] ?? 'Sistem'); ?></small>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <li class="list-group-item text-center">Henüz bir aktivite kaydedilmedi.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'partials/footer.php'; 
?>