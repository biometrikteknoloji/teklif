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
    $signature = $_POST['signature'] ?? '';

    // --- PDF'i merkezi fonksiyondan oluştur ---
    $pdf_content = generate_proposal_pdf($pdo, $proposal_id);
    if (!$pdf_content) {
        echo json_encode(['success' => false, 'message' => 'PDF oluşturulurken bir hata oluştu.']);
        exit;
    }
    
    $stmt_no = $pdo->prepare("SELECT proposal_no FROM proposals WHERE id = ?");
    $stmt_no->execute([$proposal_id]);
    $proposal_no = $stmt_no->fetchColumn();
    $dosya_adi = 'Teklif-' . str_replace('/', '-', $proposal_no) . '.pdf';
    
    // --- Maili Gönder ---
    $settings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'mail_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $mail = new PHPMailer(true);
    try {
        // ... (Tüm mail ayarları...)
        $mail->isSMTP();
        $mail->Host       = $settings['mail_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['mail_username'];
        $mail->Password   = $settings['mail_password'];
        $mail->SMTPSecure = $settings['mail_security'] == 'none' ? '' : $settings['mail_security'];
        $mail->Port       = (int)$settings['mail_port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($settings['mail_from_address'], $settings['mail_from_name']);
        $mail->addAddress($to_email);
        $mail->addStringAttachment($pdf_content, $dosya_adi);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $full_body = $body . "<br><br><hr>" . $signature;
        $mail->Body    = $full_body;
        $mail->AltBody = strip_tags($full_body);

        $mail->send();

        // ---- DÜZELTME BURADA ----
        // Mail başarıyla gönderildikten hemen sonra veritabanını güncelle.
        $update_stmt = $pdo->prepare("UPDATE proposals SET is_sent_by_mail = 1, mail_sent_at = NOW() WHERE id = ?");
        $update_stmt->execute([$proposal_id]);
        
        $response = ['success' => true, 'message' => 'Mail başarıyla gönderildi!'];

    } catch (Exception $e) {
        $response = ['success' => false, 'message' => "Mail gönderilemedi. Hata: {$mail->ErrorInfo}"];
    }
}

echo json_encode($response);