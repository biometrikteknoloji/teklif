<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit('Yetkisiz Erişim.'); }
require 'config/database.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) { header('Location: proforma_listesi.php?status=error'); exit(); }

$pdo->beginTransaction();
try {
    // Önce kalemleri sil
    $stmt_items = $pdo->prepare("DELETE FROM proforma_items WHERE proforma_id = ?");
    $stmt_items->execute([$id]);

    // Sonra ana proformayı sil
    $stmt_proforma = $pdo->prepare("DELETE FROM proformas WHERE id = ?");
    $stmt_proforma->execute([$id]);

    $pdo->commit();
    header('Location: proforma_listesi.php?status=deleted');
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    die("HATA: Proforma silinemedi. " . $e->getMessage());
}
?>