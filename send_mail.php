<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'lib/PHPMailer/Exception.php';
require_once 'lib/PHPMailer/PHPMailer.php';
require_once 'lib/PHPMailer/SMTP.php';
require_once 'config/database.php';

$response = ['success' => false, 'message' => 'Geçersiz istek.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $proposal_id = $_POST['proposal_id'];
    $to_email = $_POST['to_email'];
    $subject = $_POST['subject'];
    $body = nl2br(htmlspecialchars($_POST['body']));

    // --- PDF'İ GÜVENİLİR KAYNAKTAN OLUŞTURMA ---
    ob_start();
    // generate_pdf_for_mail.php'nin içindeki $_GET['id']'yi dolduruyoruz
    $_GET['id'] = $proposal_id; 
    // Bu dosya, teklif_pdf.php'nin kopyası olduğu için doğru çıktıyı üretecek ve return ile bize verecek.
    $pdf_content = require 'generate_pdf_for_mail.php';
    ob_end_clean();
    
    if (!$pdf_content || empty($pdf_content)) {
        echo json_encode(['success' => false, 'message' => 'PDF içeriği oluşturulamadı.']);
        exit;
    }
    
    // --- GERİ KALAN KODLAR ---
    $stmt_no = $pdo->prepare("SELECT proposal_no FROM proposals WHERE id = ?");
    $stmt_no->execute([$proposal_id]);
    $proposal_no = $stmt_no->fetchColumn();
    $dosya_adi = 'Teklif-' . str_replace('/', '-', $proposal_no) . '.pdf';
    
    // Hem mail hem de imza için tüm ayarları çekiyoruz
    $settings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $signature = $settings['mail_signature_html'] ?? '';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $settings['mail_host'] ?? '';
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['mail_username'] ?? '';
        $mail->Password   = $settings['mail_password'] ?? '';
        $mail->SMTPSecure = ($settings['mail_security'] ?? 'tls') == 'none' ? '' : ($settings['mail_security'] ?? 'tls');
        $mail->Port       = (int)($settings['mail_port'] ?? 587);
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($settings['mail_from_address'] ?? '', $settings['mail_from_name'] ?? '');
        $mail->addAddress($to_email);
        $mail->addStringAttachment($pdf_content, $dosya_adi);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $full_body = $body . "<br><br><hr>" . $signature;
        $mail->Body    = $full_body;
        $mail->AltBody = strip_tags($full_body);

        $mail->send();
        
        $update_stmt = $pdo->prepare("UPDATE proposals SET is_sent_by_mail = 1, mail_sent_at = NOW() WHERE id = ?");
        $update_stmt->execute([$proposal_id]);
        
        $response = ['success' => true, 'message' => 'Mail başarıyla gönderildi!'];
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => "Mail gönderilemedi: {$mail->ErrorInfo}"];
    }
}
echo json_encode($response);
?>