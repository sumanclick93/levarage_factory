<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');
require_once(__DIR__ . '/includes/functions.php');

// Manual Loading Protocol for PHPMailer
require_once(__DIR__ . '/PHPMailer-master/src/Exception.php');
require_once(__DIR__ . '/PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__ . '/PHPMailer-master/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Handle Approval/Rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['inv_action'])) {
    $inv_id = $_POST['inv_id'];
    $action = $_POST['inv_action'];
    
    $pdo->beginTransaction();
    try {
        if ($action == 'approve') {
            // 1. Activate Investment & Start ROI
            $pdo->prepare("UPDATE investments SET status = 'active', payout_started_at = NOW() WHERE id = ?")->execute([$inv_id]);
            
            // 2. Fetch details for bonus eligibility check AND email notification
            $stmt = $pdo->prepare("SELECT i.user_id, i.amount, i.wallet_deduction, i.hash_ref, u.username, u.email FROM investments i JOIN users u ON i.user_id = u.id WHERE i.id = ?");
            $stmt->execute([$inv_id]);
            $inv_data = $stmt->fetch();

            /* * REMOVED: Step 3 (Wallet Deduction). 
             * The wallet is already deducted on the user side when the investment is submitted as pending. 
             * Doing it here was causing a double-deduction.
             */

            // 4. Bonus Logic: Distribute commissions
            distributeMLMCommissions($pdo, $inv_id);
            
            logAdminAction($pdo, "Approved Investment #$inv_id (Split: Wallet \${$inv_data['wallet_deduction']})", $inv_data['user_id']);
            
            // 5. DISPATCH SUCCESSFUL DEPOSIT EMAIL
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
                
                $mail->addAddress($inv_data['email']);
                
                $mail->isHTML(true);
                $mail->Subject = 'Deposit Successfully Received';
                
                $username = $inv_data['username'];
                $display_txid = !empty($inv_data['hash_ref']) ? $inv_data['hash_ref'] : 'INTERNAL-INV-'.$inv_id;
                $display_amount = '$' . number_format($inv_data['amount'], 2);

                $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{margin:0;padding:0;background:#f2f4f8;font-family:Arial, Helvetica, sans-serif;}
.wrapper{width:100%;padding:30px 10px;}
.container{max-width:600px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,0.08);}
.header{background:#0f172a;padding:35px;text-align:center;}
.logo{max-width:180px;}
.content{padding:35px;color:#333;line-height:1.6;}
.title{font-size:24px;font-weight:bold;margin-bottom:15px;color:#111;}
.subtitle{font-size:14px;color:#666;margin-bottom:25px;}
.card{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin-top:20px;}
.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;}
.row:last-child{border-bottom:none;}
.label{color:#777;font-size:14px;}
.value{font-weight:bold;font-size:14px;}
.status{color:#16a34a;font-weight:bold;}
.button{display:inline-block;margin-top:25px;background:#2563eb;color:#ffffff;text-decoration:none;padding:14px 30px;border-radius:6px;font-weight:bold;font-size:14px;}
.footer{background:#f1f5f9;text-align:center;padding:25px;font-size:12px;color:#777;}
.website{margin-top:10px;font-weight:bold;color:#111;}
.social{margin-top:15px;}
.social a{margin:0 6px;text-decoration:none;font-size:12px;color:#2563eb;}
@media only screen and (max-width:600px){.content{padding:25px;}.row{flex-direction:column;}.value{margin-top:4px;}}
</style>
</head>
<body>
<div class="wrapper">
<div class="container">
<div class="header">
<img src="https://leveragefactory.ai/wp-content/uploads/2026/02/logo.png" class="logo">
</div>
<div class="content">
<div class="title">Deposit Successfully Received</div>
<div class="subtitle">Hello <strong>$username</strong>, your deposit has been credited to your wallet.</div>
<div class="card">
<div class="row"><div class="label">Transaction ID</div><div class="value"> $display_txid</div></div>
<div class="row"><div class="label">Amount</div><div class="value"> $display_amount</div></div>
<div class="row"><div class="label">Currency</div><div class="value"> USD</div></div>
<div class="row"><div class="label">Status</div><div class="value status"> Completed</div></div>
</div>
<center><a href="https://leveragefactory.ai/dashboard" class="button">View Dashboard</a></center>
<p style="margin-top:25px;font-size:13px;color:#666;">If you did not authorize this transaction, please contact support immediately.</p>
</div>
<div class="footer">
Security Reminder: Never share your login credentials or verification codes.
<div class="website">https://leveragefactory.ai</div>
</div>
</div>
</div>
</body>
</html>
HTML;
                $mail->send();
            } catch (Exception $e) {
                // Fail silently so the admin interface still redirects successfully
            }

        } else {
            
            $stmt = $pdo->prepare("SELECT user_id, wallet_deduction, status FROM investments WHERE id = ? FOR UPDATE");
            $stmt->execute([$inv_id]);
            $invoce = $stmt->fetch();
            $refund_amount = (float)$invoce['wallet_deduction'];
            $uid = (int)$invoce['user_id'];
            
            $pdo->prepare("UPDATE investments SET status = 'rejected' WHERE id = ?")->execute([$inv_id]);
            
            if ($refund_amount > 0) {
                // A. Sweep the deducted funds back into the Main E-Wallet
                $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")
                    ->execute([$refund_amount, $uid]);
                
                // B. Log the refund using the 'reverse_back' transaction type
                $desc = "Amount Reversed: Node #" . $inv_id . " rejected by Admin. Refunded to wallet.";
                $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description, created_at) VALUES (?, ?, 'reverse_back', ?, NOW())")
                    ->execute([$uid, $refund_amount, $desc]);
            }
            
            logAdminAction($pdo, "Rejected Investment #$inv_id");
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
    header("Location: pending_investments.php?success=1");
    exit();
}

// UPDATED QUERY: Included 'wallet_deduction' field
$query = "SELECT i.*, u.username, u.email, s.name as scheme_name, c.symbol 
          FROM investments i 
          JOIN users u ON i.user_id = u.id 
          JOIN investment_schemes s ON i.scheme_id = s.id 
          LEFT JOIN currencies c ON i.currency_id = c.id 
          WHERE i.status = 'pending' ORDER BY i.created_at ASC";
$pendings = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Approvals - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Payment Approval Queue</h1>
            <p class="text-gray-500">Verify split-payment details and external screenshots.</p>
        </header>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-bold">
                    <tr>
                        <th class="py-4 px-6">User / Plan</th>
                        <th class="py-4 px-6">Funding Breakdown</th>
                        <th class="py-4 px-6">External Hash</th>
                        <th class="py-4 px-6">Proof</th>
                        <th class="py-4 px-6 text-right">Decision</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach($pendings as $p): 
                        $external_deposit = $p['amount'] - $p['wallet_deduction'];
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-4 px-6">
                            <div class="font-bold text-gray-900"><?php echo htmlspecialchars($p['username']); ?></div>
                            <div class="text-[9px] bg-red-100 text-red-700 px-2 py-0.5 rounded-full inline-block font-black uppercase mt-1">
                                <?php echo htmlspecialchars($p['scheme_name']); ?>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-400 font-bold uppercase tracking-tighter">Total:</span>
                                    <span class="font-black">$<?php echo number_format($p['amount'], 2); ?></span>
                                </div>
                                <div class="flex justify-between text-[10px] text-blue-500 font-bold">
                                    <span class="uppercase tracking-tighter">Wallet Share:</span>
                                    <span>-$<?php echo number_format($p['wallet_deduction'], 2); ?></span>
                                </div>
                                <div class="flex justify-between text-[10px] text-green-600 font-black border-t border-gray-100 pt-1">
                                    <span class="uppercase tracking-tighter italic">Net Deposit:</span>
                                    <span>$<?php echo number_format($external_deposit, 2); ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <?php if($external_deposit > 0): ?>
                                <code class="text-[10px] bg-gray-100 p-1.5 rounded text-blue-600 block truncate w-32">
                                    <?php echo htmlspecialchars($p['hash_ref']); ?>
                                </code>
                            <?php else: ?>
                                <span class="text-[10px] font-black text-gray-300 uppercase italic">100% Wallet Paid</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6">
                            <?php if($p['screenshot_url']): ?>
                                <button onclick="viewProof('../uploads/payments/<?php echo $p['screenshot_url']; ?>')" class="text-blue-500 hover:text-blue-700 font-bold text-[10px] uppercase">
                                    View Proof
                                </button>
                                <div id="proofOverlay" class="hidden fixed inset-0 bg-black/90 z-[100] flex-col items-center justify-center p-4 backdrop-blur-sm" onclick="this.classList.add('hidden'); this.classList.remove('flex');">
                                    <div class="mb-6 text-white/50 text-[10px] font-black uppercase tracking-[0.3em] flex gap-4">
                                        <span>Click anywhere to dismiss protocol_</span>
                                    </div>
                                    <div class="relative group max-w-5xl w-full flex justify-center">
                                        <img id="overlayImg" src="" class="max-w-full max-h-[80vh] rounded-[2rem] shadow-[0_0_100px_rgba(0,0,0,0.5)] border border-white/10 object-contain">
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-300 font-bold text-[10px] uppercase">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <form method="POST" class="inline-flex gap-2">
                                <input type="hidden" name="inv_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" name="inv_action" value="approve" class="bg-green-600 text-white px-4 py-2 rounded-lg text-xs font-bold">Approve</button>
                                <button type="submit" name="inv_action" value="reject" class="bg-white border text-gray-500 px-4 py-2 rounded-lg text-xs font-bold">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script>
        function viewProof(src) {
            const overlay = document.getElementById('proofOverlay');
            const img = document.getElementById('overlayImg');
            
            // Update the image source and show the overlay
            img.src = src;
            overlay.classList.remove('hidden');
            overlay.classList.add('flex'); // Ensure it uses flex for centering
        }
    
        // Close on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape") {
                document.getElementById('proofOverlay').classList.add('hidden');
                document.getElementById('proofOverlay').classList.remove('flex');
            }
        });
    </script>
    </body>
</html>