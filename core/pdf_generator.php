<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) { exit('Bu işlemi yapmak için giriş yapmalısınız.'); }

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__FILE__) . '/..');
}

require_once PROJECT_ROOT . '/lib/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// === FONKSİYON TANIMI GÜNCELLENDİ ===
// Artık 3. parametre olarak $theme_color'ı da alıyor.
function generate_proposal_pdf($pdo, $proposal_id, $theme_color = '#004a99') {
    
    // Veritabanı Sorguları
    $sql_proposal = "SELECT p.*, c.*, u.full_name as user_name FROM proposals p JOIN customers c ON p.customer_id = c.id JOIN users u ON p.user_id = u.id WHERE p.id = ?";
    $stmt = $pdo->prepare($sql_proposal);
    $stmt->execute([$proposal_id]);
    $teklif = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$teklif) { return null; }

    $stmt_items = $pdo->prepare("SELECT pi.*, pr.fotograf_yolu, pr.urun_aciklamasi FROM proposal_items pi LEFT JOIN products pr ON pi.product_id = pr.id WHERE pi.proposal_id = ? ORDER BY pi.id ASC");
    $stmt_items->execute([$proposal_id]);
    $teklif_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Ayarları SADECE pdf_template.php'nin ihtiyacı olanlar için çekiyoruz.
    // Renk zaten parametre olarak geldiği için tekrar çekmeye gerek yok.
    $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    ob_start();
    
    // pdf_template.php dosyası, bu fonksiyondan gelen $settings ve $theme_color değişkenlerini kullanacak.
    include 'pdf_template.php'; 
    
    $html = ob_get_clean();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('chroot', PROJECT_ROOT); 
    $options->set('defaultFont', 'dejavu sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
}
?>