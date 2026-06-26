<?php
require_once('includes/db_connect.php');
session_start();

set_time_limit(0);
ini_set('max_execution_time', 0);

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* PHPMailer */
require_once(__DIR__ . '/PHPMailer-master/src/Exception.php');
require_once(__DIR__ . '/PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__ . '/PHPMailer-master/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* SMTP SETTINGS (SAME AS YOUR WORKING CODE) */
$smtp_email = "support@leveragefactory.ai";
$smtp_password = "ShohanPassword22!";

$from_email = "support@leveragefactory.ai";
$from_name  = "Leverage Factory";

$subject = "Leverage Factory Launch Update – Crypto Index Opportunity";

/* HTML EMAIL BODY */

$message_content = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

body{
margin:0;
padding:0;
background:#f4f6f9;
font-family:Arial, Helvetica, sans-serif;
}

.container{
max-width:600px;
margin:auto;
background:#ffffff;
}

.header{
background:#0f172a;
padding:30px;
text-align:center;
}

.logo{
max-width:200px;
}

.content{
padding:30px;
color:#333;
font-size:15px;
line-height:1.6;
}

.title{
font-size:22px;
font-weight:bold;
margin-bottom:20px;
color:#111827;
}

.section-title{
font-size:18px;
font-weight:bold;
margin-top:25px;
margin-bottom:10px;
color:#0f172a;
}

.coin-grid{
width:100%;
margin-top:10px;
}

.coin{
display:inline-block;
width:48%;
padding:8px 0;
font-weight:bold;
}

.box{
background:#f8fafc;
border-left:4px solid #2563eb;
padding:15px;
margin-top:15px;
border-radius:6px;
}

.button{
display:inline-block;
background:#2563eb;
color:#ffffff;
padding:14px 28px;
text-decoration:none;
border-radius:6px;
margin-top:25px;
font-weight:bold;
}

.footer{
background:#0f172a;
color:#d1d5db;
text-align:center;
padding:25px;
font-size:13px;
}

.website{
margin-top:8px;
font-weight:bold;
color:#ffffff;
}

</style>
</head>

<body>

<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td>

<table class="container">

<tr>
<td class="header">
<img src="https://leveragefactory.ai/wp-content/uploads/2026/02/logo.png" class="logo">
</td>
</tr>

<tr>
<td class="content">

<div class="title">
Leverage Factory Launch Update – Crypto Index Opportunity
</div>

<p>Dear Leverage Factory Community,</p>

<p>
We are excited to share that we are <strong>very close to the official launch</strong> of Leverage Factory.
</p>

<div class="section-title">
New Crypto Deposits & Withdrawals
</div>

<div class="coin-grid">
<div class="coin">BTC – Bitcoin</div>
<div class="coin">ETH – Ethereum</div>
<div class="coin">XRP – Ripple</div>
<div class="coin">SOL – Solana</div>
<div class="coin">TRX – TRON</div>
</div>

<div class="box">
Returns are paid in the <strong>same cryptocurrency you deposit</strong>.
</div>

<div class="section-title">
Limited-Time Opportunity
</div>

<p>
Our <strong>Crypto Index Package</strong> allows members to earn up to <strong>260%</strong>.
</p>

<center>
<a href="https://leveragefactory.ai" class="button">
Visit Leverage Factory
</a>
</center>

<p style="margin-top:30px;">
Warm regards,<br>
<strong>The Leverage Factory Team</strong>
</p>

</td>
</tr>

<tr>
<td class="footer">
Secure • Trusted • Profitable
<div class="website">
https://leveragefactory.ai
</div>
</td>
</tr>

</table>

</td>
</tr>
</table>

</body>
</html>';


/* SEND IN BATCHES */

$limit = 20;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

$stmt = $pdo->prepare("SELECT email FROM users WHERE is_locked = 0 LIMIT :offset,:limit");
$stmt->bindValue(':offset',$offset,PDO::PARAM_INT);
$stmt->bindValue(':limit',$limit,PDO::PARAM_INT);
$stmt->execute();

$users = $stmt->fetchAll();

echo "<h2>Broadcast Progress</h2>";
echo "Sending users $offset → ".($offset+$limit)."<br><br>";

if(!$users){
    echo "<h3>Broadcast Completed</h3>";
    exit;
}

$mail = new PHPMailer(true);

try{

$mail->isSMTP();
$mail->Host       = 'localhost';
$mail->SMTPAuth   = true;
$mail->Username   = $smtp_email;
$mail->Password   = $smtp_password;
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
$mail->setFrom($from_email,$from_name);
$mail->addReplyTo($from_email,$from_name);
$mail->Sender     = $from_email;
$mail->isHTML(true);
$mail->Subject = $subject;

foreach($users as $user){

$email = trim($user['email']);

try{

$mail->clearAddresses();
$mail->addAddress($email);
$mail->Body = $message_content;
$mail->send();

echo "<div style='color:green'>SENT → $email</div>";

}catch(Exception $e){

echo "<div style='color:red'>FAILED → $email | ".$mail->ErrorInfo."</div>";

}

sleep(2);

}

}catch(Exception $e){

echo "Mailer Error: ".$mail->ErrorInfo;

}

$next = $offset + $limit;

echo "<script>
setTimeout(function(){
window.location='?offset=$next';
},3000);
</script>";
?>