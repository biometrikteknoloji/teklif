<?php
// Gerekli kütüphaneleri dahil et
require_once dirname(__DIR__) . '/lib/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if (!function_exists('generate_proposal_pdf')) {
    /**
     * Belirtilen ID'ye sahip teklif için PDF içeriğini oluşturur ve string olarak döndürür.
     * @param PDO $pdo Veritabanı bağlantı objesi
     * @param int $proposal_id Oluşturulacak teklifin ID'si
     * @return string|null PDF içeriği veya hata durumunda null
     */
    function generate_proposal_pdf($pdo, $proposal_id) {
        
        // Projenin kök dizinini tanımla
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__));
        }

        // Gerekli verileri veritabanından çek
        $sql = "SELECT p.*, c.*, u.full_name as user_name FROM proposals p JOIN customers c ON p.customer_id = c.id JOIN users u ON p.user_id = u.id WHERE p.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$proposal_id]);
        $teklif = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$teklif) { return null; }

        $stmt_items = $pdo->prepare("SELECT pi.*, pr.fotograf_yolu FROM proposal_items pi LEFT JOIN products pr ON pi.product_id = pr.id WHERE pi.proposal_id = ? ORDER BY pi.id ASC");
        $stmt_items->execute([$proposal_id]);
        $teklif_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // HTML şablonunu oluşturmak için çıktı tamponlamasını başlat
        ob_start();
        // PDF HTML şablonunu ayrı bir dosyadan çağırıyoruz
        include PROJECT_ROOT . '/templates/pdf_template.php';
        $html = ob_get_clean();

        // DomPDF'i yapılandır
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', PROJECT_ROOT); 
        $options->set('defaultFont', 'dejavu sans');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // PDF içeriğini bir string olarak döndür
        return $dompdf->output();
    }
}
?>