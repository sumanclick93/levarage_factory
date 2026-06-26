<?php
require_once('includes/db_connect.php');
require_once('includes/GoogleAuthenticator.php'); 
session_start();

// If no one is trying to log in, send them back to login
if (!isset($_SESSION['temp_2fa_user_id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['otp_code'];
    $user_id = $_SESSION['temp_2fa_user_id'];

    $stmt = $pdo->prepare("SELECT google_2fa_secret, username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $ga = new PHPGangsta_GoogleAuthenticator();
    // Verify the code against the secret stored in the database
    $checkResult = $ga->verifyCode($user['google_2fa_secret'], $code, 2); 

    if ($checkResult) {
        // SUCCESS: Log them in fully
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $user['username'];
        unset($_SESSION['temp_2fa_user_id']);
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid 6-digit code. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2FA Verification - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100 p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full border border-gray-200">
        <h2 class="text-2xl font-black text-center uppercase tracking-tighter mb-6">Security <span class="text-[#00A6FB]">Gate</span></h2>
        <form method="POST" class="space-y-6">
            <?php if($error): ?><div class="text-red-600 text-center font-bold text-xs"><?php echo $error; ?></div><?php endif; ?>
            <p class="text-center text-gray-500 text-xs uppercase font-bold">Enter the code from your App</p>
            <input type="text" name="otp_code" placeholder="000000" maxlength="6" required class="w-full p-4 border rounded-xl text-center text-4xl font-black tracking-[0.3em] outline-none focus:ring-2 focus:ring-[#00A6FB]">
            <button type="submit" class="w-full py-4 bg-black text-white rounded-xl font-bold uppercase text-xs tracking-widest hover:bg-[#00A6FB] transition-all">Authorize_Login</button>
        </form>
    </div>
</body>
</html>