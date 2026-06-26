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

// Handle Post Actions (Lock/Recharge/Add Gold/Password Reset)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $target_id = $_POST['user_id'];
    
    if ($_POST['action'] == 'toggle_lock') {
        $pdo->prepare("UPDATE users SET is_locked = NOT is_locked WHERE id = ?")->execute([$target_id]);
        logAdminAction($pdo, "Toggled lock for User #$target_id", $target_id);
    }
    
    // RECHARGE USD FUNDS PROTOCOL
    if ($_POST['action'] == 'recharge' && !empty($_POST['amount'])) {
        $amount = (float)$_POST['amount'];

        $stmt_email = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
        $stmt_email->execute([$target_id]);
        $target_user_data = $stmt_email->fetch();

        if ($target_user_data) {
            $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$amount, $target_id]);
            $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'admin_recharge', 'Manual Admin Recharge')")->execute([$target_id, $amount]);
            
            // SMTP Notification
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'localhost'; // Outgoing Server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'admin@leveragefactory.ai'; // Username
                $mail->Password   = 'IR=hxVT!u]&3'; // Your actual password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Port 465 uses SMTPS
                $mail->Port       = 465; // Port
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
                $mail->setFrom('admin@leveragefactory.ai', 'Leverage Factory');
                $mail->addReplyTo('admin@leveragefactory.ai', 'Leverage Factory');
                $mail->Sender     = 'admin@leveragefactory.ai';
                $mail->addAddress($target_user_data['email']); 
                $mail->isHTML(true);
                $mail->Subject = 'Wallet Recharge Successful';
                $mail->Body = "
                <div style='background-color: #0f172a; padding: 40px; font-family: sans-serif; color: #ffffff; text-align: center;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #1e293b; border-radius: 24px; overflow: hidden; border: 1px solid #334155;'>
                        <div style='padding: 30px; background-color: #000000;'>
                            <h1 style='margin: 0; font-size: 20px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; font-style: italic;'>
                                LEVERAGE <span style='color: #00A6FB;'>FACTORY</span>_
                            </h1>
                        </div>
                        <div style='padding: 40px; text-align: left;'>
                            <p style='color: #00A6FB; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 10px;'>Transaction Confirmed</p>
                            <h2 style='font-size: 24px; font-weight: 900; margin-top: 0; margin-bottom: 20px; color: #ffffff;'>WALLET RECHARGED</h2>
                            <p style='color: #94a3b8; font-size: 14px; line-height: 1.6;'>
                                Hello <b>" . htmlspecialchars($target_user_data['username']) . "</b>,<br><br>
                                Your financial node has been successfully updated. The administrator has added funds to your wallet balance.
                            </p>
                            <div style='background: #0f172a; padding: 25px; border-radius: 16px; border: 1px solid #334155; margin: 30px 0; text-align: center;'>
                                <p style='margin: 0; font-size: 12px; color: #94a3b8; text-transform: uppercase; font-weight: bold;'>Credit Amount</p>
                                <p style='margin: 10px 0 0 0; font-size: 32px; font-weight: 900; color: #10b981;'>$ " . number_format($amount, 2) . "</p>
                            </div>
                            <div style='text-align: center; margin-top: 20px;'>
                                <a href='https://leveragefactory.ai/dashboard.php' style='background-color: #00A6FB; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 12px; font-weight: 900; text-transform: uppercase; font-size: 11px;'>View Balance</a>
                            </div>
                        </div>
                        <div style='padding: 20px; background-color: #0f172a; border-top: 1px solid #334155;'>
                            <p style='color: #475569; font-size: 10px; font-weight: 800; text-transform: uppercase;'>Automated Financial Alert | Do Not Reply</p>
                        </div>
                    </div>
                </div>";
                $mail->send();
            } catch (Exception $e) { /* Log error silently or use error_log($e->getMessage()); */ }
        }
        logAdminAction($pdo, "Recharged $$amount to User #$target_id", $target_id);
    }

    // ADD GOLD PROTOCOL 
    if ($_POST['action'] == 'add_gold' && !empty($_POST['gold_amount'])) {
        $gold_amount = (float)$_POST['gold_amount'];

        $stmt_email = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
        $stmt_email->execute([$target_id]);
        $target_user_data = $stmt_email->fetch();

        if ($target_user_data) {
            $pdo->prepare("UPDATE users SET gold_wallet = gold_wallet + ? WHERE id = ?")->execute([$gold_amount, $target_id]);
            $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'gold_bonus', 'Manual Admin Gold Credit')")->execute([$target_id, $gold_amount]);
            
            // SMTP Notification for Gold
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'localhost';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'admin@leveragefactory.ai';
                $mail->Password   = 'IR=hxVT!u]&3';
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
                $mail->setFrom('admin@leveragefactory.ai', 'Leverage Factory');
                $mail->addReplyTo('admin@leveragefactory.ai', 'Leverage Factory');
                $mail->Sender     = 'admin@leveragefactory.ai';
                $mail->addAddress($target_user_data['email']); 
                $mail->isHTML(true);
                $mail->Subject = 'Gold Wallet Credit Successful';
                $mail->Body = "
                <div style='background-color: #0f172a; padding: 40px; font-family: sans-serif; color: #ffffff; text-align: center;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #1e293b; border-radius: 24px; overflow: hidden; border: 1px solid #334155;'>
                        <div style='padding: 30px; background-color: #000000;'>
                            <h1 style='margin: 0; font-size: 20px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; font-style: italic;'>
                                LEVERAGE <span style='color: #00A6FB;'>FACTORY</span>_
                            </h1>
                        </div>
                        <div style='padding: 40px; text-align: left;'>
                            <p style='color: #fbbf24; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 10px;'>Transaction Confirmed</p>
                            <h2 style='font-size: 24px; font-weight: 900; margin-top: 0; margin-bottom: 20px; color: #ffffff;'>GOLD CREDITED</h2>
                            <p style='color: #94a3b8; font-size: 14px; line-height: 1.6;'>
                                Hello <b>" . htmlspecialchars($target_user_data['username']) . "</b>,<br><br>
                                Your digital vault has been successfully updated. The administrator has added Gold to your balance.
                            </p>
                            <div style='background: #0f172a; padding: 25px; border-radius: 16px; border: 1px solid #334155; margin: 30px 0; text-align: center;'>
                                <p style='margin: 0; font-size: 12px; color: #94a3b8; text-transform: uppercase; font-weight: bold;'>Credit Amount</p>
                                <p style='margin: 10px 0 0 0; font-size: 32px; font-weight: 900; color: #fbbf24;'>" . number_format($gold_amount, 4) . " gm</p>
                            </div>
                            <div style='text-align: center; margin-top: 20px;'>
                                <a href='https://leveragefactory.ai/dashboard.php' style='background-color: #00A6FB; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 12px; font-weight: 900; text-transform: uppercase; font-size: 11px;'>View Balance</a>
                            </div>
                        </div>
                        <div style='padding: 20px; background-color: #0f172a; border-top: 1px solid #334155;'>
                            <p style='color: #475569; font-size: 10px; font-weight: 800; text-transform: uppercase;'>Automated Financial Alert | Do Not Reply</p>
                        </div>
                    </div>
                </div>";
                $mail->send();
            } catch (Exception $e) { /* Log error silently */ }
        }
        logAdminAction($pdo, "Added {$gold_amount}gm Gold to User #$target_id", $target_id);
    }

    // ADMIN PASSWORD OVERRIDE PROTOCOL
    if ($_POST['action'] == 'change_password' && !empty($_POST['new_password'])) {
        $hashed_password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hashed_password, $target_id]);
        logAdminAction($pdo, "Force changed password for User #$target_id", $target_id);
    }

    header("Location: manage_users.php?success=1");
    exit();
}

