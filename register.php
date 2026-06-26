<?php
require_once('includes/db_connect.php');

// Manual Loading Protocol for PHPMailer-master
require_once(__DIR__ . '/PHPMailer-master/src/Exception.php');
require_once(__DIR__ . '/PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__ . '/PHPMailer-master/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();

$error = "";
$ref_code_input = isset($_GET['ref_code']) ? $_GET['ref_code'] : "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']); 
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $provided_ref_code = strtoupper(trim($_POST['referral_code']));
    
    $welcome_gold = 0.5; 
    $my_referral_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    
    if ($stmt->rowCount() > 0) {
        $error = "Username or Email already exists.";
    } else {
        $pdo->beginTransaction();
        try {
            $referrer_id = null;
            $path = "";
            $upline_email = "";
            $upline_username = "";

            if (!empty($provided_ref_code)) {
                // Modified to fetch Upline details for email notification
                $stmt = $pdo->prepare("SELECT id, username, email, path FROM users WHERE referral_code = ?");
                $stmt->execute([$provided_ref_code]);
                $referrer = $stmt->fetch();
                
                if ($referrer) {
                    $referrer_id = $referrer['id'];
                    $upline_username = $referrer['username'];
                    $upline_email = $referrer['email'];
                    $path = ($referrer['path'] ? $referrer['path'] . "/" : "") . $referrer_id;
                } else {
                    throw new Exception("Invalid Referral Code.");
                }
            }

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, referral_code, referrer_id, path, gold_wallet) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password, $my_referral_code, $referrer_id, $path, $welcome_gold]);
            
            $new_user_id = $pdo->lastInsertId();

            $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'gold_bonus', ?)");
            $log_stmt->execute([$new_user_id, $welcome_gold, "Welcome Incentive: 0.5gm Gold Credited"]);
            // --- SMTP SERVER SETTINGS ---
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'localhost'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'support@leveragefactory.ai'; 
            $mail->Password   = 'ShohanPassword22!'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
            $mail->Port       = 465; 
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 10;

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            /* IMPORTANT — required by Namecheap */
            $mail->setFrom('support@leveragefactory.ai', 'Leverage Factory');
            $mail->addReplyTo('support@leveragefactory.ai', 'Leverage Factory');
            $mail->Sender     = 'support@leveragefactory.ai';
            $mail->isHTML(true);

            // 1. WELCOME EMAIL TO NEW USER
            try {
                $mail->addAddress($email);
                $mail->Subject = 'Protocol Activated: Welcome to the Elite';
                $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body{margin:0;padding:0;background:#f3f5f9;font-family:Arial, Helvetica, sans-serif;}
.wrapper{width:100%;padding:30px 10px;}
.container{max-width:600px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,0.08);}
.header{background:#0f172a;padding:35px;text-align:center;}
.logo{max-width:180px;}
.content{padding:35px;color:#333;line-height:1.6;}
.title{font-size:24px;font-weight:bold;margin-bottom:10px;}
.subtitle{color:#666;font-size:14px;margin-bottom:25px;}
.steps{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin-top:20px;}
.step{margin-bottom:10px;font-size:14px;}
.button{display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:6px;font-weight:bold;margin-top:25px;}
.footer{background:#f1f5f9;text-align:center;padding:25px;font-size:12px;color:#777;}
.website{margin-top:10px;font-weight:bold;}
</style>

</head>
<body>
<div class="wrapper">
<div class="container">
<div class="header">
<img src="https://leveragefactory.ai/wp-content/uploads/2026/02/logo.png" class="logo">
</div>
<div class="content">
<div class="title">Welcome to Leverage Factory </div>
<div class="subtitle">Hello <strong>$username</strong>, your account has been successfully created.</div>
<p>We're excited to have you join the Leverage Factory platform. You have been credited <strong>0.5gm GOLD</strong> as a welcome incentive. You can now access powerful tools designed to help you grow your portfolio.</p>
<div class="steps">
<strong>Getting Started</strong>
<div class="step">1️⃣ Deposit funds into your wallet</div>
<div class="step">2️⃣ Explore trading strategies</div>
<div class="step">3️⃣ Track your performance in the dashboard</div>
</div>
<center>
<a href="https://leveragefactory.ai/dashboard" class="button">Open Dashboard</a>
</center>
<p style="margin-top:25px;font-size:13px;color:#666;">If you have any questions, our support team is always ready to help.</p>
</div>
<div class="footer">
Security Reminder: Never share your login credentials with anyone.
<div class="website">https://leveragefactory.ai</div>
</div>
</div>
</div>
</body>
</html>
HTML;
                $mail->send();
            } catch (Exception $e) {}

            // 2. NOTIFICATION TO UPLINE (If exists)
            if (!empty($upline_email)) {
                try {
                    $mail->clearAddresses();
                    $mail->addAddress($upline_email);
                    $mail->Subject = ' A New Member Joined Your Leverage Factory Network';
        
                    // Upline Email - matching the new HTML structure
                    $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body{margin:0;padding:0;background:#f3f5f9;font-family:Arial, Helvetica, sans-serif;}
.wrapper{width:100%;padding:30px 10px;}
.container{max-width:600px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,0.08);}
.header{background:#0f172a;padding:35px;text-align:center;}
.logo{max-width:180px;}
.content{padding:35px;color:#333;line-height:1.6;}
.title{font-size:24px;font-weight:bold;margin-bottom:10px;color:#2563eb;}
.subtitle{color:#666;font-size:14px;margin-bottom:25px;}
.steps{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin-top:20px;border-left:4px solid #2563eb;}
.step{margin-bottom:10px;font-size:14px;}
.button{display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:6px;font-weight:bold;margin-top:25px;}
.footer{background:#f1f5f9;text-align:center;padding:25px;font-size:12px;color:#777;}
.website{margin-top:10px;font-weight:bold;}
</style>

</head>
<body>
<div class="wrapper">
<div class="container">
<div class="header">
<img src="https://leveragefactory.ai/wp-content/uploads/2026/02/logo.png" class="logo">
</div>
<div class="content">
<div class="title">Network Expansion Alert </div>
<div class="subtitle">Hello <strong>$upline_username</strong>, your network is growing!</div>
<p>Great news! <strong>$username</strong> has just joined Leverage Factory using your referral link.</p>
<p>You are now their primary connection inside the platform. Make sure to reach out, get them up to date, and guide them through their investment journey using our social investment platform.</p>
<div class="steps">
<strong><em>"Helping your network succeed helps you grow too."</em></strong>
<div class="step" style="margin-top:10px;">• Reach out and welcome them to the platform</div>
<div class="step">• Share your insights and strategies</div>
<div class="step">• Track your expanding network in your dashboard</div>
</div>
<center>
<a href="https://leveragefactory.ai/dashboard" class="button">View Your Network</a>
</center>
</div>
<div class="footer">
Network Expansion Protocol Active
<div class="website">https://leveragefactory.ai</div>
</div>
</div>
</div>
</body>
</html>
HTML;
                    $mail->send();
                } catch (Exception $e) {}
            }

            $pdo->commit();
            header("Location: login.php?registered=success");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-xl p-8 max-w-md w-full">
        <div class="text-center mb-8">
            <!--<div class="flex justify-center items-center gap-2 mb-4">-->
            <!--    <svg width="40" height="40" viewBox="0 0 48 48" fill="none">-->
            <!--        <path d="M8 20L16 12L28 20L40 10" stroke="#00A6FB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>-->
            <!--        <rect x="13" y="24" width="6" height="16" rx="2" fill="#FFD700"/> <rect x="21" y="28" width="6" height="12" rx="2" fill="#DAA520"/> <rect x="29" y="22" width="6" height="18" rx="2" fill="#B8860B"/> </svg>-->
            <!--    <span class="text-2xl font-bold text-gray-900 uppercase">Leverage Factory</span>-->
            <!--</div>-->
            <div class="flex justify-center space-x-2">
                <img src="logo-removebg-preview.png" style="height: 40px;">
                <!-- <span class="text-xl font-bold tracking-wider">LEVERAGE <span class="font-light">FACTORY</span></span> -->
            </div>
            <h2 class="text-xl font-bold text-gray-900">Join the Elite Protocol</h2>
            <p class="text-[10px] font-black text-[#22d3ee] uppercase tracking-widest mt-2 animate-pulse">Bonus: 0.5gm Gold Credited on Success</p>
        </div>

        <form action="register.php" method="POST" class="space-y-4">
            <?php if($error): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-lg text-xs font-bold border border-red-100 text-center uppercase"><?php echo $error; ?></div>
            <?php endif; ?>
        
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Username</label>
                <input type="text" name="username" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#FFD700] outline-none">
            </div>
        
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#FFD700] outline-none">
            </div>
        
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Referral Code (Optional)</label>
                <input type="text" name="referral_code" value="<?php echo htmlspecialchars($ref_code_input); ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#FFD700] outline-none" placeholder="e.g. D12EA090">
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#FFD700] outline-none">
            </div>
        
            <button type="submit" class="w-full mt-4 px-4 py-4 rounded-xl font-black bg-gradient-to-r from-[#22d3eecc] to-[#05cdec] text-black shadow-lg transition transform active:scale-95 uppercase text-xs tracking-widest">
                Execute Registration
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6 font-medium">
            Already verified? <a href="login.php" class="text-[#22d3ee] hover:underline font-bold">Login</a>
        </p>
    </div>
</body>
</html>