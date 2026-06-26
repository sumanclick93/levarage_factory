<?php
require_once('includes/db_connect.php');
require_once('includes/GoogleAuthenticator.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ga = new PHPGangsta_GoogleAuthenticator();

// Fetch user data
$stmt = $pdo->prepare("SELECT google_2fa_secret, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Generate secret if not exists
$secret = $user['google_2fa_secret'];
if (empty($secret)) {
    $secret = $ga->createSecret();
    $update = $pdo->prepare("UPDATE users SET google_2fa_secret = ? WHERE id = ?");
    $update->execute([$secret, $user_id]);
}

// Generate QR Code URL
$qrCodeUrl = $ga->getQRCodeGoogleUrl('LeverageFactory:'.$user['username'], $secret);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2FA Setup - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-[2rem] shadow-xl max-w-md w-full border border-gray-200">
        <h2 class="text-2xl font-black uppercase italic tracking-tighter text-center mb-6">Setup <span class="text-[#00A6FB]">2FA</span>_</h2>
        
        <div class="text-center space-y-6">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">1. Scan QR with Authenticator App</p>
            
            <div class="flex justify-center p-4 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code">
            </div>

            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">2. Enter 6-Digit Code to Activate</p>

            <form action="activate_2fa.php" method="POST" class="space-y-4">
                <input type="text" name="verify_code" placeholder="000000" maxlength="6" required 
                       class="w-full p-4 border rounded-xl text-center text-3xl font-black tracking-[0.3em] outline-none focus:ring-2 focus:ring-[#00A6FB]">
                <button type="submit" class="w-full py-4 bg-black text-white rounded-xl font-bold uppercase text-xs tracking-widest hover:bg-[#00A6FB] transition-all">
                    Enable 2FA_
                </button>
            </form>
        </div>
    </div>
</body>
</html>