// Optimized Query to get REFID, Personal Investment, and Team Turnover
$query = "SELECT u.*, s.username as sponsor_name,
          (SELECT SUM(amount) FROM investments WHERE user_id = u.id AND status = 'active') as personal_investment,
          (SELECT COUNT(*) FROM users WHERE referrer_id = u.id) as direct_downline,
          (SELECT SUM(i.amount) FROM investments i JOIN users d ON i.user_id = d.id 
           WHERE (d.path LIKE CONCAT('%/', u.id, '/%') OR d.referrer_id = u.id) AND i.status = 'active') as team_turnover
          FROM users u 
          LEFT JOIN users s ON u.referrer_id = s.id 
          ORDER BY u.created_at DESC";
$users = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Real-time Search Protocol
        function filterDirectory() {
            const input = document.getElementById('adminSearch').value.toLowerCase();
            const rows = document.querySelectorAll('.user-row');
            
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search').toLowerCase();
                if (searchData.includes(input)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</head>
<body class="bg-gray-100 text-gray-800 flex h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div id="action-alert" class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-md shadow-sm flex justify-between items-center transition-opacity duration-500">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-bold text-green-700 uppercase tracking-wide">Security Action Executed Successfully.</p>
            </div>
            <button onclick="document.getElementById('action-alert').style.display='none'" class="text-green-500 hover:text-green-700 font-bold focus:outline-none">
                &times;
            </button>
        </div>
        <script>
            // Auto-hide alert after 4 seconds
            setTimeout(function() {
                const alertBox = document.getElementById('action-alert');
                if(alertBox) {
                    alertBox.style.opacity = '0';
                    setTimeout(() => alertBox.style.display = 'none', 500);
                }
            }, 4000);
            
            // Remove ?success=1 from URL so it doesn't reappear on refresh
            if (window.history.replaceState) {
                const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path:newUrl}, '', newUrl);
            }
        </script>
        <?php endif; ?>
        <header class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 uppercase italic">Member <span class="text-[#00A6FB]">Directory</span></h1>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest">Global Node Administration</p>
            </div>

            <div class="relative w-full md:w-80">
                <input type="text" id="adminSearch" onkeyup="filterDirectory()" 
                       placeholder="Search Username or Email..." 
                       class="w-full px-5 py-3 rounded-xl border border-gray-200 shadow-sm outline-none focus:ring-2 focus:ring-[#00A6FB] font-bold text-sm bg-white">
                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="3"/>
                    </svg>
                </div>
            </div>
        </header>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-200 text-[10px] uppercase text-gray-500 font-black">
                    <tr>
                        <th class="py-4 px-6">User / REFID / Upline</th>
                        <th class="py-4 px-6">Financial Node (Personal/Team)</th>
                        <th class="py-4 px-6">Wallet Balance</th>
                        <th class="py-4 px-6">Security Actions</th>
                        <th class="py-4 px-6">Network Tree</th>
                        <th class="py-4 px-6 text-right">Audit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <?php foreach ($users as $user): ?>
                    <tr class="user-row hover:bg-gray-50 transition" 
                        data-search="<?php echo htmlspecialchars($user['username'] . ' ' . $user['email']); ?>">
                        
                        <td class="py-4 px-6">
                            <div class="font-black text-gray-900"><?php echo htmlspecialchars($user['username']); ?></div>
                            <div class="text-[10px] text-[#00A6FB] font-bold">REFID: <?php echo $user['referral_code']; ?></div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase">Upline: <?php echo $user['sponsor_name'] ?? 'MASTER_ROOT'; ?></div>
                        </td>
                        
                        <td class="py-4 px-6">
                            <div class="flex flex-col gap-1">
                                <div class="flex justify-between w-48 text-[10px] font-bold">
                                    <span class="text-gray-400 uppercase">Personal:</span>
                                    <span class="text-gray-900">$<?php echo number_format($user['personal_investment'] ?? 0, 2); ?></span>
                                </div>
                                <div class="flex justify-between w-48 text-[10px] font-black border-t border-gray-100 pt-1">
                                    <span class="text-[#00A6FB] uppercase">Team Turnover:</span>
                                    <span class="text-[#00A6FB]">$<?php echo number_format($user['team_turnover'] ?? 0, 2); ?></span>
                                </div>
                                <div class="text-[9px] text-gray-300 font-bold uppercase"><?php echo $user['direct_downline']; ?> Direct Referrals</div>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="font-mono font-black text-green-600 text-lg">$<?php echo number_format($user['wallet_balance'], 2); ?></div>
                            <span class="px-2 py-0.5 rounded-full text-[8px] font-black <?php echo $user['is_locked'] ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                                <?php echo $user['is_locked'] ? 'LOCKED' : 'ACTIVE'; ?>
                            </span>
                        </td>
                        <td class="py-4 px-6">
                            <div class="space-y-3">
                                <form method="POST" class="flex gap-1" onsubmit="return confirm('Are you sure you want to add funds to this user\'s wallet?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="number" name="amount" step="0.01" required placeholder="Add Funds" class="w-24 px-2 py-1 border rounded text-[10px] font-bold outline-none focus:ring-1 focus:ring-blue-500">
                                    <button type="submit" name="action" value="recharge" class="bg-blue-600 text-white px-3 py-1 rounded text-[9px] font-black uppercase">Recharge</button>
                                </form>

                                <form method="POST" class="flex gap-1" onsubmit="return confirm('Are you sure you want to credit Gold to this user?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="number" name="gold_amount" step="0.0001" required placeholder="Add Gold" class="w-24 px-2 py-1 border rounded text-[10px] font-bold outline-none focus:ring-1 focus:ring-yellow-500">
                                    <button type="submit" name="action" value="add_gold" class="bg-yellow-500 text-white px-3 py-1 rounded text-[9px] font-black uppercase hover:bg-yellow-600 transition">Add Gold</button>
                                </form>

                                <form method="POST" class="flex gap-1" onsubmit="return confirm('WARNING: Are you sure you want to force change this user\'s password?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="text" name="new_password" required placeholder="New Password" class="w-24 px-2 py-1 border rounded text-[10px] font-bold outline-none focus:ring-1 focus:ring-red-500">
                                    <button type="submit" name="action" value="change_password" class="bg-red-600 text-white px-3 py-1 rounded text-[9px] font-black uppercase">Reset PWD</button>
                                </form>

                                <form method="POST" onsubmit="return confirm('Are you sure you want to change the lock status for this security node?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="action" value="toggle_lock" class="w-full border border-gray-200 px-3 py-1 rounded text-[9px] font-black uppercase hover:bg-black hover:text-white transition">
                                        <?php echo $user['is_locked'] ? 'Unlock Wallet' : 'Lock Security Node'; ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <a href="network_tree.php?id=<?php echo $user['id']; ?>" class="bg-black text-white px-4 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-[#00A6FB] transition-all">Network Tree</a>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <a href="view_user.php?id=<?php echo $user['id']; ?>" class="bg-black text-white px-4 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-[#00A6FB] transition-all">View_Audit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>