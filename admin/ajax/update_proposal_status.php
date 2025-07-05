<?php
// Geliştirme tamamlandığında bu 3 satırı silebilirsiniz.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/functions.php';

$response = ['success' => false, 'message' => 'Geçersiz istek.'];

// GET metodu ile gelen verileri kontrol ediyoruz
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['proposal_id'], $_GET['status_id'])) {
    
    $proposal_id = $_GET['proposal_id'];
    $new_status_id = $_GET['status_id'];

    try {
        // Yetki Kontrolü
        if ($_SESSION['user_role_id'] != 1) {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE id = ? AND user_id = ?");
            $check_stmt->execute([$proposal_id, $_SESSION['user_id']]);
            if ($check_stmt->fetchColumn() == 0) {
                throw new Exception('Bu teklifin durumunu değiştirme yetkiniz yok.');
            }
        }
        
        // Teklif Durumunu Güncelle
        $sql = "UPDATE proposals SET status_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_status_id, $proposal_id]);
        
        // === ÖNEMLİ BÖLÜM BURASI ===
        // Başarılı güncelleme sonrası, yeni durumun adını ve rengini veritabanından çekelim.
        
        $new_status_stmt = $pdo->prepare("SELECT status_name, status_color FROM proposal_statuses WHERE id = ?");
        $new_status_stmt->execute([$new_status_id]);
        $new_status_data = $new_status_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$new_status_data) {
            throw new Exception('Yeni durum bilgisi bulunamadı.');
        }
        // === ÖNEMLİ BÖLÜM SONU ===
        
        add_log($pdo, 'TEKLİF DURUMU GÜNCELLENDİ', "Teklif ID: {$proposal_id}, Yeni Durum ID: {$new_status_id}");
        
        // Yanıta yeni renk ve isim bilgilerini ekliyoruz
        $response = [
            'success' => true, 
            'message' => 'Durum başarıyla güncellendi.',
            'new_status_name' => $new_status_data['status_name'],
            'new_status_color' => $new_status_data['status_color']
        ];
        
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
}

echo json_encode($response);