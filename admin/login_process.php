<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ... rest of your code
session_start();
include('includes/db_connect.php'); // Your PDO connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_role'] = $admin['role'];
        
        // Log Login Activity
        $log = $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, ip_address) VALUES (?, 'Login Success', ?)");
        $log->execute([$admin['id'], $_SERVER['REMOTE_ADDR']]);
        
        header("Location: dashboard.php");
    } else {
        header("Location: index.php?error=invalid_credentials");
    }
}
?>