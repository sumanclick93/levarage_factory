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
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32)); 

        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
        $stmt->execute([$email, $token]);

        // Replace with your actual live domain
        $reset_link = "https://leveragefactory.ai/reset_password.php?token=" . $token;
        $username = $user['username'];

        $mail = new PHPMailer(true);
        try {
            // SERVER SETTINGS
            $mail->isSMTP();
            $mail->Host       = 'localhost'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'support@leveragefactory.ai'; 
            $mail->Password   = 'ShohanPassword22!'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
            $mail->Port       = 465; 
            $mail->CharSet    = 'UTF-8';

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

            $mail->addAddress($email);     

            $mail->isHTML(true);                                  
            $mail->Subject = 'Protocol: Password Reset Request';
            
            // Replaced the old email template with the new HTML provided
            $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>

body{
margin:0;
padding:0;
background:#f3f5f9;
font-family:Arial, Helvetica, sans-serif;
}

.wrapper{
width:100%;
padding:30px 10px;
}

.container{
max-width:600px;
margin:auto;
background:#ffffff;
border-radius:10px;
overflow:hidden;
box-shadow:0 6px 18px rgba(0,0,0,0.08);
}

.header{
background:#0f172a;
padding:35px;
text-align:center;
}

.logo{
max-width:180px;
}

.content{
padding:35px;
color:#333;
line-height:1.6;
}

.title{
font-size:24px;
font-weight:bold;
margin-bottom:10px;
}

.subtitle{
color:#666;
font-size:14px;
margin-bottom:25px;
}

.button{
display:inline-block;
background:#2563eb;
color:#ffffff;
text-decoration:none;
padding:14px 28px;
border-radius:6px;
font-weight:bold;
margin-top:20px;
}

.notice{
margin-top:25px;
background:#f8fafc;
border:1px solid #e5e7eb;
border-radius:6px;
padding:18px;
font-size:13px;
color:#555;
}

.footer{
background:#f1f5f9;
text-align:center;
padding:25px;
font-size:12px;
color:#777;
}

.website{
margin-top:10px;
font-weight:bold;
}

</style>

</head>

<body>

<div class="wrapper">

<div class="container">

<div class="header">
<img src="https://leveragefactory.ai/wp-content/uploads/2026/02/logo.png" class="logo">
</div>

<div class="content">

<div class="title">
Reset Your Password
</div>

<div class="subtitle">
Hello <strong>$username</strong>, we received a request to reset your password.
</div>

<p>
Click the button below to create a new password for your account.
</p>

<center>

<a href="$reset_link" class="button">
Reset Password
</a>

</center>

<div class="notice">

<strong>Security Notice</strong><br><br>

• This password reset link will expire in <strong>30 minutes</strong>.<br>
• If you did not request a password reset, please ignore this email.<br>
• Never share your account credentials with anyone.

</div>

<p style="margin-top:25px;font-size:13px;color:#666;">
If the button above does not work, copy and paste this link into your browser:
</p>

<p style="font-size:13px;word-break:break-all;">
$reset_link
</p>

</div>

<div class="footer">

Security Reminder: Always keep your account secure.

<div class="website">
https://leveragefactory.ai
</div>

</div>

</div>

</div>

</body>
</html>
HTML;

            $mail->send();
            $message = "Recovery link dispatched. Check your node inbox.";
        } catch (Exception $e) {
            $error = "Mail delivery failure. Protocol error: {$mail->ErrorInfo}";
        }
    } else {
        $error = "Email not found in our nodes.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-[#F8FAFC] p-4">
    <div class="bg-white p-8 rounded-[2.5rem] shadow-xl max-w-md w-full border border-gray-100">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-black uppercase italic tracking-tighter">Password <span class="text-[#00A6FB]">Reset</span></h2>
            <p class="text-[10px] text-gray-400 mt-2 font-bold uppercase tracking-[0.3em]">Credentials Recovery Node</p>
        </div>

        <form method="POST" class="space-y-6">
            <?php if($message): ?>
                <div class="bg-green-50 text-green-600 p-4 rounded-xl text-xs font-black uppercase border border-green-100"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-xl text-xs font-black uppercase border border-red-100"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Registered Email</label>
                <input type="email" name="email" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-2xl outline-none focus:ring-2 focus:ring-[#00A6FB] font-bold">
            </div>

            <button type="submit" class="w-full py-5 bg-black text-white rounded-2xl font-black uppercase text-[10px] tracking-[0.3em] shadow-lg hover:bg-[#00A6FB] transition-all">Send Reset Link</button>
        </form>

        <p class="mt-8 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">
            Return to <a href="login.php" class="text-[#00A6FB] hover:underline">Login</a>
        </p>
    </div>
</body>
</html>