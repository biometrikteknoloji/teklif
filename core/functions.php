<?php
if (!function_exists('add_log')) {
    function add_log($pdo, $action, $details = null) {
        $user_id = $_SESSION['user_id'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'];

        $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $action, $details, $ip_address]);
    }
}
?>