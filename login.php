<?php
require_once('includes/db_connect.php');
session_start();
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['is_locked']) {
            $error = "Your account is locked. Please contact support.";
        } else {
            // CHECK 2FA STATUS
            if ($user['is_2fa_enabled'] == 1 && !empty($user['google_2fa_secret'])) {
                // Hold user in a "waiting room"
                $_SESSION['temp_2fa_user_id'] = $user['id'];
                header("Location: verify_2fa.php");
                exit();
            } else {
                // Normal Login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['username'];
                header("Location: dashboard.php");
                exit();
            }
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 p-4">
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 max-w-md w-full">
        <div class="text-center mb-8">
            <div class="flex justify-center space-x-2">
                <img src="logo-removebg-preview.png" style="height: 40px;">
                <!-- <span class="text-xl font-bold tracking-wider">LEVERAGE <span class="font-light">FACTORY</span></span> -->
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Welcome Back</h2>
        </div>

        <form action="login.php" method="POST" class="space-y-4">
            <?php if($error): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm font-medium border border-red-100 text-center"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#00A6FB] outline-none">
            </div>
            <div>
                <div class="flex justify-between items-center mb-1">
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <a href="forgot_password.php" class="text-[10px] font-bold text-[#00A6FB] uppercase hover:underline">Forgot Password?</a>
                </div>
                <input type="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#00A6FB] outline-none">
            </div>

            <button type="submit" class="w-full mt-6 px-4 py-3 rounded-md font-semibold bg-[#00A6FB] hover:bg-[#0095e0] text-white transition shadow-lg">
                Sign In
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Don't have an account? 
            <a href="register.php" class="font-semibold text-[#00A6FB] hover:underline ml-1">Register</a>
        </p>
    </div>
</body>
</html>