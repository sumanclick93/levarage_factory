<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the connection is available for the audit log
if (!isset($pdo)) {
    require_once(__DIR__ . '/../../db_connect.php');
}

// Check if the admin is logged in
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header("Location: index.php"); // Redirect to admin login
    exit();
}

// Function to log admin actions (Audit Trail)
function logAdminAction($pdo, $action, $target_user = null) {
    $admin_id = $_SESSION['admin_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, target_user_id, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$admin_id, $action, $target_user, $ip]);
}
?>