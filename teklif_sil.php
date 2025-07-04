<?php
session_start();
// Yetki kontrolü
if ($_SESSION['user_role_id'] != 1) { // 1 = Admin rolü ID'si
    // Admin değilse, panele yönlendir ve işlemi durdur.
    header('Location: dashboard.php');
    exit('Bu sayfaya erişim yetkiniz yok.');
}

// Güvenlik: Giriş yapılmamışsa işlemi durdur
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'config/database.php';

// Silinecek ana teklifin ID'si gönderildi mi ve sayısal mı diye kontrol et
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    $main_proposal_id = $_GET['id'];

    $pdo->beginTransaction();
    try {
        // 1. Silinecek tüm tekliflerin ID'lerini bul (ana teklif + tüm revizyonları)
        $stmt_ids = $pdo->prepare("SELECT id FROM proposals WHERE id = ? OR original_proposal_id = ?");
        $stmt_ids->execute([$main_proposal_id, $main_proposal_id]);
        $proposal_ids_to_delete = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);

        // Eğer silinecek ID bulunamazsa bir sorun yoktur, yine de devam et.
        if (!empty($proposal_ids_to_delete)) {

            // Placeholders oluştur (örn: ?,?,?)
            $placeholders = implode(',', array_fill(0, count($proposal_ids_to_delete), '?'));

            // 2. Bulunan tüm teklif ID'lerine ait kalemleri `proposal_items` tablosundan sil
            // DÜZELTME BURADA: SQL sorgusu ve execute parametreleri doğru eşleştirildi.
            $sql_items = "DELETE FROM proposal_items WHERE proposal_id IN ($placeholders)";
            $stmt_delete_items = $pdo->prepare($sql_items);
            $stmt_delete_items->execute($proposal_ids_to_delete);
            
            // 3. Ana teklifi ve tüm revizyonlarını `proposals` tablosundan sil
            $sql_proposals = "DELETE FROM proposals WHERE id IN ($placeholders)";
            $stmt_delete_proposals = $pdo->prepare($sql_proposals);
            $stmt_delete_proposals->execute($proposal_ids_to_delete);
        }

        // Tüm işlemler başarılıysa, transaction'ı onayla
        $pdo->commit();

    } catch (Exception $e) {
        // Bir hata olursa, tüm işlemleri geri al
        $pdo->rollBack();
        // Geliştirme aşamasında hatayı görmek için
        die("HATA: Teklif ve revizyonları silinemedi. " . $e->getMessage());
    }
}

// Her durumda teklif listesine geri dön
header("Location: teklif_listesi.php");
exit();
?>