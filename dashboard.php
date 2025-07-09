<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require 'config/database.php';

// ----- DASHBOARD VERİLERİNİ ÇEKME -----

// 1. Üst Kartlar için Veriler
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');
$year_start = date('Y-01-01');

$proposals_this_week = $pdo->query("SELECT COUNT(*) FROM proposals WHERE proposal_date >= '$week_start'")->fetchColumn();
$proposals_this_month = $pdo->query("SELECT COUNT(*) FROM proposals WHERE proposal_date >= '$month_start'")->fetchColumn();
$proposals_this_year = $pdo->query("SELECT COUNT(*) FROM proposals WHERE proposal_date >= '$year_start'")->fetchColumn();


// 2. Grafik için Veriler (Düzeltilmiş Yöntem)
$stmt_statuses = $pdo->query("SELECT id, status_name FROM proposal_statuses ORDER BY id ASC");
$all_statuses = $stmt_statuses->fetchAll(PDO::FETCH_KEY_PAIR); // id => status_name

$stmt_counts = $pdo->query("SELECT status_id, COUNT(id) as count FROM proposals GROUP BY status_id");
$proposal_counts = $stmt_counts->fetchAll(PDO::FETCH_KEY_PAIR); // status_id => count

$chart_labels = [];
$chart_values = [];
foreach ($all_statuses as $id => $name) {
    $chart_labels[] = $name;
    $chart_values[] = $proposal_counts[$id] ?? 0; 
}


// 3. Alt Bölümler için Veriler
$stmt_proposals = $pdo->query("SELECT p.id, p.proposal_no, p.grand_total, p.currency, c.unvan FROM proposals p JOIN customers c ON p.customer_id = c.id WHERE p.original_proposal_id IS NULL ORDER BY p.created_at DESC LIMIT 5");
$son_teklifler = $stmt_proposals->fetchAll();
$stmt_logs = $pdo->query("SELECT a.*, u.full_name as user_name FROM activity_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
$son_aktiviteler = $stmt_logs->fetchAll();

include 'partials/header.php';
?>
<div class="main-wrapper">
    
    <?php include 'partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <button class="btn btn-light d-lg-none mobile-menu-toggle" type="button"><i class="fas fa-bars"></i></button>
            <div class="user-info ms-auto">
                Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Misafir'); ?></strong>!
            </div>
        </div>

        <div class="page-content">
            <h1 class="mb-4">TEKLİF TAKİP SİSTEMİ</h1>
            
            <div class="row">
                <div class="col-12 col-md-4 mb-4">
                    <div class="card text-white h-100" style="background-color: #6aaa64;">
                        <div class="card-body text-center p-3">
                            <h5 class="card-title small">BU YIL GÖNDERİLEN</h5>
                            <p class="display-5 fw-bold my-1"><?php echo $proposals_this_year; ?></p>
                            <p class="card-text">TOPLAM TEKLİF</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-4">
                    <div class="card text-white h-100" style="background-color: #c9b458;">
                        <div class="card-body text-center p-3">
                            <h5 class="card-title small">BU AY GÖNDERİLEN</h5>
                            <p class="display-5 fw-bold my-1"><?php echo $proposals_this_month; ?></p>
                            <p class="card-text">TOPLAM TEKLİF</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-4">
                    <div class="card text-white h-100" style="background-color: #4c8fc3;">
                        <div class="card-body text-center p-3">
                            <h5 class="card-title small">BU HAFTA GÖNDERİLEN</h5>
                            <p class="display-5 fw-bold my-1"><?php echo $proposals_this_week; ?></p>
                            <p class="card-text">TOPLAM TEKLİF</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header text-center">
                    <h5 class="card-title mb-0">Teklif Durumları</h5>
                </div>
                <div class="card-body">
                    <canvas id="teklifDurumGrafigi" style="width:100%; height:300px;"></canvas>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header">GÖNDERİLEN SON TEKLİFLER</div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php if (!empty($son_teklifler)): ?>
                                    <?php foreach($son_teklifler as $teklif): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="teklif_view.php?id=<?php echo $teklif['id']; ?>"><?php echo htmlspecialchars($teklif['proposal_no']); ?></a>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($teklif['unvan']); ?></small>
                                        </div>
                                        <span class="badge bg-primary rounded-pill"><?php echo number_format($teklif['grand_total'], 2, ',', '.') . ' ' . $teklif['currency']; ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center text-muted">Gösterilecek teklif yok.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header">SON YAPILAN İŞLEMLER</div>
                         <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php if (!empty($son_aktiviteler)): ?>
                                    <?php foreach ($son_aktiviteler as $log): ?>
                                    <li class="list-group-item">
                                        <p class="mb-1"><strong><?php echo htmlspecialchars($log['action']); ?></strong></p>
                                        <p class="mb-1 small text-muted"><?php echo htmlspecialchars($log['details']); ?></p>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">Yapan: <?php echo htmlspecialchars($log['user_name'] ?? 'Sistem'); ?></small>
                                            <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></small>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center text-muted">Gösterilecek aktivite yok.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('teklifDurumGrafigi');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Teklif Sayısı',
                data: <?php echo json_encode($chart_values); ?>,
                backgroundColor: 'rgba(76, 143, 195, 0.6)',
                borderColor: 'rgba(76, 143, 195, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 2 } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>