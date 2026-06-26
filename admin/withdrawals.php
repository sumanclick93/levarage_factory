<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

// PHPMailer Manual Loading Protocol
require_once(__DIR__ . '/PHPMailer-master/src/Exception.php');
require_once(__DIR__ . '/PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__ . '/PHPMailer-master/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$error_notification = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['w_action'])) {
    $w_id = $_POST['w_id'];
    $action = $_POST['w_action'];
    $txn_id = trim($_POST['txn_id'] ?? '');
    $reject_reason = trim($_POST['reject_reason'] ?? '');

    // --- SERVER-SIDE VALIDATION ENFORCEMENT ---
    if ($action == 'approve' && empty($txn_id)) {
        $error_notification = "Protocol Error: Transaction ID is mandatory for approvals.";
    } elseif ($action == 'reject' && empty($reject_reason)) {
        $error_notification = "Protocol Error: A rejection reason must be provided to the user.";
    } else {
        $db_success = false;
        $request = null;

        // ==========================================
        // PHASE 1: EXECUTE DATABASE QUERIES FIRST
        // ==========================================
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT w.*, u.username, u.email FROM withdrawal_requests w 
                                   JOIN users u ON w.user_id = u.id WHERE w.id = ?");
            $stmt->execute([$w_id]); 
            $request = $stmt->fetch();

            if ($request) {
                if ($action == 'approve') {
                    $pdo->prepare("UPDATE withdrawal_requests SET status = 'approved', `transaction_id` = ? WHERE id = ?")
                        ->execute([$txn_id, $w_id]);
                    // Store TXN ID in Activity Log
                    logAdminAction($pdo, "Approved Withdrawal #$w_id. TXN Hash: $txn_id", $request['user_id']);
                } else {
                    $pdo->prepare("UPDATE withdrawal_requests SET status = 'rejected' WHERE id = ?")->execute([$w_id]);
                    $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$request['amount'], $request['user_id']]);
                    $pdo->prepare("INSERT INTO `transactions` (`user_id`, `amount`, `type`, `description`) VALUES (?, ?, 'reverse_back', ?)")
                    ->execute([
                        $request['user_id'], 
                        $request['amount'], 
                        "Rejected Withdrawal of {$request['amount']}, transaction id #{$w_id}. Reason: {$reject_reason}"
                    ]);
                    
                    // Store Reason in Activity Log
                    logAdminAction($pdo, "Rejected Withdrawal #$w_id. Reason: $reject_reason", $request['user_id']);
                }
            }
            $pdo->commit();
            $db_success = true; // DB updated successfully
        } catch (\Exception $e) { 
            $pdo->rollBack(); 
            $error_notification = "Database Error: " . $e->getMessage();
        }

        // ==========================================
        // PHASE 2: SEND EMAIL ONLY IF DB SUCCEEDED
        // ==========================================
        if ($db_success && $request) {
            try {
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
                $mail->setFrom('support@leveragefactory.ai', 'Leverage Factory Finance');
                $mail->addReplyTo('support@leveragefactory.ai', 'Leverage Factory Finance');
                $mail->Sender     = 'support@leveragefactory.ai';
                
                $mail->addAddress($request['email']);
                $mail->isHTML(true);

                $email_username = htmlspecialchars($request['username']);
                $display_amount = '$' . number_format($request['amount'], 2);
                $display_wallet = htmlspecialchars($request['wallet_address'] ?? 'N/A');
                $display_reason = htmlspecialchars($reject_reason);

                if ($action == 'approve') {
                    $mail->Subject = 'Withdrawal Completed';
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
.subtitle{font-size:14px;color:#666;margin-bottom:20px;}
.card{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin-top:20px;}
.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;}
.row:last-child{border-bottom:none;}
.label{color:#777;font-size:14px;}
.value{font-weight:bold;font-size:14px;}
.status{color:#16a34a;font-weight:bold;}
.txid{word-break:break-all;font-size:13px;}
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
<div class="title">Withdrawal Completed</div>
<div class="subtitle">Hello <strong> $email_username</strong>, your withdrawal has been successfully processed.</div>
<div class="card">
<div class="row"><div class="label">Amount</div><div class="value"> $display_amount</div></div>
<div class="row"><div class="label">Currency</div><div class="value"> USD</div></div>
<div class="row"><div class="label">Wallet Address</div><div class="value"> $display_wallet</div></div>
<div class="row"><div class="label">Transaction ID (TXID)</div><div class="value txid"> $txn_id</div></div>
<div class="row"><div class="label">Status</div><div class="value status"> Completed</div></div>
</div>
<center><a href="https://leveragefactory.ai/dashboard" class="button">View Dashboard</a></center>
<p style="margin-top:25px;font-size:13px;color:#666;">If you did not authorize this withdrawal, please contact support immediately.</p>
</div>
<div class="footer">
Security Reminder: Always keep your account secure.
<div class="website">https://leveragefactory.ai</div>
</div>
</div>
</div>
</body>
</html>
HTML;
                } else {
                    $mail->Subject = 'Security Alert: Withdrawal Request Rejected';
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
.subtitle{font-size:14px;color:#666;margin-bottom:20px;}
.card{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin-top:20px;}
.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;}
.row:last-child{border-bottom:none;}
.label{color:#777;font-size:14px;}
.value{font-weight:bold;font-size:14px;}
.status-rejected{color:#ef4444;font-weight:bold;}
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
<div class="title">Withdrawal Request Rejected</div>
<div class="subtitle">Hello <strong> $email_username</strong>, your withdrawal request has been rejected.</div>
<div class="card">
<div class="row"><div class="label">Amount</div><div class="value"> $display_amount</div></div>
<div class="row"><div class="label">Currency</div><div class="value"> USD</div></div>
<div class="row"><div class="label">Status</div><div class="value status-rejected"> Rejected</div></div>
<div class="row" style="flex-direction: column; border-bottom: none; margin-top: 10px;">
    <div class="label" style="margin-bottom: 5px;">Reason for Rejection:</div>
    <div class="value" style="color: #ef4444;"> $display_reason</div>
</div>
</div>
<p style="margin-top:25px;font-size:14px;color:#10b981;font-weight:bold;">
Note: Your funds have been fully restored to your wallet balance.
</p>
<center><a href="https://leveragefactory.ai/dashboard" class="button">View Dashboard</a></center>
</div>
<div class="footer">
Security Reminder: Always keep your account secure.
<div class="website">https://leveragefactory.ai</div>
</div>
</div>
</div>
</body>
</html>
HTML;
                }
                $mail->send();
            } catch (\Exception $e) {
                // Mail failed, but database was already updated. 
                // We optionally log this error so the script can still redirect smoothly.
                error_log("Mail sending failed: " . $mail->ErrorInfo);
            }
            
            // Redirect happens regardless of mail success as long as DB updated
            header("Location: withdrawals.php?success=1");
            exit();
        }
    }
}

// Fetch Pending Requests
$query = "SELECT w.*, u.username, u.email, u.wallet_balance as current_balance 
          FROM withdrawal_requests w 
          JOIN users u ON w.user_id = u.id 
          WHERE w.status = 'pending' ORDER BY w.created_at DESC";
$requests = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Withdrawal Manager - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // --- Copy to Clipboard Protocol ---
        function copyAddress(btn) {
            const address = btn.getAttribute('data-address');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(address).then(() => {
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3"/></svg>';
                    setTimeout(() => { btn.innerHTML = originalHtml; }, 1500);
                });
            } else {
                const textarea = document.createElement("textarea");
                textarea.value = address;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand("copy");
                document.body.removeChild(textarea);
                alert("Address copied");
            }
        }

        // --- CLIENT-SIDE VALIDATION PROTOCOL ---
        function validateDecision(form, action) {
            const txnInput = form.querySelector('input[name="txn_id"]');
            const reasonInput = form.querySelector('input[name="reject_reason"]');

            if (action === 'approve') {
                if (txnInput.value.trim() === "") {
                    alert("Error: Transaction ID is required to mark as paid.");
                    txnInput.focus();
                    return false;
                }
            } else if (action === 'reject') {
                if (reasonInput.value.trim() === "") {
                    alert("Error: A rejection reason must be provided for the user.");
                    reasonInput.focus();
                    return false;
                }
            }
            return true;
        }
    </script>
</head>
<body class="bg-gray-100 text-gray-800 flex h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="mb-8">
            <h1 class="text-3xl font-black text-gray-900 uppercase italic">Withdrawal <span class="text-[#00A6FB]">Requests</span>_</h1>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-widest">Financial Node Payout Authorization</p>
        </header>

        <?php if($error_notification): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-xs font-black uppercase tracking-widest">
                <?php echo htmlspecialchars($error_notification); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-200 text-[10px] uppercase text-gray-500 font-black">
                    <tr>
                        <th class="py-4 px-6">User / Network</th>
                        <th class="py-4 px-6">Requested Amount</th>
                        <th class="py-4 px-6">Destination Node</th>
                        <th class="py-4 px-6">Withdrawl Network</th>
                        <th class="py-4 px-6 text-right">Action Protocol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <?php if(empty($requests)): ?>
                        <tr><td colspan="5" class="py-12 text-center text-gray-400 font-bold uppercase text-xs">No pending payout nodes active.</td></tr>
                    <?php endif; ?>

                    <?php foreach($requests as $r): ?>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="py-4 px-6">
                            <div class="font-black text-gray-900 uppercase tracking-tighter"><?php echo htmlspecialchars($r['username']); ?></div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="font-mono font-black text-red-600 text-lg">-$<?php echo number_format($r['amount'], 2); ?></div>
                            <p class="text-[9px] text-gray-400 font-bold uppercase"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></p>
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-2">
                                <p class="text-[9px] font-mono bg-gray-100 p-2 rounded-lg border border-gray-200 break-all max-w-[180px]">
                                    <?php echo htmlspecialchars($r['wallet_address']); ?>
                                </p>
                                <button type="button"
                                        data-address="<?php echo htmlspecialchars($r['wallet_address']); ?>"
                                        onclick="copyAddress(this)"
                                        class="p-2 hover:bg-gray-100 rounded-lg transition-all text-gray-400 hover:text-[#00A6FB]"
                                        title="Copy Address">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-[10px] text-[#00A6FB] font-bold uppercase tracking-widest"><?php echo htmlspecialchars($r['network']); ?>_NETWORK</div>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <form method="POST" onsubmit="return false;" class="flex flex-col gap-3 items-end">
                                <input type="hidden" name="w_id" value="<?php echo $r['id']; ?>">
                                <input type="hidden" name="w_action" value="">
                                
                                <div class="flex flex-col gap-2 w-full max-w-[220px]">
                                    <input type="text" name="txn_id" placeholder="Enter Blockchain TXN ID" 
                                           class="px-3 py-2 border rounded-lg text-[10px] font-bold outline-none focus:ring-1 focus:ring-green-500 bg-gray-50 uppercase shadow-inner">
                                    <input type="text" name="reject_reason" placeholder="Reason (If Denying)" 
                                           class="px-3 py-2 border rounded-lg text-[10px] font-bold outline-none focus:ring-1 focus:ring-red-500 bg-gray-50 shadow-inner">
                                </div>

                                <div class="flex gap-2">
                                    <button type="button" onclick="window.location.href='view_all_withdrawls.php?user_id=<?php echo $r['user_id']; ?>'"
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg transition active:scale-95">
                                        View
                                    </button>
                                    <button type="button" 
                                            onclick="if(validateDecision(this.form, 'approve')){ this.form.w_action.value='approve'; this.form.submit(); }"
                                            class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg transition active:scale-95">
                                        Mark Paid
                                    </button>
                                    <button type="button" 
                                            onclick="if(validateDecision(this.form, 'reject')){ this.form.w_action.value='reject'; this.form.submit(); }"
                                            class="bg-white border border-gray-200 text-gray-600 hover:bg-red-50 hover:text-red-700 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition">
                                        Reject
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>