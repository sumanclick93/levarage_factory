<?php
header('Content-Type: application/json');

require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

/* PHPMailer Requirements */
require_once(__DIR__ . '/../PHPMailer-master/src/Exception.php');
require_once(__DIR__ . '/../PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__ . '/../PHPMailer-master/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* SMTP Connection Credentials (matching announcement.php for compatibility) */
$smtp_email = "support@leveragefactory.ai";
$smtp_password = "ShohanPassword22!";
$from_email = "support@leveragefactory.ai";
$from_name  = "Leverage Factory";

// Helper function to wrap text/HTML inside the brand email template
function wrapInEmailTemplate($subject, $message_body) {
    return '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    margin: 0;
    padding: 0;
    background: #f4f6f9;
    font-family: Arial, Helvetica, sans-serif;
}
.container {
    max-width: 600px;
    margin: auto;
    background: #ffffff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
.header {
    background: #0f172a;
    padding: 30px;
    text-align: center;
}
.logo {
    max-width: 200px;
}
.content {
    padding: 35px 30px;
    color: #334155;
    font-size: 15px;
    line-height: 1.6;
}
.title {
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 20px;
    color: #0f172a;
    border-bottom: 2px solid #00A6FB;
    padding-bottom: 10px;
}
.footer {
    background: #0f172a;
    color: #cbd5e1;
    text-align: center;
    padding: 25px;
    font-size: 13px;
}
.website {
    margin-top: 8px;
    font-weight: bold;
    color: #ffffff;
}
</style>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f6f9;">
    <tr>
        <td style="padding: 20px 0;">
            <table class="container" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    <td class="header">
                        <img src="https://leveragefactory.ai/wp-content/uploads/2026/02/logo.png" class="logo" alt="Leverage Factory Logo">
                    </td>
                </tr>
                <tr>
                    <td class="content">
                        <div class="title">' . htmlspecialchars($subject) . '</div>
                        ' . $message_body . '
                    </td>
                </tr>
                <tr>
                    <td class="footer">
                        Secure • Trusted • Profitable
                        <div class="website">https://leveragefactory.ai</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>';
}

// Function to construct and configure PHPMailer
function getMailerInstance($smtp_email, $smtp_password, $from_email, $from_name) {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'localhost';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_email;
    $mail->Password   = $smtp_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 10;
    
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    $mail->setFrom($from_email, $from_name);
    $mail->addReplyTo($from_email, $from_name);
    $mail->Sender = $from_email;
    $mail->isHTML(true);
    
    return $mail;
}

// Check action parameter
$action = $_POST['action'] ?? $_GET['action'] ?? null;

if (!$action) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameter: action']);
    exit();
}

try {
    if ($action === 'send_single') {
        $recipient = trim($_POST['recipient'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = $_POST['message'] ?? '';
        $wrap_template = isset($_POST['wrap_template']) && $_POST['wrap_template'] == '1';

        if (empty($recipient) || empty($subject) || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'All fields (recipient, subject, message) are required']);
            exit();
        }

        // Validate recipient email
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            // Check if it's a username, if so, look up email
            $stmt = $pdo->prepare("SELECT email FROM users WHERE username = ? OR id = ?");
            $stmt->execute([$recipient, $recipient]);
            $user_email = $stmt->fetchColumn();
            if ($user_email) {
                $recipient = $user_email;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid email address or username not found']);
                exit();
            }
        }

        $mail = getMailerInstance($smtp_email, $smtp_password, $from_email, $from_name);
        $mail->addAddress($recipient);
        $mail->Subject = $subject;
        $mail->Body = $wrap_template ? wrapInEmailTemplate($subject, $message) : $message;
        $mail->send();

        // Log this action to admin audit logs
        logAdminAction($pdo, "Sent individual email to: $recipient | Subject: $subject");

        echo json_encode(['status' => 'success', 'message' => "Email sent successfully to $recipient"]);
        exit();

    } elseif ($action === 'get_bulk_count') {
        $segment = $_POST['segment'] ?? 'all';
        
        $sql = "SELECT COUNT(*) FROM users";
        $params = [];
        if ($segment === 'non_locked') {
            $sql .= " WHERE is_locked = 0";
        } elseif ($segment === 'active_investors') {
            $sql = "SELECT COUNT(DISTINCT u.id) FROM users u JOIN investments i ON u.id = i.user_id WHERE i.status = 'active'";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $count = (int)$stmt->fetchColumn();

        echo json_encode(['status' => 'success', 'total_count' => $count]);
        exit();

    } elseif ($action === 'send_bulk_batch') {
        $segment = $_POST['segment'] ?? 'all';
        $offset = (int)($_POST['offset'] ?? 0);
        $limit = (int)($_POST['limit'] ?? 10);
        $subject = trim($_POST['subject'] ?? '');
        $message = $_POST['message'] ?? '';
        $wrap_template = isset($_POST['wrap_template']) && $_POST['wrap_template'] == '1';

        if (empty($subject) || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Campaign subject and message body are required']);
            exit();
        }

        // Fetch emails in this batch slice
        if ($segment === 'active_investors') {
            $sql = "SELECT DISTINCT u.email 
                    FROM users u 
                    JOIN investments i ON u.id = i.user_id 
                    WHERE i.status = 'active'
                    ORDER BY u.id ASC 
                    LIMIT :offset, :limit";
        } elseif ($segment === 'non_locked') {
            $sql = "SELECT email FROM users WHERE is_locked = 0 ORDER BY id ASC LIMIT :offset, :limit";
        } else {
            $sql = "SELECT email FROM users ORDER BY id ASC LIMIT :offset, :limit";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll();

        if (empty($users)) {
            echo json_encode([
                'status' => 'success',
                'batch_results' => [],
                'completed' => true,
                'message' => 'Campaign sending completed.'
            ]);
            exit();
        }

        $mail = getMailerInstance($smtp_email, $smtp_password, $from_email, $from_name);
        $mail->Subject = $subject;
        $mail->Body = $wrap_template ? wrapInEmailTemplate($subject, $message) : $message;

        $batch_results = [];
        $success_count = 0;
        $fail_count = 0;

        foreach ($users as $user) {
            $email = trim($user['email']);
            if (empty($email)) continue;

            try {
                $mail->clearAddresses();
                $mail->addAddress($email);
                $mail->send();
                $batch_results[] = ['email' => $email, 'status' => 'success'];
                $success_count++;
            } catch (Exception $e) {
                $batch_results[] = ['email' => $email, 'status' => 'failed', 'error' => $mail->ErrorInfo];
                $fail_count++;
            }
        }

        // Log this action to admin audit logs on the first batch or at completion
        if ($offset === 0) {
            logAdminAction($pdo, "Started bulk campaign: '$subject' to segment: $segment");
        }

        echo json_encode([
            'status' => 'success',
            'batch_results' => $batch_results,
            'success_count' => $success_count,
            'fail_count' => $fail_count,
            'completed' => count($users) < $limit,
            'next_offset' => $offset + count($users)
        ]);
        exit();

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action type']);
        exit();
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
    exit();
}
