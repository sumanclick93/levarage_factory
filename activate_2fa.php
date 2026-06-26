<?php
require_once('includes/db_connect.php');
require_once('includes/GoogleAuthenticator.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $code = $_POST['verify_code'];

    $stmt = $pdo->prepare("SELECT google_2fa_secret FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $secret = $stmt->fetchColumn();

    $ga = new PHPGangsta_GoogleAuthenticator();
    
    // Verify the code before enabling
    if ($ga->verifyCode($secret, $code, 2)) {
        $update = $pdo->prepare("UPDATE users SET is_2fa_enabled = 1 WHERE id = ?");
        $update->execute([$user_id]);
        header("Location: dashboard.php?status=2fa_enabled");
    } else {
        header("Location: setup_2fa.php?error=invalid_code");
    }
}