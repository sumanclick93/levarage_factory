<?php
require_once('includes/db_connect.php');

require_once(__DIR__ . '/PHPMailer-master/src/Exception.php');
require_once(__DIR__ . '/PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__ . '/PHPMailer-master/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();
$message = "";
$error = "";
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    die("Invalid access protocol: Security token missing.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Error: Passwords do not match.";
    // } elseif (strlen($new_password) < 8) {
        // $error = "Error: Password must be at least 8 characters.";
    } else {
        // 1. Fetch email and username first to include in the email
        $stmt = $pdo->prepare("SELECT r.email, u.username FROM password_resets r 
                               JOIN users u ON r.email = u.email 
                               WHERE r.token = ? AND r.expires_at > NOW()");
        $stmt->execute([$token]);
        $user_data = $stmt->fetch();

        if ($user_data) {
            $email = $user_data['email'];
            $username = $user_data['username'];
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            $pdo->beginTransaction();
            try {
                // 2. Update credentials
                $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
                $update->execute([$hashed_password, $email]);

                // 3. Clear token
                $delete = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $delete->execute([$email]);

                $pdo->commit();

                // 4. SEND DETAILED SUCCESS NOTIFICATION
                $mail = new PHPMailer(true);
                try {
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
                    $mail->Sender = 'support@leveragefactory.ai';
                    
                    $mail->addAddress($email);
                    
                    $mail->isHTML(true);
                    $mail->Subject = 'Security Update: Credentials Successfully Updated';
                    
                    // Success Email Template
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
color:#10b981; /* Optional: Added a subtle green to signify success */
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
<img src="https://leveragefactory.ai/wp-content/uploads/2026/02/logo.png" class="logo" alt="Leverage Factory">
</div>

<div class="content">

<div class="title">
Password Successfully Updated
</div>

<div class="subtitle">
Hello <strong>$username</strong>, your account security protocol has been updated.
</div>

<p>
This email confirms that the password for your Leverage Factory account was recently changed. You can now use your new credentials to access the terminal.
</p>

<center>

<a href="https://leveragefactory.ai/login.php" class="button">
Log In Now
</a>

</center>

<div class="notice">

<strong>Security Alert</strong><br><br>

• If you made this change, no further action is required.<br>
• If you did <strong>not</strong> authorize this change, please contact our support team immediately to secure your account.<br>
• Never share your account credentials with anyone.

</div>

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
                } catch (Exception $e) {
                    // Log error if mail fails
                }

                $message = "Protocol Complete: Password updated. Redirecting...";
                header("refresh:3;url=login.php");

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "System Error: Could not update node.";
            }
        } else {
            $error = "Error: Invalid or expired security token.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100 p-4">
    <div class="bg-white p-8 rounded-[2rem] shadow-xl max-w-md w-full border border-gray-200">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-black uppercase italic tracking-tighter">New <span class="text-[#00A6FB]">Credentials</span>_</h2>
            <p class="text-xs text-gray-400 mt-2 font-bold uppercase tracking-widest">Update your security protocol</p>
        </div>

        <form method="POST" class="space-y-6">
            <?php if($message): ?>
                <div class="bg-green-50 text-green-600 p-4 rounded-xl text-xs font-black uppercase text-center border border-green-100"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-xl text-xs font-black uppercase text-center border border-red-100"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">New Password</label>
                <input type="password" name="password" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-[#00A6FB] font-bold">
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Confirm Password</label>
                <input type="password" name="confirm_password" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-[#00A6FB] font-bold">
            </div>

            <button type="submit" class="w-full py-4 bg-black text-white rounded-xl font-black uppercase text-xs tracking-[0.2em] shadow-lg hover:bg-[#00A6FB] transition-all">Update Password</button>
        </form>
    </div>
</body>
</html>