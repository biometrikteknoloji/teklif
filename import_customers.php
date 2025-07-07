<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role_id'] != 1) {
    header('Location: dashboard.php');
    exit('Yetkisiz Erişim.');
}

// === DEĞİŞİKLİK BURADA: DOĞRU YOL TANIMLAMASI ===
// Bu dosya ana dizinde olduğu için, '/..' kısmını kaldırıyoruz.
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/config/database.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['excelFile']) && $_FILES['excelFile']['error'] == UPLOAD_ERR_OK) {
    
    $inputFileName = $_FILES['excelFile']['tmp_name'];
    
    try {
        $spreadsheet = IOFactory::load($inputFileName);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        unset($sheetData[1]); // Başlık satırını atla

        $sql = "INSERT INTO customers (unvan, adres, telefon, vergi_dairesi, vergi_no, email, cep_telefonu, yetkili_ismi) 
                VALUES (:unvan, :adres, :telefon, :vergi_dairesi, :vergi_no, :email, :cep_telefonu, :yetkili_ismi)";
        $stmt = $pdo->prepare($sql);

        $pdo->beginTransaction();
        $importedCount = 0;

        foreach ($sheetData as $row) {
            $unvan = trim($row['A']);
            if (empty($unvan)) { continue; }

            $vergi_no = trim($row['E']);
            if (!empty($vergi_no)) {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE vergi_no = ?");
                $checkStmt->execute([$vergi_no]);
                if ($checkStmt->fetchColumn() > 0) {
                    continue; // Zaten var, atla
                }
            }
            
            $stmt->execute([
                ':unvan' => $unvan,
                ':adres' => trim($row['B']),
                ':telefon' => trim($row['C']),
                ':vergi_dairesi' => trim($row['D']),
                ':vergi_no' => $vergi_no,
                ':email' => trim($row['F']),
                ':cep_telefonu' => trim($row['G']),
                ':yetkili_ismi' => trim($row['H'])
            ]);
            $importedCount++;
        }
        
        $pdo->commit();

        header("Location: musteri_listesi.php?import_status=success&count=$importedCount");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $message = urlencode("Hata: " . $e->getMessage());
        header("Location: musteri_listesi.php?import_status=error&message=$message");
        exit();
    }
} else {
    $message = urlencode("Dosya yükleme hatası.");
    header("Location: musteri_listesi.php?import_status=error&message=$message");
    exit();
}
?>