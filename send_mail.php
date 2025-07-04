<?php
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'lib/PHPMailer/Exception.php';
require_once 'lib/PHPMailer/PHPMailer.php';
require_once 'lib/PHPMailer/SMTP.php';
require_once 'config/database.php';
require_once 'core/pdf_generator.php';

$response = ['success' => false, 'message' => 'Geçersiz istek.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $proposal_id = $_POST['proposal_id'];
    $to_email = $_POST['to_email'];
    $subject = $_POST['subject'];
    $body = nl2br(htmlspecialchars($_POST['body']));

    // --- DEĞİŞİKLİK BURADA BAŞLIYOR ---

    // 1. TÜM ayarları tek seferde çekiyoruz (mail, imza, renk hepsi için)
    $settings_all = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // 2. İmzayı ve Rengi değişkenlere atıyoruz
    $signature = $settings_all['mail_signature_html'] ?? '';
    $theme_color = $settings_all['proposal_theme_color'] ?? '#004a99';

    // 3. PDF'i oluştururken renk bilgisini de fonksiyona gönderiyoruz
    $pdf_content = generate_proposal_pdf($pdo, $proposal_id, $theme_color);

    // --- DEĞİŞİKLİK BURADA BİTİYOR ---

    if (!$pdf_content) {
        echo json_encode(['success' => false, 'message' => 'PDF oluşturulurken bir hata oluştu.']);
        exit;
    }
    
    $stmt_no = $pdo->prepare("SELECT proposal_no FROM proposals WHERE id = ?");
    $stmt_no->execute([$proposal_id]);
    $proposal_no = $stmt_no->fetchColumn();
    $dosya_adi = 'Teklif-' . str_replace('/', '-', $proposal_no) . '.pdf';
    
    // --- Maili Gönder ---
    // Mail ayarlarını $settings_all dizisinden alıyoruz
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $settings_all['mail_host'] ?? '';
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings_all['mail_username'] ?? '';
        $mail->Password   = $settings_all['mail_password'] ?? '';
        $mail->SMTPSecure = ($settings_all['mail_security'] ?? 'tls') == 'none' ? '' : ($settings_all['mail_security'] ?? 'tls');
        $mail->Port       = (int)($settings_all['mail_port'] ?? 587);
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($settings_all['mail_from_address'] ?? '', $settings_all['mail_from_name'] ?? '');
        $mail->addAddress($to_email);
        $mail->addStringAttachment($pdf_content, $dosya_adi);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $full_body = $body . "<br><br><hr>" . $signature;
        $mail->Body    = $full_body;
        $mail->AltBody = strip_tags($full_body);

        $mail->send();

        // Veritabanını güncelle
        $update_stmt = $pdo->prepare("UPDATE proposals SET is_sent_by_mail = 1, mail_sent_at = NOW() WHERE id = ?");
        $update_stmt->execute([$proposal_id]);
        
        $response = ['success' => true, 'message' => 'Mail başarıyla gönderildi!'];

    } catch (Exception $e) {
        $response = ['success' => false, 'message' => "Mail gönderilemedi. Hata: {$mail->ErrorInfo}"];
    }
}

echo json_encode($response);
?>