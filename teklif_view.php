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
                                <span class="badge bg-<?php echo $versiyon['revision_number'] > 0 ? 'warning text-dark' : 'primary'; ?> me-3">
                                    <?php echo $versiyon['revision_number'] > 0 ? 'Revizyon ' . $versiyon['revision_number'] : 'Orijinal Teklif'; ?>
                                </span>
                                <?php echo htmlspecialchars($versiyon['proposal_no']); ?>
                                <span class="ms-auto text-muted small">Tarih: <?php echo date('d.m.Y', strtotime($versiyon['proposal_date'])); ?></span>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $versiyon['id']; ?>" class="accordion-collapse collapse <?php echo $index == 0 ? 'show' : ''; ?>" data-bs-parent="#teklifGecmisi">
                            <div class="accordion-body">
                                
                                <!-- BUTON GRUBU -->
                                <div class="mb-3">
                                    <a href="teklif_pdf.php?id=<?php echo $versiyon['id']; ?>" target="_blank" class="btn btn-primary btn-sm">
                                        <i class="fas fa-print me-2"></i>PDF Görüntüle / Yazdır
                                    </a>
                                    <button type="button" class="btn btn-success btn-sm send-mail-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#mailGonderModal"
                                            data-proposal-id="<?php echo $versiyon['id']; ?>"
                                            data-proposal-no="<?php echo htmlspecialchars($versiyon['proposal_no']); ?>"
                                            data-customer-name="<?php echo htmlspecialchars($versiyon['unvan']); ?>"
                                            data-customer-email="<?php echo htmlspecialchars($versiyon['customer_email']); ?>">
                                        <i class="fas fa-paper-plane me-2"></i>Mail Olarak Gönder
                                    </button>
                                </div>
                                
                                <?php if ($versiyon['revision_note']): ?>
                                    <div class="alert alert-info">
                                        <strong>Revizyon Notu (<?php echo htmlspecialchars($versiyon['user_name']); ?>):</strong>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($versiyon['revision_note'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- EKSİK OLAN ÜRÜN TABLOSU -->
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 10%;">Görsel</th>
                                            <th>Ürün Adı</th>
                                            <th class="text-center">Adet</th>
                                            <th class="text-end">Birim Fiyat</th>
                                            <th class="text-center">İskonto (%)</th>
                                            <th class="text-end">Toplam</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($versiyon['items'] as $item): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($item['fotograf_yolu']) && file_exists($item['fotograf_yolu'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['fotograf_yolu']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="img-thumbnail text-center bg-light d-flex justify-content-center align-items-center" style="width: 60px; height: 60px;">
                                                            <i class="fas fa-image text-muted fs-4"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                <td class="text-end"><?php echo number_format($item['unit_price'], 2, ',', '.') . ' ' . $versiyon['currency']; ?></td>
                                                <td class="text-center"><?php echo $item['discount_percent']; ?> %</td>
                                                <td class="text-end fw-bold"><?php echo number_format($item['total_price'], 2, ',', '.') . ' ' . $versiyon['currency']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <!-- EKSİK OLAN TOPLAMLAR BÖLÜMÜ -->
                                <div class="row justify-content-end mt-3">
                                    <div class="col-md-5">
                                        <table class="table">
                                            <tr>
                                                <th class="text-end">Ara Toplam:</th>
                                                <td class="text-end"><?php echo number_format($versiyon['sub_total'], 2, ',', '.') . ' ' . $versiyon['currency']; ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-end">İskonto Toplamı:</th>
                                                <td class="text-end text-danger">(<?php echo number_format($versiyon['total_discount'], 2, ',', '.'); ?>) <?php echo $versiyon['currency']; ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-end">KDV (%<?php echo number_format($versiyon['tax_rate'], 0); ?>):</th>
                                                <td class="text-end"><?php echo number_format($versiyon['tax_amount'], 2, ',', '.') . ' ' . $versiyon['currency']; ?></td>
                                            </tr>
                                            <tr class="fs-5 fw-bold border-top">
                                                <th class="text-end">Genel Toplam:</th>
                                                <td class="text-end"><?php echo number_format($versiyon['grand_total'], 2, ',', '.') . ' ' . $versiyon['currency']; ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Mail Gönderme Modalı (Açılır Pencere) -->
<div class="modal fade" id="mailGonderModal" tabindex="-1" aria-labelledby="mailGonderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mailGonderModalLabel">Teklif Maili Gönder</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="mailGonderForm" action="send_mail.php" method="POST">
        <div class="modal-body">
          <input type="hidden" id="mail_proposal_id" name="proposal_id">
          <div class="mb-3">
              <label for="mail_to" class="form-label">Alıcı E-posta Adresi:</label>
              <input type="email" class="form-control" id="mail_to" name="to_email" required>
          </div>
          <div class="mb-3">
              <label for="mail_subject" class="form-label">Konu:</label>
              <input type="text" class="form-control" id="mail_subject" name="subject" required>
          </div>
          <div class="mb-3">
              <label for="mail_body" class="form-label">Mail İçeriği:</label>
              <textarea class="form-control" id="mail_body" name="body" rows="10" required></textarea>
          </div>
          <div id="mailStatus"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
          <button type="submit" class="btn btn-primary" id="sendMailSubmitBtn">
            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            Gönder
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php 
include 'partials/footer.php'; 
?>
<script>
$(document).ready(function() {
    // Mail Gönder butonuna tıklandığında modalı doldur
    $('.send-mail-btn').on('click', function() {
        var proposalId = $(this).data('proposal-id');
        var proposalNo = $(this).data('proposal-no');
        var customerName = $(this).data('customer-name');
        var customerEmail = $(this).data('customer-email');

        // Modal'daki gizli input'ları ve form alanlarını doldur
        $('#mail_proposal_id').val(proposalId);
        $('#mail_to').val(customerEmail);
        $('#mail_subject').val(proposalNo + ' Numaralı Fiyat Teklifiniz');

        // Hazır mail şablonu oluştur
        var mailBody = `Sayın ${customerName},\n\nFirmanız için hazırlamış olduğumuz ${proposalNo} numaralı fiyat teklifimiz ektedir.\n\nTeklifimizi inceleyip, geri dönüş yapmanızı rica ederiz.\n\nİyi çalışmalar dileriz.`;
        
        // Metin alanını sadece bu şablonla dolduruyoruz. İmza PHP tarafında eklenecek.
        $('#mail_body').val(mailBody);
        
        // Önceki durumları temizle
        $('#mailStatus').html('');
        $('#sendMailSubmitBtn').prop('disabled', false).find('.spinner-border').addClass('d-none');
    });

    // Mail gönderme formunu AJAX ile gönder
    $('#mailGonderForm').on('submit', function(e) {
        e.preventDefault(); // Formun normal gönderimini engelle

        var form = $(this);
        var submitBtn = $('#sendMailSubmitBtn');
        var statusDiv = $('#mailStatus');

        submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');
        statusDiv.html('<div class="alert alert-info">Mail gönderiliyor, lütfen bekleyin...</div>');

        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    statusDiv.html('<div class="alert alert-success">Mail başarıyla gönderildi!</div>');
                    setTimeout(function() {
                        $('#mailGonderModal').modal('hide');
                        // Sayfayı yenilemek yerine, listedeki ikonu anlık değiştirelim
                        // Bu, teklif_listesi.php'deki #mail-icon-ID elemanını hedef alacak.
                        // Ancak bu sayfada olmadığımız için en garantili yol, liste sayfasına dönüldüğünde güncel halini görmektir.
                        // Şimdilik sadece modalı kapatıyoruz.
                    }, 2000); 
                } else {
                    statusDiv.html('<div class="alert alert-danger">Hata: ' + response.message + '</div>');
                }
            },
            error: function() {
                statusDiv.html('<div class="alert alert-danger">Sunucuya bağlanırken bir hata oluştu. Lütfen tekrar deneyin.</div>');
            },
            complete: function() {
                submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');
            }
        });
    });
});
</script>