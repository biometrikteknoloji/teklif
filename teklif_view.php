<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require 'config/database.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header('Location: teklif_listesi.php');
    exit();
}

$sql = "SELECT p.*, c.unvan, c.email as customer_email, u.full_name as user_name FROM proposals p JOIN customers c ON p.customer_id = c.id JOIN users u ON p.user_id = u.id WHERE p.id = ? OR p.original_proposal_id = ? ORDER BY p.revision_number DESC, p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id, $id]);
$teklif_versiyonlari = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$teklif_versiyonlari) {
    header('Location: teklif_listesi.php');
    exit();
}

foreach ($teklif_versiyonlari as $key => $versiyon) {
    $stmt_items = $pdo->prepare("SELECT pi.*, pr.fotograf_yolu FROM proposal_items pi LEFT JOIN products pr ON pi.product_id = pr.id WHERE pi.proposal_id = ? ORDER BY pi.id ASC");
    $stmt_items->execute([$versiyon['id']]);
    $teklif_versiyonlari[$key]['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
}

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
                <div>
                    <h1>Teklif Detayları</h1>
                    <p class="lead mb-0">Teklif No: <strong><?php echo htmlspecialchars(preg_replace('/-R\d+$/', '', $teklif_versiyonlari[0]['proposal_no'])); ?></strong> (ve revizyonları)</p>
                    <p>Müşteri: <strong><?php echo htmlspecialchars($teklif_versiyonlari[0]['unvan']); ?></strong></p>
                </div>
                <a href="teklif_listesi.php" class="btn btn-secondary">Listeye Dön</a>
            </div>

            <div class="accordion" id="teklifGecmisi">
                <?php foreach ($teklif_versiyonlari as $index => $versiyon): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $versiyon['id']; ?>">
                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $versiyon['id']; ?>" aria-expanded="<?php echo $index == 0 ? 'true' : 'false'; ?>">
                                <span class="badge <?php echo $versiyon['revision_number'] > 0 ? 'bg-warning text-dark' : 'bg-primary'; ?> me-3">
                                    <?php echo $versiyon['revision_number'] > 0 ? 'Revizyon ' . $versiyon['revision_number'] : 'Orijinal Teklif'; ?>
                                </span>
                                <?php echo htmlspecialchars($versiyon['proposal_no']); ?>
                                <span class="ms-auto text-muted small">Tarih: <?php echo date('d.m.Y', strtotime($versiyon['proposal_date'])); ?></span>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $versiyon['id']; ?>" class="accordion-collapse collapse <?php echo $index == 0 ? 'show' : ''; ?>" data-bs-parent="#teklifGecmisi">
                            <div class="accordion-body">
                                
                                <!-- === İÇERİK BURAYA GERİ EKLENDİ === -->
                                <div class="mb-3">
                                    <a href="teklif_pdf.php?id=<?php echo $versiyon['id']; ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-print me-2"></i>PDF Görüntüle / Yazdır</a>
                                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#mailGonderModal" data-proposal-id="<?php echo $versiyon['id']; ?>" data-proposal-no="<?php echo htmlspecialchars($versiyon['proposal_no']); ?>" data-customer-name="<?php echo htmlspecialchars($versiyon['unvan']); ?>" data-customer-email="<?php echo htmlspecialchars($versiyon['customer_email']); ?>"><i class="fas fa-paper-plane me-2"></i>Mail Olarak Gönder</button>
                                </div>
                                
                                <?php if ($versiyon['revision_note']): ?>
                                    <div class="alert alert-info">
                                        <strong>Revizyon Notu (<?php echo htmlspecialchars($versiyon['user_name']); ?>):</strong>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($versiyon['revision_note'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 10%;">Görsel</th><th>Ürün Adı</th><th class="text-center">Adet</th><th class="text-end">Birim Fiyat</th><th class="text-center">İskonto (%)</th><th class="text-end">Toplam</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($versiyon['items'] as $item): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($item['fotograf_yolu']) && file_exists($item['fotograf_yolu'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['fotograf_yolu']); ?>" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="img-thumbnail text-center bg-light d-flex justify-content-center align-items-center" style="width: 60px; height: 60px;"><i class="fas fa-image text-muted fs-4"></i></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                <td class="text-end"><?php echo number_format($item['unit_price'], 2, ',', '.'); ?> <?php echo $versiyon['currency']; ?></td>
                                                <td class="text-center"><?php echo $item['discount_percent']; ?> %</td>
                                                <td class="text-end fw-bold"><?php echo number_format($item['total_price'], 2, ',', '.'); ?> <?php echo $versiyon['currency']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <div class="row justify-content-end mt-4">
                                    <div class="col-md-6 col-lg-5">
                                        <ul class="list-group">
                                            <?php /* ... Toplamlar bölümü burada ... */ ?>
                                        </ul>
                                    </div>
                                </div>
                                <!-- === İÇERİK SONU === -->

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- === SAYFA SONUNA EKLENECEK KOD === -->

<!-- 1. Gerekli Temel Kütüphaneler (Her ihtimale karşı burada da çağırıyoruz) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>

<!-- 2. Modal Penceresinin HTML Kodu -->
<div class="modal fade" id="mailGonderModal" tabindex="-1" aria-labelledby="mailGonderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mailGonderModalLabel">Teklif Maili Gönder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendMailForm">
                <div class="modal-body">
                    <input type="hidden" id="mail_proposal_id" name="proposal_id">
                    <div class="mb-3">
                        <label for="to_email" class="form-label">Alıcı E-posta Adresi:</label>
                        <input type="email" class="form-control" id="to_email" name="to_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Konu:</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="mail_body" class="form-label">Mail İçeriği:</label>
                        <textarea class="form-control" id="mail_body" name="body" rows="6"></textarea>
                    </div>
                    <div id="mail-response-alert" class="alert mt-3" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" class="btn btn-primary">Gönder</button>
                </div>
            </form>
        </div>
		<!-- === SAYFA SONUNA EKLENECEK KOD === -->

<!-- 1. Gerekli Temel Kütüphaneler (Her ihtimale karşı burada da çağırıyoruz) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>


<!-- 2. Modal Penceresinin HTML Kodu -->
<div class="modal fade" id="mailGonderModal" tabindex="-1" aria-labelledby="mailGonderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mailGonderModalLabel">Teklif Maili Gönder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendMailForm">
                <div class="modal-body">
                    <input type="hidden" id="mail_proposal_id" name="proposal_id">
                    <div class="mb-3">
                        <label for="to_email" class="form-label">Alıcı E-posta Adresi:</label>
                        <input type="email" class="form-control" id="to_email" name="to_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Konu:</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="mail_body" class="form-label">Mail İçeriği:</label>
                        <textarea class="form-control" id="mail_body" name="body" rows="6"></textarea>
                    </div>
                    <div id="mail-response-alert" class="alert mt-3" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" class="btn btn-primary">Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- 3. Modal'ı Çalıştıracak JavaScript Kodu -->
<script>
$(document).ready(function() {
    $('#mailGonderModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var proposalId = button.data('proposal-id');
        var proposalNo = button.data('proposal-no');
        var customerEmail = button.data('customer-email');
        var customerName = button.data('customer-name');

        var modal = $(this);
        modal.find('#mail_proposal_id').val(proposalId);
        modal.find('#to_email').val(customerEmail);
        modal.find('#subject').val(proposalNo + ' Numaralı Fiyat Teklifiniz');
        modal.find('#mail_body').val('Sayın ' + customerName + ',\n\nİlginize teşekkür eder, teklifimizi ekte bilgilerinize sunarız.\n\nİyi çalışmalar dileriz.');
    });

    $('#sendMailForm').on('submit', function(e) {
        e.preventDefault(); 
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.html();
        var alertDiv = $('#mail-response-alert');

        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Gönderiliyor...');
        alertDiv.hide();

        $.ajax({
            type: 'POST',
            url: 'send_mail.php', 
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alertDiv.removeClass('alert-danger').addClass('alert-success').text(response.message).show();
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    alertDiv.removeClass('alert-success').addClass('alert-danger').text(response.message).show();
                    submitButton.prop('disabled', false).html(originalButtonText);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Hatası: ", jqXHR.responseText);
                alertDiv.removeClass('alert-success').addClass('alert-danger').text('Sunucuya bağlanırken bir hata oluştu.').show();
                submitButton.prop('disabled', false).html(originalButtonText);
            }
        });
    });
});
</script>
    </div>
	
	
</div>

<!-- 3. Modal'ı Çalıştıracak JavaScript Kodu -->
<script>
$(document).ready(function() {
    $('#mailGonderModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var proposalId = button.data('proposal-id');
        var proposalNo = button.data('proposal-no');
        var customerEmail = button.data('customer-email');
        var customerName = button.data('customer-name');

        var modal = $(this);
        modal.find('#mail_proposal_id').val(proposalId);
        modal.find('#to_email').val(customerEmail);
        modal.find('#subject').val(proposalNo + ' Numaralı Fiyat Teklifiniz');
        modal.find('#mail_body').val('Sayın ' + customerName + ',\n\nİlginize teşekkür eder, teklifimizi ekte bilgilerinize sunarız.\n\nİyi çalışmalar dileriz.');
    });

    $('#sendMailForm').on('submit', function(e) {
        e.preventDefault(); 
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.html();
        var alertDiv = $('#mail-response-alert');

        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Gönderiliyor...');
        alertDiv.hide();

        $.ajax({
            type: 'POST',
            url: 'send_mail.php', 
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alertDiv.removeClass('alert-danger').addClass('alert-success').text(response.message).show();
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    alertDiv.removeClass('alert-success').addClass('alert-danger').text(response.message).show();
                    submitButton.prop('disabled', false).html(originalButtonText);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Hatası: ", jqXHR.responseText);
                alertDiv.removeClass('alert-success').addClass('alert-danger').text('Sunucuya bağlanırken bir hata oluştu.').show();
                submitButton.prop('disabled', false).html(originalButtonText);
            }
        });
    });
	<!-- === SAYFA SONUNA EKLENECEK KOD === -->

<!-- 1. Gerekli Temel Kütüphaneler (Her ihtimale karşı burada da çağırıyoruz) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>


<!-- 2. Modal Penceresinin HTML Kodu -->
<div class="modal fade" id="mailGonderModal" tabindex="-1" aria-labelledby="mailGonderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mailGonderModalLabel">Teklif Maili Gönder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendMailForm">
                <div class="modal-body">
                    <input type="hidden" id="mail_proposal_id" name="proposal_id">
                    <div class="mb-3">
                        <label for="to_email" class="form-label">Alıcı E-posta Adresi:</label>
                        <input type="email" class="form-control" id="to_email" name="to_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Konu:</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="mail_body" class="form-label">Mail İçeriği:</label>
                        <textarea class="form-control" id="mail_body" name="body" rows="6"></textarea>
                    </div>
                    <div id="mail-response-alert" class="alert mt-3" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" class="btn btn-primary">Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- 3. Modal'ı Çalıştıracak JavaScript Kodu -->
<script>
$(document).ready(function() {
    // Bu kodun sadece bu sayfada çalışmasını garanti altına alıyoruz.
    
    // Modal açıldığında içini doldur
    $('#mailGonderModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var proposalId = button.data('proposal-id');
        var proposalNo = button.data('proposal-no');
        var customerEmail = button.data('customer-email');
        var customerName = button.data('customer-name');

        var modal = $(this);
        modal.find('#mail_proposal_id').val(proposalId);
        modal.find('#to_email').val(customerEmail);
        modal.find('#subject').val(proposalNo + ' Numaralı Fiyat Teklifiniz');
        modal.find('#mail_body').val('Sayın ' + customerName + ',\n\nİlginize teşekkür eder, teklifimizi ekte bilgilerinize sunarız.\n\nİyi çalışmalar dileriz.');
    });

    // Form gönderildiğinde AJAX isteğini yap
    $('#sendMailForm').on('submit', function(e) {
        e.preventDefault(); 
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.html();
        var alertDiv = $('#mail-response-alert');

        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Gönderiliyor...');
        alertDiv.hide();

        $.ajax({
            type: 'POST',
            url: 'send_mail.php', 
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alertDiv.removeClass('alert-danger').addClass('alert-success').text(response.message).show();
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    alertDiv.removeClass('alert-success').addClass('alert-danger').text(response.message).show();
                    submitButton.prop('disabled', false).html(originalButtonText);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Hatası: ", jqXHR.responseText);
                // "Response" sekmesinde hatayı daha net görmek için JSON.parse deneyelim
                var errorMsg = 'Sunucuya bağlanırken bir hata oluştu.';
                try {
                    var err = JSON.parse(jqXHR.responseText);
                    errorMsg = err.message || errorMsg;
                } catch(e) {
                    // Eğer parse edilemezse, ham metni göster
                    if(jqXHR.responseText) {
                       errorMsg = jqXHR.responseText;
                    }
                }
                alertDiv.removeClass('alert-success').addClass('alert-danger').text(errorMsg).show();
                submitButton.prop('disabled', false).html(originalButtonText);
            }
        });
    });
});
</script>
});
</script>

<?php 
// Footer en sonda ve sadece 1 kere çağrılıyor
include 'partials/footer.php'; 
?>