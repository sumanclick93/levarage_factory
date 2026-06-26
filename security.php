<?php
require_once('includes/db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// 1. Fetch current 2FA status
$stmt = $pdo->prepare("SELECT username, google_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 2. Handle Password Change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_pw = $_POST['current_password'];
    $new_pw = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];

    // Verify current password
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $stored_hash = $stmt->fetchColumn();

    if (!password_verify($current_pw, $stored_hash)) {
        $error = "Current password is incorrect.";
    } elseif ($new_pw !== $confirm_pw) {
        $error = "New passwords do not match.";
    } else {
        $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_hash, $user_id]);
        $success = "Password updated successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Center - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col md:flex-row min-h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="mb-8">
            <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase italic">Security Center</h1>
            <p class="text-gray-500 text-sm">Protect your account and managed assets.</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-200">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#00A6FB]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    Change Password
                </h2>
                
                <?php if($success): ?>
                    <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl text-xs font-bold border border-green-100 text-center uppercase tracking-tight"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-xs font-bold border border-red-100 text-center uppercase tracking-tight"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="change_password" value="1">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1 tracking-widest">Current Password</label>
                        <input type="password" name="current_password" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#00A6FB] outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1 tracking-widest">New Password</label>
                        <input type="password" name="new_password" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#00A6FB] outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1 tracking-widest">Confirm New Password</label>
                        <input type="password" name="confirm_password" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#00A6FB] outline-none">
                    </div>
                    <button type="submit" class="w-full py-4 bg-gray-900 text-white rounded-xl font-bold uppercase tracking-widest hover:bg-[#00A6FB] transition">
                        Update Password
                    </button>
                </form>
            </div>

            <!--<div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-200">-->
            <!--    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">-->
            <!--        <svg class="w-5 h-5 text-[#00A6FB]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>-->
            <!--        Two-Factor Authentication (2FA)-->
            <!--    </h2>-->
                
            <!--    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-100 mb-6">-->
            <!--        <div>-->
            <!--            <p class="font-bold text-gray-900">Google Authenticator</p>-->
            <!--            <p class="text-xs text-gray-500">Adds an extra layer of security to your logins.</p>-->
            <!--        </div>-->
            <!--        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase <?php echo $user['google_id'] ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500'; ?>">-->
            <!--            <?php echo $user['google_id'] ? 'Active' : 'Disabled'; ?>-->
            <!--        </span>-->
            <!--    </div>-->

            <!--    <p class="text-sm text-gray-500 mb-8 leading-relaxed">-->
            <!--        Once enabled, you will be required to enter a code generated by the Google Authenticator app on your mobile device to sign in.-->
            <!--    </p>-->

            <!--    <?php if(!$user['google_id']): ?>-->
            <!--        <a href="setup_2fa.php" class="block w-full text-center py-4 bg-[#00A6FB] text-white rounded-xl font-bold uppercase tracking-widest hover:bg-blue-600 shadow-lg shadow-blue-100 transition">-->
            <!--            Enable 2FA Now-->
            <!--        </a>-->
            <!--    <?php else: ?>-->
            <!--        <button onclick="confirm('Disable 2FA? This will reduce your account security.')" class="w-full py-4 border-2 border-red-100 text-red-600 rounded-xl font-bold uppercase tracking-widest hover:bg-red-50 transition">-->
            <!--            Deactivate 2FA-->
            <!--        </button>-->
            <!--    <?php endif; ?>-->
            <!--</div>-->
        </div>
    </main>
</body>
</html>